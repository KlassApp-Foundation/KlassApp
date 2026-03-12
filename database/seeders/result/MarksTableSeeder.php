<?php

namespace Database\Seeders\result;

use App\Models\Academics\Comments;
use App\Models\Academics\Exam;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $students=User::where("usergroup_id", 6)->get();
        $subjects=Subject::all();
        $exams=Exam::all();
        $schools=School::where("country_id", 1)->get();
        $teachers=User::where("school_id", 4)->get();
        $comments=Comments::all();

        foreach ($students as $student){
            foreach ($subjects as $subject){
                 foreach ($exams as $exam){
                      foreach ($schools as $school){
                         foreach ($teachers as $teacher){
                           foreach ($comments as $comment){
                             DB::table("marks")->insert([
                                "student_id"=>$student->id, 
                                "subject_id"=>$subject->id,
                                 "exam_id"=>$exam->id,
                                  "teacher_id"=>$teacher->id,
                                   "school_id"=>$school->id,
                                    "marks"=> rand(45, 95) ,
                                     "comment"=>$comment->comment
                            ]);
                           }
            }
            }
            }
            }
        }
        
    }
}
