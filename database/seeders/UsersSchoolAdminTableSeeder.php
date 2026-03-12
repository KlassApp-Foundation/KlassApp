<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\TeacherProfile;

class UsersSchoolAdminTableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where('status', 1)->get();

        if ($schools->isEmpty()) {
            $this->command->warn('No active schools found. Skipping admin/staff seeding.');
            return;
        }

        // Uganda country/state/city lookup (once)
        $uganda = DB::table('countries')->where('name', 'Uganda')->first();
        if (! $uganda) {
            $this->command->error('Uganda country not found. Skipping.');
            return;
        }

        $centralRegion = DB::table('states')->where('country_id', $uganda->id)
            ->where('name', 'Central Region')->first();

        $kampala = DB::table('cities')->where('country_id', $uganda->id)
            ->where('name', 'Kampala')->first();

        $stateId = $centralRegion ? $centralRegion->id : null;
        $cityId  = $kampala ? $kampala->id : null;

        foreach ($schools as $school) {
            $academicYear = AcademicYear::where([
                'school_id' => $school->id,
                'status'    => 1,
            ])->first();

            if (! $academicYear) {
                $this->command->info("Skipping school {$school->name} — no active academic year.");
                continue;
            }

            // Helper to create user + profile + teacher profile safely
            $createStaff = function ($roleName, $emailPrefix, $designation) use ($school, $academicYear, $uganda, $stateId, $cityId) {
                $email = $emailPrefix . '@' . Str::slug($school->name, '') . '.sch.ug';

                // Safe user creation
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'school_id'    => $school->id,
                        'name'         => ucfirst($roleName) . ' ' . $school->name,
                        'mobile_no'    => '+256 77' . rand(1000000, 9999999),
                        'usergroup_id' => $this->getUserGroupIdForRole($roleName), // map role to group ID
                        'password'     => bcrypt('password123'), // change in real life!
                    ]
                );

                // Safe profile
                Userprofile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'school_id'     => $school->id,
                        'usergroup_id'  => $user->usergroup_id,
                        'firstname'     => ucfirst($roleName),
                        'lastname'      => 'Staff',
                        'profession'    => $designation,
                        'address'       => 'School Office, ' . $school->name . ', Kampala',
                        'country_id'    => $uganda->id,
                        'state_id'      => $stateId,
                        'city_id'       => $cityId,
                        'pincode'       => null,
                    ]
                );

                // Safe teacher profile (assuming all these roles use it)
                TeacherProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'school_id'         => $school->id,
                        'academic_year_id'  => $academicYear->id,
                        'designation'       => $designation,
                        'status'            => 1,
                    ]
                );

                return $user;
            };

            // Create each role safely
            $createStaff('admin', 'admin', 'Principal / Administrator');
            $createStaff('librarian', 'librarian', 'Librarian');
            $createStaff('receptionist', 'reception', 'Receptionist / Secretary');
            $createStaff('bursar', 'bursar', 'Bursar / Accountant');
            $createStaff('store_keeper', 'store', 'Store Keeper / Procurement');
        }

        $this->command->info('Seeded admin & support staff for ' . $schools->count() . ' schools.');
    }

    /**
     * Map role name to usergroup_id (adjust IDs based on your DB)
     */
    private function getUserGroupIdForRole(string $role): int
    {
        return match ($role) {
            'admin'        => 3,
            'librarian'    => 8,
            'receptionist' => 10,
            'bursar'       => 11,
            'store_keeper' => 12,
            default        => 3, // fallback to admin
        };
    }
}