<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\RoleUser;

class RoleUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch teachers (assuming usergroup_id = 5 is teachers)
        $teachers = User::where('usergroup_id', 5)->get();

        if ($teachers->isEmpty()) {
            $this->command->info('No teachers found (usergroup_id = 5). Skipping role assignment.');
            return;
        }

        // Fetch role IDs by name (safer than hard-coded IDs)
        $roles = Role::whereIn('name', [
            'leave_applier',
            'leave_checker',
            'principal',
        ])->pluck('id', 'name');

        if ($roles->count() < 2) {
            $this->command->warn('Missing required roles (leave_applier / leave_checker). Skipping.');
            return;
        }

        $leaveApplierId = $roles['leave_applier'] ?? null;
        $leaveCheckerId = $roles['leave_checker'] ?? null;

        foreach ($teachers as $teacher) {
            // Get designation safely (assuming getTeacherDetails() returns array)
            $designation = $teacher->getTeacherDetails()['designation'] ?? null;

            $roleId = match (strtolower($designation ?? '')) {
                'principal', 'headteacher', 'vice_principal', 'deputy_principal', 'head_of_department' => $leaveCheckerId,
                default => $leaveApplierId,
            };

            if ($roleId === null) {
                continue; // Skip if no valid role found
            }

            RoleUser::factory()->create([
                'user_id' => $teacher->id,
                'role_id' => $roleId,
            ]);
        }

        $this->command->info("Assigned roles to {$teachers->count()} teachers.");
    }
}