<?php

namespace Database\Seeders\result;

use App\Helpers\SiteHelper;
use App\Models\Academics\Classes;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Standard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ExamTableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where("status", 1)->get();
        $standards = Standard::where("status", 1)->get();
        
        $subjects = Subject::where("status", 1)->get();
        $types = ExamType::all();
        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        if ($schools->isEmpty()) {
            $this->command->warn('No active schools. Skipping exams.');
            return;
        }

        // $examTemplates = [
        //     [ 'offset_days' => 30, 'for_levels' => range(1, 7)],   // P1–P7 order
        //     [ 'offset_days' => 60, 'for_levels' => range(1, 7)],
        //     [ 'offset_days' => 90, 'for_levels' => range(1, 10)],

        //     [ 'offset_days' => 45,  'for_levels' => range(8, 16)],
        //     [  'offset_days' => 100, 'for_levels' => range(8, 16)],
        //     [  'offset_days' => 180, 'for_levels' => [14,15,16]], // S4,S5,S6
        // ];

           foreach ($subjects as $subject){
            //  foreach ($examTemplates as $exam){
               foreach ($types as $type){
                $tr = User::where("usergroup_id", 5)->where("school_id", $subject->school_id)->first();
                 Exam::create([
                    // ...$exam,
                    "school_id"=>$subject->school_id,
                    "standard_id" => $subject->standard_id,
                    "subject_id" => $subject->id,
                    "academic_year_id" => $subject->academic_year_id,
                    "teacher_id" => $tr->id,
                    "exam_type_id" => $type->id,
                    "scheduled_at" => now(),
                    "term" =>  strval(rand(1, 3)),
                    ]);
               }
            //  }
           }
           $this->command->info("Exams seeded successfully!");
    }
    
}