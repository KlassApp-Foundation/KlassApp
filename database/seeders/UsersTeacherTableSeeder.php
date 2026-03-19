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
        // $schools = School::where('status',1)->get();
        // foreach ($schools as $school) 
        // {
        //     $academic_year = AcademicYear::where([['school_id',$school->id],['status',1]])->first();
        //     factory(App\Models\User::class, 1)->create([
        //         'school_id'    => $school->id,
        //         'usergroup_id' => 5
        //     ])->each(function($principal) use($academic_year)
        //     {
        //         factory(\App\Models\Userprofile::class, 1)->create([
        //             'school_id'     =>  $principal->school_id,
        //             'user_id'       =>  $principal->id,
        //             'usergroup_id'  =>  $principal->usergroup_id,
        //             'address'       => 'Madurai,Tamilnadu,India',
        //             'pincode'       => '625001',
        //             'date_of_birth' =>  Carbon::now()->subYears(rand(35, 45)),
        //         ]);

        //         factory(\App\Models\TeacherProfile::class)->create([
        //             'school_id'         =>  $principal->school_id,
        //             'academic_year_id'  =>  $academic_year->id,
        //             'user_id'           =>  $principal->id,
        //             'status'            =>  1,
        //             'designation'       =>  'principal',
        //             'specialization'    =>  $uniSubjects[array_rand($uniSubjects, 1)]
        //         ]);

        //         factory(\App\Models\LibraryCard::class)->create([
        //             'school_id' => $principal->school_id,
        //             'user_id'   => $principal->id,
        //         ]);

        //         factory(\App\Models\RoleUser::class)->create([
        //             'role_id'   => 3,
        //             'user_id'   => $principal->id,
        //         ]);
        //     });

        //     $standards = Standard::where('school_id',$school->id)->get();
        //     $sections = Section::where('school_id',$school->id)->get();
        //     $teacherCount = $standards->count() * $sections->count();

        //     $uniSubjects = Subject::where([['school_id',$school->id],['academic_year_id',$academic_year->id]])->where('status', 1)->pluck('name')->unique()->flatten()->toArray();

        //     $teachers =  factory(App\Models\User::class, $teacherCount)->create([
        //         'school_id'    => $school->id,
        //         'usergroup_id' => 5
        //     ]);

        //     foreach ($teachers as $teacher) 
        //     {
        //         factory(\App\Models\Userprofile::class, 1)->create([
        //             'school_id'     => $teacher->school_id,
        //             'user_id'       => $teacher->id,
        //             'usergroup_id'  => $teacher->usergroup_id,
        //             'address'       => 'Madurai,Tamilnadu,India',
        //             'pincode'       => '625001',
        //             'date_of_birth' =>  Carbon::now()->subYears(rand(35, 45)),
        //         ]);

        //         factory(\App\Models\TeacherProfile::class)->create([
        //             'school_id'         =>  $teacher->school_id,
        //             'academic_year_id'  =>  $academic_year->id,
        //             'user_id'           =>  $teacher->id,
        //             'status'            =>  1,
        //             'designation'       => 'teacher',
        //             'specialization'    => $uniSubjects[array_rand($uniSubjects, 1)]
        //         ]);

        //         factory(\App\Models\LibraryCard::class)->create([
        //             'school_id' => $teacher->school_id,
        //             'user_id'   => $teacher->id,
        //         ]);
        //     }

        //     $staffs =  factory(App\Models\User::class, 15)->create([
        //         'school_id'    => $school->id,
        //         'usergroup_id' => 5
        //     ]);

        //     foreach ($staffs as $staff) 
        //     {
        //         factory(\App\Models\Userprofile::class, 1)->create([
        //             'school_id'     => $staff->school_id,
        //             'user_id'       => $staff->id,
        //             'usergroup_id'  => $staff->usergroup_id,
        //             'address'       => 'Madurai,Tamilnadu,India',
        //             'pincode'       => '625002',
        //             'date_of_birth' =>  Carbon::now()->subYears(rand(35, 45)),
        //         ]);

        //         factory(\App\Models\TeacherProfile::class)->create([
        //             'school_id'         =>  $staff->school_id,
        //             'academic_year_id'  =>  $academic_year->id,
        //             'user_id'           =>  $staff->id,
        //             'status'            =>  1,
        //         ]);

        //         factory(\App\Models\LibraryCard::class)->create([
        //             'school_id' => $staff->school_id,
        //             'user_id'   => $staff->id,
        //         ]);
        //     }
        // }
    }
}