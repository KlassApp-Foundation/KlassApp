<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Releases one or more phone numbers from their test-school ties without
 * purging the school that owns them.
 *
 * A number is "tied" to a test school when any of these is an is_test school:
 *   (A) whatsapp_users.school_id for a row whose phone matches
 *   (B) users.school_id for the user_id on that whatsapp_users row
 *   (C) users.school_id for any user whose mobile_no matches
 *
 * On release:
 *   - users.mobile_no is set to NULL for every test-school user whose number
 *     matches (the user is not deleted).
 *   - whatsapp_users rows whose school_id or user_id→school_id is an is_test
 *     school are deleted (this removes the TestSchoolCommsGuard ties A and B).
 *   - Orphan phone rows in the other PHONE_SCOPED tables (early_access_requests,
 *     whatsapp_pending_parent_links, whatsapp_pending_confirmations,
 *     parent_link_requests, message_delivery_log,
 *     whatsapp_verification_challenges) that carry no school_id FK or whose
 *     school_id is an is_test school are deleted.
 *
 * Safety:
 *   - Refuses if the number belongs (mobile_no) to a user at a non-test school.
 *   - Refuses if the number belongs to a platform admin (usergroup 1 or 2).
 *   - Dry-run by default; use --force to execute.
 *
 * Run on purpose only (never automatic, never from a migration):
 *     php artisan test:release-phone --phone=+256781940358          (dry run)
 *     php artisan test:release-phone --phone=+256781940358 --force   (release)
 */
class TestReleasePhoneCommand extends Command
{
    protected $signature = 'test:release-phone
        {--phone=* : Phone number(s) to release from test-school ties (repeatable)}
        {--force : Actually modify/delete rows. Without this flag the command is a dry run.}';

    protected $description = 'Release phone number(s) from test-school ties (dry run unless --force).';

    /** Platform-level user groups: super admin (1) and platform co-admin (2). */
    private const PLATFORM_USERGROUPS = [1, 2];

    /**
     * Tables cleared by phone cleanup.  Key = table name, value = phone column.
     * whatsapp_users is handled separately (it has school_id / user_id ties).
     */
    private const PHONE_SCOPED = [
        'early_access_requests'            => 'phone',
        'whatsapp_pending_parent_links'    => 'phone',
        'whatsapp_pending_confirmations'   => 'phone',
        'parent_link_requests'             => 'phone',
        'message_delivery_log'             => 'phone',
        'whatsapp_verification_challenges' => 'phone_digits',
    ];

    public function handle(): int
    {
        $phones = array_values(array_unique(array_filter(array_map(
            fn ($p) => trim((string) $p),
            (array) $this->option('phone')
        ))));

        if (empty($phones)) {
            $this->error('Pass at least one --phone=<number>.');

            return self::FAILURE;
        }

        foreach ($phones as $phone) {
            $result = $this->processPhone($phone);
            if ($result !== self::SUCCESS) {
                return $result;
            }
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('Dry run — nothing was modified. Re-run with --force to execute.');
        }

        return self::SUCCESS;
    }

    private function processPhone(string $phone): int
    {
        $forms = $this->phoneForms($phone);
        if ($forms === []) {
            $this->error("phone {$phone} is not a readable number — refusing.");

            return self::FAILURE;
        }

        // --- Safety: refuse if any user with this mobile_no is at a real (non-test) school.
        //   Platform admins (groups 1 & 2) and no-school users are not test-school ties;
        //   they are listed as ignored and processing continues.
        //   Only a tie to a confirmed non-test school causes a hard refusal. ---
        $users = DB::table('users')
            ->whereIn('mobile_no', $forms)
            ->get(['id', 'school_id', 'usergroup_id', 'mobile_no']);

        foreach ($users as $user) {
            if (! $this->matchesFullNumber((string) $user->mobile_no, $forms)) {
                continue;
            }
            if (in_array((int) $user->usergroup_id, self::PLATFORM_USERGROUPS, true)) {
                $this->line("  ignored: user {$user->id} is a platform admin, not a test-school tie.");
                continue;
            }
            $userSchoolId = (int) $user->school_id;
            if ($userSchoolId < 1) {
                $this->line("  ignored: user {$user->id} has no school, not a test-school tie.");
                continue;
            }
            if (! $this->isTestSchool($userSchoolId)) {
                $this->error("phone {$phone} belongs to user {$user->id} at non-test school {$userSchoolId} — refusing.");

                return self::FAILURE;
            }
        }

        // --- Tie analysis ---
        $ties = $this->findTies($phone, $forms);

        $this->printTies($phone, $ties);

        if (! $this->option('force')) {
            return self::SUCCESS;
        }

        // --- Execute ---
        DB::transaction(function () use ($phone, $forms, $ties) {
            // (C) Null mobile_no for test-school users.
            $mobileUserIds = array_column(
                array_filter($ties, fn ($t) => $t['tie'] === 'users.mobile_no'),
                'user_id'
            );
            if ($mobileUserIds) {
                DB::table('users')->whereIn('id', $mobileUserIds)->update(['mobile_no' => null]);
                $this->line("  users.mobile_no: " . count($mobileUserIds) . " row(s) nulled.");
            }

            // (A+B) Delete whatsapp_users rows tied to test schools.
            $wauIds = array_column(
                array_filter($ties, fn ($t) => in_array($t['tie'], ['whatsapp_users.school_id', 'whatsapp_users.user_id→school_id'], true)),
                'row_id'
            );
            $wauIds = array_values(array_unique(array_filter($wauIds)));
            if ($wauIds) {
                DB::table('whatsapp_users')->whereIn('id', $wauIds)->delete();
                $this->line("  whatsapp_users: " . count($wauIds) . " row(s) deleted.");
            }

            // Orphan phone rows in other tables (no school_id or is_test school_id).
            $allForms = $this->allForms($forms);
            foreach (self::PHONE_SCOPED as $table => $column) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                    continue;
                }
                $query = DB::table($table)->whereIn($column, $allForms);
                if (Schema::hasColumn($table, 'school_id')) {
                    $testSchoolIds = $this->testSchoolIds();
                    $query->where(function ($q) use ($testSchoolIds) {
                        $q->whereNull('school_id')->orWhereIn('school_id', $testSchoolIds);
                    });
                }
                $count = $query->delete();
                if ($count) {
                    $this->line("  {$table}: {$count} row(s) deleted.");
                }
            }
        });

        $this->info("phone {$phone} released.");

        return self::SUCCESS;
    }

    /**
     * Find every test-school tie for this number. Returns a list of tie
     * descriptors, each with the keys: tie, row_id/user_id, school_id,
     * school_name, is_test.
     *
     * @param  list<string>  $forms
     * @return list<array{tie: string, row_id: int|null, user_id: int|null, school_id: int, school_name: string, is_test: bool}>
     */
    private function findTies(string $phone, array $forms): array
    {
        $ties = [];
        $allForms = $this->allForms($forms);

        // whatsapp_users rows.
        if (Schema::hasTable('whatsapp_users')) {
            $rows = DB::table('whatsapp_users')
                ->whereIn('phone', $allForms)
                ->get(['id', 'phone', 'school_id', 'user_id']);

            foreach ($rows as $row) {
                if (! $this->matchesFullNumber((string) $row->phone, $forms)) {
                    continue;
                }

                // Tie A: whatsapp_users.school_id → is_test school.
                $rowSchoolId = (int) ($row->school_id ?? 0);
                if ($rowSchoolId > 0) {
                    $school = School::find($rowSchoolId);
                    if ($school && $school->is_test) {
                        $ties[] = [
                            'tie'         => 'whatsapp_users.school_id',
                            'row_id'      => (int) $row->id,
                            'user_id'     => null,
                            'school_id'   => $rowSchoolId,
                            'school_name' => (string) $school->name,
                            'is_test'     => true,
                        ];
                    }
                }

                // Tie B: whatsapp_users.user_id → users.school_id → is_test.
                $rowUserId = (int) ($row->user_id ?? 0);
                if ($rowUserId > 0) {
                    $userSchoolId = (int) DB::table('users')->where('id', $rowUserId)->value('school_id');
                    if ($userSchoolId > 0 && $this->isTestSchool($userSchoolId)) {
                        $school = School::find($userSchoolId);
                        if ($school) {
                            $ties[] = [
                                'tie'         => 'whatsapp_users.user_id→school_id',
                                'row_id'      => (int) $row->id,
                                'user_id'     => $rowUserId,
                                'school_id'   => $userSchoolId,
                                'school_name' => (string) $school->name,
                                'is_test'     => true,
                            ];
                        }
                    }
                }
            }
        }

        // Tie C: users.mobile_no → is_test school.
        // Platform admins (groups 1 & 2) are excluded: they are not test-school ties.
        $users = DB::table('users')
            ->whereIn('mobile_no', $allForms)
            ->get(['id', 'mobile_no', 'school_id', 'usergroup_id']);

        foreach ($users as $user) {
            if (! $this->matchesFullNumber((string) $user->mobile_no, $forms)) {
                continue;
            }
            if (in_array((int) $user->usergroup_id, self::PLATFORM_USERGROUPS, true)) {
                continue;
            }
            $userSchoolId = (int) $user->school_id;
            if ($this->isTestSchool($userSchoolId)) {
                $school = School::find($userSchoolId);
                if ($school) {
                    $ties[] = [
                        'tie'         => 'users.mobile_no',
                        'row_id'      => null,
                        'user_id'     => (int) $user->id,
                        'school_id'   => $userSchoolId,
                        'school_name' => (string) $school->name,
                        'is_test'     => true,
                    ];
                }
            }
        }

        return $ties;
    }

    private function printTies(string $phone, array $ties): void
    {
        $count = count($ties);
        $action = $this->option('force') ? 'releasing' : 'would release';
        $label = $count === 0 ? 'no test-school ties' : "{$count} tie(s) found ({$action})";

        $this->newLine();
        $this->info("phone {$phone}");
        $this->line("  {$label}");

        foreach ($ties as $t) {
            $schoolLabel = "school_id={$t['school_id']} ({$t['school_name']}, is_test=1)";

            if ($t['tie'] === 'users.mobile_no') {
                $this->line("  tie: {$t['tie']}  user_id={$t['user_id']}  {$schoolLabel}");
            } elseif ($t['tie'] === 'whatsapp_users.school_id') {
                $this->line("  tie: {$t['tie']}  row_id={$t['row_id']}  {$schoolLabel}");
            } else {
                // whatsapp_users.user_id→school_id
                $this->line("  tie: {$t['tie']}  row_id={$t['row_id']}  user_id={$t['user_id']}  {$schoolLabel}");
            }
        }

        if ($count === 0) {
            $this->line('  (nothing to release)');
        }
    }

    private function isTestSchool(int $schoolId): bool
    {
        if ($schoolId < 1) {
            return false;
        }

        return School::query()->whereKey($schoolId)->where('is_test', 1)->exists();
    }

    /**
     * All stored forms of a phone number, including every stored spelling
     * (as given, digits-only, +digits, 256-prefix, trunk-0).
     *
     * @return list<string>
     */
    private function phoneForms(string $phone): array
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if ($digits === '') {
            return [];
        }

        $forms = array_filter([$phone, $digits, '+'.$digits], fn ($form) => $form !== '');

        if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
            $international = '256'.substr($digits, 1);
            $forms[] = $international;
            $forms[] = '+'.$international;
        }

        if (str_starts_with($digits, '256') && strlen($digits) >= 12) {
            $forms[] = '0'.substr($digits, 3);
        }

        return array_values(array_unique($forms));
    }

    /**
     * @param  list<string>  $forms  canonical forms from phoneForms()
     * @return list<string>          all forms deduplicated, for use in whereIn
     */
    private function allForms(array $forms): array
    {
        return array_values(array_unique($forms));
    }

    /**
     * @param  list<string>  $forms
     */
    private function matchesFullNumber(string $stored, array $forms): bool
    {
        if (in_array($stored, $forms, true)) {
            return true;
        }
        $storedDigits = preg_replace('/\D/', '', $stored) ?? '';
        if ($storedDigits === '') {
            return false;
        }
        foreach ($forms as $form) {
            $formDigits = preg_replace('/\D/', '', $form) ?? '';
            if ($storedDigits === $formDigits) {
                return true;
            }
        }

        return false;
    }

    /**
     * All is_test school ids, for use in orphan-row cleanup.
     *
     * @return list<int>
     */
    private function testSchoolIds(): array
    {
        return School::query()->where('is_test', 1)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
