<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
                    'school'
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

            $controls = ["SUBJECT", "OUT OF", "MOT", "EOT", "AVG", "AGG", "REMARK", "TEACHER"];

            return view("admin.marks.student", compact("subjects", "learner", "controls"));
    }
    
}
