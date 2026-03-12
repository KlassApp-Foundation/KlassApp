<?php

namespace Database\Seeders\result;

use App\Models\Academics\Comments;
use App\Models\Academics\Exam;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
         $standards=Standard::where("school_id", 4)->get();
        $subjects=Subject::all();
        $schools=School::where("country_id", 1)->get();
        $teachers=User::where("school_id", 4)->get();
        $academicYears=AcademicYear::where("school_id", 4)->get();
        $comments=Comments::all();
         $exams=["Test Comment1", "Test Comment2", "Test Comment3", "Test Comment4", "Test Comment5"];

         foreach ($standards as $standard){
            foreach ($subjects as $subject){
                 foreach ($exams as $exam){
                      foreach ($schools as $school){
                         foreach ($teachers as $teacher){
                           foreach ($academicYears as $academicYear){
                             DB::table("marks")->insert([
                                "name"=>$exam,
                                "standard_id"=>$standard->id, 
                                "school_id"=>$school->id,
                                "academic_year_id"=> $academicYear->id,
                                "subject_id"=>$subject->id,
                                  "teacher_id"=>$teacher->id,         
                            ]);
                           }
            }
            }
            }
            }
        }
    }
}
