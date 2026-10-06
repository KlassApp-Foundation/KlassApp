<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Sets one shared password on the walkthrough accounts of demo schools.
 *
 * Targets: every ACTIVE account of the named schools for the roles below —
 * school admin, head teacher, teacher, parent, librarian, receptionist,
 * accountant, stock keeper and co-admin. The role list is an allowlist: a
 * credential operation must not quietly include a role that was never
 * intended. Students are never touched, and the inactive leftover admin is
 * left alone because only ACTIVE accounts are considered.
 *
 * The password is validated against the app's own password rules
 * (ChangePasswordRequest, RegisterRequest: at least 8 characters). It has no
 * default and is never read from the environment, never logged, never echoed
 * and never mailed — the command prints counts per role only.
 *
 * Every target account is left ready for a clean password login:
 *   email_verified = 1 (+ email_verified_at), userprofile active,
 *   is_reset = 0, failed-code lockout cleared
 *   (authentications.attempts = 0, locked_until = null).
 *
 * Safety rails (mirrors demo:refresh / demo:purge-schools):
 *   - every requested school must be flagged is_demo; if any id is missing
 *     or not a demo school the whole run is refused before any write;
 *   - one transaction per school; no outbound communication of any kind.
 *
 * Run on purpose only, demo schools only (never production):
 *     php artisan demo:set-password 38 39 --password=...
 */
class DemoSetPasswordCommand extends Command
{
    protected $signature = 'demo:set-password
        {school* : Demo school ids (each must be flagged is_demo)}
        {--password= : New password for every target account (required; no default)}';

    protected $description = 'Set one shared password on the active staff/teacher/parent accounts of demo schools (counts only; nothing logged or sent)';

    /**
     * Roles this command may target (allowlist). Site admins, students and
     * old students are deliberately excluded.
     */
    private const ALLOWED_USERGROUPS = [2, 3, 4, 5, 7, 8, 10, 11, 12];

    public function handle(): int
    {
        $ids = array_values(array_unique(array_map('intval', (array) $this->argument('school'))));

        if ($ids === []) {
            $this->error('Pass at least one demo school id.');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?? '');

        if ($password === '') {
            $this->error('Pass --password=<new password>. There is no default and none is read from the environment.');

            return self::FAILURE;
        }

        // The app's own password rule (ChangePasswordRequest, RegisterRequest):
        // at least 8 characters.
        $validator = Validator::make(
            ['password' => $password],
            ['password' => 'required|string|min:8'],
            ['password.min' => __('userprofile.newpassword_min')],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return self::FAILURE;
        }

        // Validate every school BEFORE any write: one non-demo id refuses the whole run.
        $schools = School::whereIn('id', $ids)->get()->keyBy('id');
        $refused = false;

        foreach ($ids as $id) {
            $school = $schools->get($id);

            if (! $school) {
                $this->error("School {$id}: not found — refusing.");
                $refused = true;

                continue;
            }

            if ((int) $school->is_demo !== 1) {
                $this->error("School {$id} ({$school->name}) is not a demo school — refusing.");
                $refused = true;
            }
        }

        if ($refused) {
            $this->error('Nothing changed. This command only runs on demo schools.');

            return self::FAILURE;
        }

        $roleNames = DB::table('usergroups')->pluck('name', 'id');
        $hash = Hash::make($password);

        foreach ($ids as $id) {
            $school = $schools->get($id);

            $targets = User::where('school_id', $id)
                ->where('status', 'active')
                ->whereIn('usergroup_id', self::ALLOWED_USERGROUPS)
                ->orderBy('id')
                ->get();

            DB::transaction(function () use ($targets, $hash) {
                foreach ($targets as $user) {
                    $user->forceFill([
                        'password' => $hash,
                        'email_verified' => 1,
                        'email_verified_at' => now(),
                        'is_reset' => 0,
                    ])->save();

                    DB::table('userprofiles')->where('user_id', $user->id)->update([
                        'status' => 'active',
                        'updated_at' => now(),
                    ]);

                    DB::table('authentications')->where('user_id', $user->id)->update([
                        'attempts' => 0,
                        'locked_until' => null,
                        'updated_at' => now(),
                    ]);
                }
            });

            $this->info("[{$school->id}] {$school->name} — {$targets->count()} accounts updated");

            foreach ($targets->groupBy('usergroup_id')->sortKeys() as $groupId => $users) {
                $name = $roleNames[$groupId] ?? "Usergroup {$groupId}";
                $this->line("  {$name}: {$users->count()}");
            }
        }

        $this->newLine();
        $this->comment('Counts only — the password was never printed, logged or sent.');

        return self::SUCCESS;
    }
}
