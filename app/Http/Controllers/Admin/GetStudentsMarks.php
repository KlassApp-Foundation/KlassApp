<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetStudentsMarks extends Controller
{
    //
    
    public function GetStudentMarks(User $learner, $class){
        $admin = Auth::user();
        $schoolId = $admin->school_id;

         $learner = User::with([
                    'marks.subject',
                    'marks.remark',
                    'marks.exam',
                    'marks.student',
                    'marks.teacher',
                    'marks.school',
                    'school',
                    'userprofile',
                   ])
                   ->whereHas('marks.exam', function ($query) use ($schoolId) {
                       $query->forSchool($schoolId);
                   })
                   ->where('id', $learner->id)
                   ->where('usergroup_id', 6)
                   ->first();
            $subjects = Subject::where("school_id", $schoolId)
                        ->where("school_id", $schoolId)
                        ->where("standard_id", $class)
                        ->get();

            $term =  Exam::where("standard_id", $class)->pluck("term")->first();
            $class_name = Standard::find($class)->name;
            $controls = ["SUBJECT", "OUT OF", "MOT", "EOT", "AVG", "AGG", "REMARK", "TEACHER"];
            $grading_system = [
                "0-39"=>"F9",
                 "40-44"=>"P8",
                 "45-49"=>"P7",
                 "50-59"=>"C5",
                 "60-64"=>"C4",
                 "65-74"=>"C3",
                 "75-84"=>"D2",
                 "85-100"=>"D1"
                 ];

            return view("admin.marks.student", compact("subjects", "learner", "controls", "term", "class_name", "grading_system"));
    }
    
}
