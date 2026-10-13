<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\User;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Points the one Demo Junior school admin at its durable login.
 *
 * Refuses every school that is not flagged is_demo, and refuses every demo
 * school whose name is not Demo Junior School. Demo Senior and every other
 * account are left untouched. The school itself is never renamed.
 *
 * The password is an argument or a hidden prompt. It has no default, is never
 * read from the environment, and is never printed, logged, stored in a file,
 * or sent. Output is a count only.
 *
 *     php artisan demo:set-admin-login 38 --password=...
 *     php artisan demo:set-admin-login 38
 */
class DemoSetAdminLoginCommand extends Command
{
    protected $signature = 'demo:set-admin-login
        {school : Demo Junior School id}
        {--password= : New password (no default; hidden prompt when omitted in a terminal)}
        {--display-only : Restore the Demo Admin name only. Does not change the password.}';

    protected $description = 'Set the Demo Junior school admin email and password (counts only; nothing logged or sent)';

    public function handle(): int
    {
        $id = (int) $this->argument('school');
        $school = School::find($id);

        if (! $school) {
            $this->error("School {$id}: not found — refusing.");

            return self::FAILURE;
        }

        if ((int) $school->is_demo !== 1) {
            $this->error("School {$id} ({$school->name}) is not a demo school — refusing.");

            return self::FAILURE;
        }

        if ($school->name !== 'Demo Junior School') {
            $this->error("School {$id} ({$school->name}) is not Demo Junior School — refusing.");

            return self::FAILURE;
        }

        $displayOnly = (bool) $this->option('display-only');
        $password = (string) ($this->option('password') ?? '');

        if (! $displayOnly && $password === '' && $this->input->isInteractive() && ! app()->runningUnitTests()) {
            $password = (string) $this->secret('Password');
        }

        if (! $displayOnly && $password === '') {
            $this->error('Pass --password. There is no default and none is read from the environment.');

            return self::FAILURE;
        }

        if (! $displayOnly) {
            $validator = Validator::make(
                ['password' => $password],
                ['password' => 'required|string|min:8'],
                ['password.min' => __('userprofile.newpassword_min')],
            );

            if ($validator->fails()) {
                $this->error($validator->errors()->first('password'));

                return self::FAILURE;
            }
        }

        $admins = User::where('school_id', $school->id)
            ->where('usergroup_id', 3)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        if ($admins->count() !== 1) {
            $this->error('Refusing: Demo Junior School must have exactly one active school admin.');

            return self::FAILURE;
        }

        $admin = $admins->first();
        $email = DemoJuniorSchoolSeeder::ADMIN_EMAIL;
        $taken = User::where('email', $email)->where('id', '!=', $admin->id)->exists();

        if ($taken) {
            $this->error('Refusing: the admin email is already used by another account.');

            return self::FAILURE;
        }

        $schoolId = (int) $admin->school_id;
        $roleId = (int) $admin->usergroup_id;

        $displayName = DemoJuniorSchoolSeeder::ADMIN_NAME;
        DB::transaction(function () use ($admin, $email, $password, $schoolId, $roleId, $displayOnly, $displayName) {
            $fill = [
                'email' => $email,
                'name' => $displayName,
                'status' => 'active',
                'email_verified' => 1,
                'email_verified_at' => now(),
                'is_reset' => 0,
                'school_id' => $schoolId,
                'usergroup_id' => $roleId,
            ];
            if (! $displayOnly) {
                $fill['password'] = Hash::make($password);
            }
            $admin->forceFill($fill)->save();

            DB::table('userprofiles')->where('user_id', $admin->id)->update([
                'firstname' => 'Demo',
                'lastname' => 'Admin',
                'status' => 'active',
                'updated_at' => now(),
            ]);

            DB::table('authentications')->where('user_id', $admin->id)->update([
                'attempts' => 0,
                'locked_until' => null,
                'updated_at' => now(),
            ]);
        });

        $this->info("[{$school->id}] {$school->name} — 1 school admin updated");
        $this->comment('Counts only — the password was never printed, logged or sent.');

        return self::SUCCESS;
    }
}
