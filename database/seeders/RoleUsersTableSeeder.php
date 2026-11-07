<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Depends on teacher users and the leave roles.
 * A teacher is a leave applier. A principal designation is a leave checker.
 */
class RoleUsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = User::query()->where('usergroup_id', 5)->get();
        if ($teachers->isEmpty()) {
            $this->command?->info('No teachers found. Skipping role assignment.');

            return;
        }

        $roles = DB::table('roles')->whereIn('name', ['leave_applier', 'leave_checker'])->pluck('id', 'name');
        $applier = $roles['leave_applier'] ?? null;
        $checker = $roles['leave_checker'] ?? null;
        if ($applier === null || $checker === null) {
            $this->command?->warn('Missing leave roles. Skipping role assignment.');

            return;
        }

        foreach ($teachers as $teacher) {
            $designation = (string) DB::table('teacherprofile')->where('user_id', $teacher->id)->value('designation');
            $roleId = in_array(strtolower($designation), ['principal', 'headteacher', 'vice_principal', 'deputy_principal', 'head_of_department'], true)
                ? $checker
                : $applier;

            ReferenceUpsert::sync('role_user', [
                'user_id' => $teacher->id,
                'role_id' => $roleId,
            ], [
                'user_type' => User::class,
            ]);
        }
    }
}
