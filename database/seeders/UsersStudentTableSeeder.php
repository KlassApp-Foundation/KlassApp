<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\StandardLink;
use App\Helpers\SiteHelper;
use App\Models\School;
use Carbon\Carbon;

class UsersStudentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        $schools = School::where('status',1)->get();
        foreach ($schools as $school) 
        {

            $academic_year = SiteHelper::getAcademicyear($school->id);
            $classRooms= StandardLink::where([['school_id',$school->id],['academic_year_id',$academic_year->id]])->get();
            foreach ($classRooms as $classRoom) 
            {
                $studentCountPerSection = rand(10,12);

                $students = factory(App\Models\User::class, $studentCountPerSection)->create([
                    'school_id'    =>   $classRoom->school_id,
                    'usergroup_id' =>   6
                ]);

                foreach ($students as $student) 
                {
                    factory(\App\Models\Userprofile::class, 1)->create([
                        'school_id'     =>  $student->school_id,
                        'user_id'       =>  $student->id,
                        'usergroup_id'  =>  $student->usergroup_id
                    ]);

                    $father = factory(App\Models\User::class)->create([
                        'school_id'     => $student->school_id,
                        'usergroup_id'  => 7
                    ]);

                    factory(\App\Models\Userprofile::class)->create([
                        'school_id'     =>  $student->school_id,
                        'user_id'       =>  $father->id,
                        'usergroup_id'  =>  $father->usergroup_id,
                        'address'       =>  'Madurai,Tamilnadu,India',
                        'pincode'       =>  '625001',
                        'gender'        =>  'male',
                        'date_of_birth' =>  Carbon::now()->subYears(rand(25, 45))
                    ]);

                    factory(App\Models\StudentParentLink::class)->create([
                        'school_id'  => $student->school_id,
                        'parent_id'  => $father->id,
                        'student_id' => $student->id
                    ]);

                    factory(App\Models\ParentProfile::class)->create([
                        'school_id'  => $father->school_id,
                        'user_id'    => $father->id
                    ]);

                    $mother = factory(App\Models\User::class)->create([
                        'school_id'     => $student->school_id,
                        'usergroup_id'  => 7
                    ]);

                    factory(\App\Models\Userprofile::class)->create([
                        'school_id'     =>  $student->school_id,
                        'user_id'       =>  $mother->id,
                        'usergroup_id'  =>  $mother->usergroup_id,
                        'address'       =>  'Madurai,Tamilnadu,India',
                        'pincode'       =>  '625001',
                        'gender'        =>  'female',
                        'date_of_birth' =>  Carbon::now()->subYears(rand(25, 40)),
                    ]);

                    factory(App\Models\StudentParentLink::class)->create([
                        'school_id'  => $student->school_id,
                        'parent_id'  => $mother->id,
                        'student_id' => $student->id
                    ]);

                    factory(App\Models\ParentProfile::class)->create([
                        'school_id'  => $mother->school_id,
                        'user_id'    => $mother->id
                    ]);

                    factory(\App\Models\LibraryCard::class)->create([
                        'school_id'  => $student->school_id,
                        'user_id'  => $student->id,
                    ]);

                    factory(\App\Models\StudentAcademic::class, 1)->create([
                        'school_id'         =>  $student->school_id,
                        'user_id'           =>  $student->id,
                        'standardLink_id'   =>  $classRoom->id,
                        'academic_year_id'  =>  $classRoom->academic_year_id
                    ]);
                }
            }
        }
    }
}