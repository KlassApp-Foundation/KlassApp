<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes E2E test schools created by the staging onboarding suite
 * (e2e/onboarding) and every row that belongs to them.
 *
 * Dry-run by default: without --force nothing is deleted; the command prints
 * the full inventory (every school, every user, and the exact per-table row
 * counts with ids) of exactly what a real run would remove.
 *
 * Safety rails:
 *  - Every targeted school must be flagged is_test AND named "E2E ..."
 *    (refuses anything else, so the phase-4 roster fixture and any other
 *    is_test school are never touched). A manually created test school with a
 *    real-looking name can only be targeted by also passing its exact name
 *    with --confirm-name; it must still be is_test.
 *  - Refuses a school that has a platform admin (usergroup 1) or platform
 *    co-admin (usergroup 2) among its users.
 *  - --email purges early-access requests and invites (school, teacher,
 *    co-admin) for that address, so it can sign up again. Refused when the
 *    address belongs to a user outside the targeted schools, or to a platform
 *    admin (usergroup 1 or 2).
 *  - --phone frees a number the same way: early-access requests, WhatsApp
 *    rows, parent link requests, pending confirmations, delivery logs and
 *    verification challenges. Refused when the number belongs to a user
 *    outside the targeted schools or to a platform admin.
 *  - Purging a school frees its own admin/user emails and phone numbers
 *    automatically (the same cleanup), so the same people can sign up again.
 *  - Demo schools are owned by demo:purge-schools and are refused here.
 *  - Idempotent: schools that are already gone are reported and skipped.
 *  - One transaction per school; a failure rolls that school back cleanly.
 *
 * Run on purpose only (never automatic, never from a migration):
 *     php artisan test:purge-schools --school=123           (dry run)
 *     php artisan test:purge-schools --school=123 --force    (delete)
 *     php artisan test:purge-schools --school=123 --confirm-name="Example Test School" \
 *         --email=admin@example.com --phone=+256700000123      (dry run)
 */
class TestPurgeSchoolsCommand extends Command
{
    protected $signature = 'test:purge-schools
        {--school=* : School ids to purge (repeatable; each must be an is_test school named "E2E ...")}
        {--confirm-name=* : Exact name of an is_test school that is not "E2E ..."-named, to allow targeting it (repeatable)}
        {--email=* : Also purge early-access requests and school/teacher/co-admin invites for this email (repeatable)}
        {--phone=* : Also purge early-access requests, WhatsApp rows, link requests, delivery logs and verification challenges for this number (repeatable)}
        {--force : Actually delete rows. Without this flag the command is a dry run.}';

    protected $description = 'Remove E2E test schools ("E2E ...", is_test) and their dependent rows (dry run unless --force).';

    /**
     * Tables with a school_id column, in foreign-key-safe delete order.
     * Same order as demo:purge-schools — MySQL enforces the constraints;
     * the test suite turns them on for sqlite too.
     */
    private const SCHOOL_SCOPED = [
        'activity_log',
        'current_plans',
        'subscriptions',
        'marks',
        'exams',
        'attendances',
        'fee_payments',
        'fees_categories',
        'student_academics',
        'admissions',
        'class_teacher_links',
        'teacher_invites',
        'student_parent_links',
        'co_admin_invites',
        'whatsapp_verification_challenges',
        'whatsapp_users',
        'school_details',
        'teacherprofile',
        'standards_link',
        'subjects',
        'sections',
        'school_grading_systems',
        'student_id_sequences',
        'standards',
        'academic_terms',
        'academic_years',
    ];

    /**
     * Tables cleared by --phone (and by a school purge for that school's own
     * numbers). The column is the stored phone, or phone_digits for challenges.
     */
    private const PHONE_SCOPED = [
        'early_access_requests' => 'phone',
        'whatsapp_users' => 'phone',
        'whatsapp_pending_parent_links' => 'phone',
        'whatsapp_pending_confirmations' => 'phone',
        'parent_link_requests' => 'phone',
        'message_delivery_log' => 'phone',
        'whatsapp_verification_challenges' => 'phone_digits',
    ];

    /** Tables with an email column cleared by --email, in delete order. */
    private const EMAIL_SCOPED = [
        'early_access_requests',
        'school_invites',
        'teacher_invites',
        'co_admin_invites',
    ];

    /** Platform-level user groups: super admin (1) and platform co-admin (2). */
    private const PLATFORM_USERGROUPS = [1, 2];

    /**
     * Tables with a user_id column whose FK to users makes them break the
     * users delete when left behind (staging FK audit of 2026-10-05:
     * authentications.user_id is NO ACTION and failed the purge with 32 live
     * rows). The user-scoped rows an E2E flow can create: sign-in records,
     * onboarding sessions, WhatsApp link confirmations, parent profiles and
     * Toshi personas.
     */
    private const USER_SCOPED = [
        'userprofiles',
        'authentications',
        'onboarding_sessions',
        'whatsapp_pending_confirmations',
        'parent_profiles',
        'toshi_personas',
    ];

    public function handle(): int
    {
        $ids = array_values(array_unique(array_map('intval', (array) $this->option('school'))));
        $confirmNames = array_values(array_map('strval', (array) $this->option('confirm-name')));
        $emails = array_values(array_unique(array_filter(array_map(
            fn ($e) => strtolower(trim((string) $e)),
            (array) $this->option('email')
        ))));
        $phones = array_values(array_unique(array_filter(array_map(
            fn ($p) => trim((string) $p),
            (array) $this->option('phone')
        ))));

        if (empty($ids) && empty($emails) && empty($phones)) {
            $this->error('Pass at least one --school=<id>, --email=<address> or --phone=<number>.');

            return self::FAILURE;
        }

        $schools = [];
        foreach ($ids as $id) {
            $school = \App\Models\School::find($id);

            if (! $school) {
                $this->line("school {$id}: already removed (idempotent skip).");
                continue;
            }

            if ($school->is_demo) {
                $this->error("school {$id} ({$school->name}) is a demo school — use demo:purge-schools for it.");

                return self::FAILURE;
            }

            if (! $school->is_test) {
                $this->error("school {$id} ({$school->name}) is not flagged is_test — refusing to purge it.");

                return self::FAILURE;
            }

            $confirmed = in_array((string) $school->name, $confirmNames, true);
            if (! str_starts_with((string) $school->name, 'E2E ') && ! $confirmed) {
                $this->error("school {$id} ({$school->name}) is not an E2E test school (name must start with \"E2E \", or pass --confirm-name with its exact name) — refusing to purge it.");

                return self::FAILURE;
            }

            $platformUsers = DB::table('users')
                ->where('school_id', $school->id)
                ->whereIn('usergroup_id', self::PLATFORM_USERGROUPS)
                ->pluck('id')->all();
            if ($platformUsers) {
                $this->error("school {$id} ({$school->name}) has platform admin user(s) " . implode(',', $platformUsers) . ' — refusing to purge it.');

                return self::FAILURE;
            }

            $schools[] = $school;
        }

        foreach ($emails as $email) {
            $owners = DB::table('users')->whereRaw('lower(email) = ?', [$email])->get(['id', 'school_id', 'usergroup_id']);
            foreach ($owners as $owner) {
                if (in_array((int) $owner->usergroup_id, self::PLATFORM_USERGROUPS, true)) {
                    $this->error("email {$email} belongs to platform admin user {$owner->id} — refusing.");

                    return self::FAILURE;
                }
                if (! in_array((int) $owner->school_id, $ids, true)) {
                    $this->error("email {$email} belongs to user {$owner->id} at school {$owner->school_id}, outside the targeted schools — refusing.");

                    return self::FAILURE;
                }
            }
        }

        if (empty($schools) && empty($emails) && empty($phones)) {
            $this->info('Nothing to purge.');

            return self::SUCCESS;
        }

        $plan = [];
        foreach ($schools as $school) {
            $plan[$school->id] = $this->inventoryFor($school);
        }

        // Purging a school frees the people in it: their emails and numbers
        // go through the same cleanup as --email/--phone.
        foreach ($schools as $school) {
            foreach ($plan[$school->id]['users'] as $u) {
                if (! empty($u->email)) {
                    $emails[] = strtolower((string) $u->email);
                }
                if (! empty($u->mobile_no)) {
                    $phones[] = (string) $u->mobile_no;
                }
            }
            if (Schema::hasTable('whatsapp_users') && Schema::hasColumn('whatsapp_users', 'school_id')) {
                foreach (DB::table('whatsapp_users')->where('school_id', $school->id)->pluck('phone') as $phone) {
                    $phones[] = (string) $phone;
                }
            }
        }
        $emails = array_values(array_unique($emails));
        $phones = array_values(array_unique(array_filter($phones)));

        foreach ($phones as $phone) {
            $forms = $this->phoneForms($phone);
            if ($forms === []) {
                $this->error("phone {$phone} is not a readable number — refusing.");

                return self::FAILURE;
            }

            $owners = DB::table('users')->whereIn('mobile_no', $forms)->get(['id', 'school_id', 'usergroup_id']);
            foreach ($owners as $owner) {
                if (in_array((int) $owner->usergroup_id, self::PLATFORM_USERGROUPS, true)) {
                    $this->error("phone {$phone} belongs to platform admin user {$owner->id} — refusing.");

                    return self::FAILURE;
                }
                if (! in_array((int) $owner->school_id, $ids, true)) {
                    $this->error("phone {$phone} belongs to user {$owner->id} at school {$owner->school_id}, outside the targeted schools — refusing.");

                    return self::FAILURE;
                }
            }
        }

        $this->printPlan($plan, $schools);

        $emailPlan = $this->emailInventory($emails);
        $this->printEmailPlan($emailPlan, $emails);

        $phonePlan = $this->phoneInventory($phones, $ids);
        $this->printPhonePlan($phonePlan, $phones);

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('Dry run — nothing was deleted. Re-run with --force to execute.');

            return self::SUCCESS;
        }

        foreach ($schools as $school) {
            $this->purgeSchool($school, $plan[$school->id]);
        }

        if ($emails) {
            DB::transaction(function () use ($emailPlan) {
                foreach ($emailPlan as $table => $meta) {
                    DB::table($table)->whereIn('id', $meta['ids'])->delete();
                }
            });
            $this->info('Early-access requests and invites for ' . implode(', ', $emails) . ' purged.');
        }

        if ($phones) {
            DB::transaction(function () use ($phonePlan) {
                foreach ($phonePlan as $table => $meta) {
                    DB::table($table)->whereIn('id', $meta['ids'])->delete();
                }
            });
            $this->info('Phone rows for ' . implode(', ', $phones) . ' purged.');
        }

        $this->newLine();
        $this->info('Done. E2E test schools purged.');

        return self::SUCCESS;
    }

    /**
     * Every dependent row for one school: user rows and school-scoped rows.
     *
     * @return array{users: array, tables: array<string, array{ids: array, count: int}>}
     */
    private function inventoryFor(\App\Models\School $school): array
    {
        // Query builder, not Eloquent: soft-deleted users still hold the
        // users.school_id FK and must be inventoried and removed too.
        $users = DB::table('users')->where('school_id', $school->id)
            ->orderBy('id')
            ->get(['id', 'email', 'name', 'mobile_no', 'usergroup_id', 'status']);

        $userIds = $users->pluck('id')->all();

        $tables = [];
        foreach (self::SCHOOL_SCOPED as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'school_id')) {
                continue;
            }
            if (! Schema::hasColumn($table, 'id')) {
                // e.g. student_id_sequences (keyed by school_id only).
                $count = DB::table($table)->where('school_id', $school->id)->count();
                if ($count) {
                    $tables[$table] = ['ids' => [], 'count' => $count];
                }
                continue;
            }
            $ids = DB::table($table)->where('school_id', $school->id)->orderBy('id')->pluck('id')->all();
            if ($ids) {
                $tables[$table] = ['ids' => $ids, 'count' => count($ids)];
            }
        }
        foreach (self::USER_SCOPED as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }
            $ids = DB::table($table)->whereIn('user_id', $userIds)->orderBy('id')->pluck('id')->all();
            if ($ids) {
                $tables[$table] = ['ids' => $ids, 'count' => count($ids)];
            }
        }

        return ['users' => $users->all(), 'tables' => $tables];
    }

    /**
     * @param  list<string>  $emails  lower-cased
     * @return array<string, array{ids: array, count: int}>
     */
    private function emailInventory(array $emails): array
    {
        $tables = [];
        if (! $emails) {
            return $tables;
        }
        foreach (self::EMAIL_SCOPED as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'email')) {
                continue;
            }
            $ids = DB::table($table)->whereIn(DB::raw('lower(email)'), $emails)->orderBy('id')->pluck('id')->all();
            if ($ids) {
                $tables[$table] = ['ids' => $ids, 'count' => count($ids)];
            }
        }

        return $tables;
    }

    private function printEmailPlan(array $emailPlan, array $emails): void
    {
        if (! $emails) {
            return;
        }
        $this->line('');
        $this->info('emails ' . implode(', ', $emails) . ($this->option('force') ? ' — PURGING' : ' — would purge'));
        if (! $emailPlan) {
            $this->line('    (no early-access requests or invites)');
        }
        foreach ($emailPlan as $table => $meta) {
            $this->line("    {$table}: {$meta['count']} row(s) [ids " . implode(',', $meta['ids']) . ']');
        }
    }

    /**
     * Every exact spelling of one full number: as given, digits, +digits, and
     * the trunk-0 / 256 pair. A shorter suffix is never included, so freeing a
     * number cannot touch a different one that happens to share its tail.
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
     * Rows in the phone-keyed tables for these numbers. A row that belongs to
     * another school is left alone; a row with no school id is the person's
     * own (early access, delivery log) and is removed.
     *
     * @param  list<string>  $phones
     * @param  list<int>  $allowedSchoolIds
     * @return array<string, array{ids: array, count: int}>
     */
    private function phoneInventory(array $phones, array $allowedSchoolIds): array
    {
        $plan = [];
        if (! $phones) {
            return $plan;
        }

        $allForms = [];
        foreach ($phones as $phone) {
            foreach ($this->phoneForms($phone) as $form) {
                $allForms[] = $form;
            }
        }
        $allForms = array_values(array_unique($allForms));

        foreach (self::PHONE_SCOPED as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $query = DB::table($table)->whereIn($column, $allForms);

            if (Schema::hasColumn($table, 'school_id')) {
                $query->where(function ($q) use ($allowedSchoolIds) {
                    $q->whereNull('school_id')->orWhereIn('school_id', $allowedSchoolIds);
                });
            }

            $ids = $query->orderBy('id')->pluck('id')->all();
            if ($ids) {
                $plan[$table] = ['ids' => $ids, 'count' => count($ids)];
            }
        }

        return $plan;
    }

    /**
     * @param  array<string, array{ids: array, count: int}>  $phonePlan
     * @param  list<string>  $phones
     */
    private function printPhonePlan(array $phonePlan, array $phones): void
    {
        if (! $phones) {
            return;
        }

        $this->line('');
        $this->info('phones ' . implode(', ', $phones) . ($this->option('force') ? ' — PURGING' : ' — would purge'));
        if (! $phonePlan) {
            $this->line('    (no phone rows)');
        }
        foreach ($phonePlan as $table => $meta) {
            $ids = implode(',', $meta['ids']);
            $shown = strlen($ids) > 300 ? substr($ids, 0, 300) . '…' : $ids;
            $this->line("    {$table}: {$meta['count']} row(s) [ids {$shown}]");
        }
    }

    private function printPlan(array $plan, array $schools): void
    {
        foreach ($schools as $school) {
            $this->line('');
            $this->info("school {$school->id} — {$school->name} (slug {$school->slug})" . ($this->option('force') ? ' — PURGING' : ' — would purge'));
            $this->line('  users:');
            foreach ($plan[$school->id]['users'] as $u) {
                $this->line("    user id={$u->id} group={$u->usergroup_id} status={$u->status} <{$u->email}>");
            }
            $this->line('  dependent tables:');
            foreach ($plan[$school->id]['tables'] as $table => $meta) {
                $ids = implode(',', $meta['ids']);
                $shown = strlen($ids) > 300 ? substr($ids, 0, 300) . '…' : $ids;
                $this->line("    {$table}: {$meta['count']} row(s) [ids {$shown}]");
            }
        }
    }

    private function purgeSchool(\App\Models\School $school, array $inventory): void
    {
        $userIds = array_map(fn ($u) => (int) $u->id, $inventory['users']);

        DB::transaction(function () use ($school, $inventory, $userIds) {
            foreach ($inventory['tables'] as $table => $meta) {
                if (in_array($table, self::USER_SCOPED, true)) {
                    DB::table($table)->whereIn('user_id', $userIds)->delete();
                    continue;
                }
                DB::table($table)->where('school_id', $school->id)->delete();
            }

            if ($userIds) {
                DB::table('users')->whereIn('id', $userIds)->delete();
            }

            DB::table('schools')->where('id', $school->id)->delete();
        });

        $this->info("school {$school->id} ({$school->name}) purged.");
    }
}
