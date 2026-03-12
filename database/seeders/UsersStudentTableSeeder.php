<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\TeacherProfile;
use App\Models\StudentParentLink;
use App\Models\ParentProfile;
use App\Models\LibraryCard;
use App\Models\StudentAcademic;
use Carbon\Carbon;

class UsersStudentTableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where('status', 1)->get();

        if ($schools->isEmpty()) {
            $this->command->warn('No active schools found. Skipping student seeding.');
            return;
        }

        $faker = \Faker\Factory::create('en_UG'); // Uganda locale for names/addresses

        foreach ($schools as $school) {
            $academicYear = AcademicYear::where([
                'school_id' => $school->id,
                'status'    => 1,
            ])->first();

            if (! $academicYear) {
                $this->command->info("Skipping school {$school->name} — no active academic year.");
                continue;
            }

            $classRooms = StandardLink::where([
                'school_id'        => $school->id,
                'academic_year_id' => $academicYear->id,
            ])->get();

            if ($classRooms->isEmpty()) {
                $this->command->info("Skipping school {$school->name} — no class rooms.");
                continue;
            }

            $totalStudents = 0;

            foreach ($classRooms as $classRoom) {
                $studentCount = rand(10, 12); // 10–12 students per class

                for ($i = 0; $i < $studentCount; $i++) {
                    // Student user
                    $student = User::firstOrCreate(
                        [
                            'email' => $faker->unique()->safeEmail,
                        ],
                        [
                            'school_id'    => $school->id,
                            'name'         => $faker->name('male'), // or random gender
                            'mobile_no'    => '+256 7' . $faker->numerify('#######'),
                            'usergroup_id' => 6, // students
                            'password'     => bcrypt('student123'), // change later
                        ]
                    );

                    // Student profile
                    Userprofile::firstOrCreate(
                        ['user_id' => $student->id],
                        [
                            'school_id'     => $school->id,
                            'usergroup_id'  => 6,
                            'firstname'     => $faker->firstName,
                            'lastname'      => $faker->lastName,
                            'profession'    => 'Student',
                            'address'       => $faker->streetAddress . ', Kampala, Uganda',
                            'country_id'    => DB::table('countries')->where('name', 'Uganda')->value('id'),
                            'city_id'       => DB::table('cities')->where('name', 'Kampala')->value('id'),
                            'state_id'      => DB::table('states')->where('name', 'Central Region')->value('id'),
                            'gender'        => $faker->randomElement(['male', 'female']),
                            'date_of_birth' => $faker->dateTimeBetween('-18 years', '-5 years'),
                        ]
                    );

                    // Link to class room
                    StudentAcademic::firstOrCreate(
                        [
                            'user_id'          => $student->id,
                            'school_id'        => $school->id,
                        ],
                        [
                            'standardLink_id'   => $classRoom->id,
                            'academic_year_id'  => $academicYear->id,
                            // Add roll_no, status, etc. if needed
                        ]
                    );

                    // Father
                    $father = User::factory()->create([
                        'school_id'    => $school->id,
                        'usergroup_id' => 7, // parents
                        'name'         => $faker->name('male'),
                        'email'        => $faker->unique()->safeEmail,
                        'mobile_no'    => '+256 7' . $faker->numerify('#######'),
                    ]);

                    Userprofile::factory()->create([
                        'school_id'     => $school->id,
                        'user_id'       => $father->id,
                        'usergroup_id'  => 7,
                        'firstname'     => $faker->firstNameMale,
                        'lastname'      => $faker->lastName,
                        'profession'    => 'Parent',
                        'address'       => $faker->streetAddress . ', Kampala, Uganda',
                        'gender'        => 'male',
                        'date_of_birth' => Carbon::now()->subYears(rand(30, 55)),
                    ]);

                    StudentParentLink::firstOrCreate([
                        'school_id'  => $school->id,
                        'parent_id'  => $father->id,
                        'student_id' => $student->id,
                    ]);

                    ParentProfile::firstOrCreate([
                        'school_id' => $school->id,
                        'user_id'   => $father->id,
                    ]);

                    // Mother (similar)
                    $mother = User::factory()->create([
                        'school_id'    => $school->id,
                        'usergroup_id' => 7,
                        'name'         => $faker->name('female'),
                        'email'        => $faker->unique()->safeEmail,
                        'mobile_no'    => '+256 7' . $faker->numerify('#######'),
                    ]);

                    Userprofile::factory()->create([
                        'school_id'     => $school->id,
                        'user_id'       => $mother->id,
                        'usergroup_id'  => 7,
                        'firstname'     => $faker->firstNameFemale,
                        'lastname'      => $faker->lastName,
                        'profession'    => 'Parent',
                        'address'       => $faker->streetAddress . ', Kampala, Uganda',
                        'gender'        => 'female',
                        'date_of_birth' => Carbon::now()->subYears(rand(28, 50)),
                    ]);

                    StudentParentLink::firstOrCreate([
                        'school_id'  => $school->id,
                        'parent_id'  => $mother->id,
                        'student_id' => $student->id,
                    ]);

                    ParentProfile::firstOrCreate([
                        'school_id' => $school->id,
                        'user_id'   => $mother->id,
                    ]);

                    // Library card
                    LibraryCard::firstOrCreate([
                        'school_id' => $school->id,
                        'user_id'   => $student->id,
                    ]);

                    $totalStudents++;
                }
            }

            $this->command->info("Seeded {$totalStudents} students + parents for school: {$school->name}");
        }
    }
}