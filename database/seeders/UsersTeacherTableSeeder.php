<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Helpers\SiteHelper;
use App\Models\Standard;
use App\Models\Section;
use App\Models\Subject;
use App\Models\School;
use Carbon\Carbon;

class UsersTeacherTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seed one teacher per active school
        $schools = School::where('status', 1)->get();
        
        foreach ($schools as $school) {
            $academic_year = AcademicYear::where('school_id', $school->id)->where('status', 1)->first();
            if (!$academic_year) {
                $academic_year = AcademicYear::where('school_id', $school->id)->orderByDesc('id')->first();
            }
            
            // Create one teacher user per school
            $host = preg_replace('/[^a-z0-9]/', '', strtolower($school->name));
            $teacher_email = 'teacher_'.$host.'@'.$host.'.edu';
            
            $user = \App\Models\User::where('email', $teacher_email)->first();
            if (!$user) {
                $user_id = DB::table('users')->insertGetId([
                    'usergroup_id' => 5,
                    'school_id' => $school->id,
                    'name' => 'Teacher ' . $school->name,
                    'email' => $teacher_email,
                    'mobile_no' => '+256771'.str_pad((string) $school->id, 6, '0', STR_PAD_LEFT),
                    'password' => bcrypt('password'),
                    'status' => 'active',
                    'email_verified' => 1,
                    'mobile_verified' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $user = \App\Models\User::find($user_id);
            }
            
            // Ensure userprofile
            $profile = \App\Models\Userprofile::where('user_id', $user->id)->first();
            if (!$profile) {
                DB::table('userprofiles')->insert([
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'usergroup_id' => 5,
                    'firstname' => 'Teacher',
                    'lastname' => $school->name,
                    'profession' => 'teacher',
                    'address' => $school->name,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Ensure teacher profile
            $tprofile = \App\Models\TeacherProfile::where('user_id', $user->id)->first();
            if (!$tprofile && $academic_year) {
                DB::table('teacherprofile')->insert([
                    'school_id' => $school->id,
                    'academic_year_id' => $academic_year->id,
                    'user_id' => $user->id,
                    'designation' => 'teacher',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            echo "Teacher seeded for school: " . $school->name . " (id=" . $user->id . ", email=" . $user->email . ")\n";
        }
    }
}