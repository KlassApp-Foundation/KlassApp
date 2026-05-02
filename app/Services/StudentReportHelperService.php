<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\Academics\Exam;
use App\Models\FeesCategories;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;

class StudentReportHelperService
{

public function learner($schoolId, $learner, $exam){
    // dd($exam);
    return $learner = User::with([
                    'marks.subject',
                    'marks.remark',
                    'marks.exam',
                    'marks.student',
                    'marks.teacher',
                    'marks.school', 
                    'school',
                    'userprofile',
                   ])
                   ->whereHas('marks.exam', function ($query) use ($schoolId, $exam) {
                       $query->forSchool($schoolId)
                       ->where("section_id", $exam->section_id)
                       ->where("academic_year_id", $exam->academic_year_id)
                       ->where("academic_term_id", $exam->academic_term_id)
                       ->where("status", $exam->status);
                   })
                   ->where('id', $learner->id)
                   ->where('usergroup_id', 6)
                   ->get();
}

// subjects
public function subjects($schoolId, $section, $learner, $exam){
    return  $subjects = Subject::where("school_id", $schoolId)
                                 ->where("section_id", $section)
                                 ->with("mark", function($q) use($learner, $exam){
                                    $q->where("student_id", $learner->first()->id)
                                    ->with("exam", function ($q2) use($exam){
                                        $q2->where("section_id", $exam->school_id)
                                          ->where("academic_term_id", $exam->academic_term_id)
                                          ->where("academic_year_id", $exam->academic_year_id);
                                    });
                                 })
                                 ->get();
}

// fees
public function fees($admin, $section){
    return  FeesCategories::where("school_id", $admin->school_id)
                                    ->where(function ($query) use($section){
                                        $query->whereNull("section_id");
                                              if(!is_null($section)){
                                                $query->orWhere("section_id", $section);
                                              }
                                    }) 
                                ->sum("amount");
}

public function totalStudentsInClass($schoolId, $section){
    return  User::with(["studentAcademic.standardLink", "marks"])
                     ->where("school_id", $schoolId)
                     ->where("usergroup_id", 6)
                     ->whereHas("studentAcademic", function ($q) use($section){
                        $q->whereHas("standardLink", function ($q2) use($section){
                            $q2->where("section_id", $section);
                        });
                     });
}

public function totalMarks($learners){
    $learners = $learners->map(function ($learner){
            $learner->total = $learner->marks->sum("marks");
            return $learner;
        });
return $learners;
}
  public function position($learners ){
     $position = 1;
     $prevtotal = null;
    return $learners->map(function ($student, $index) use(&$position, &$prevtotal){
            if($prevtotal !== null && $student->total < $prevtotal){
                $position = $index + 1;
            }
            $student->position = $position;
            $prevtotal = $student->total;
            return $student;
        });
  }

  public function exam ($schoolId, $exam){
    return Exam::where("school_id", $schoolId)
                       ->where("section_id", $exam->section_id)
                       ->where("academic_year_id", $exam->academic_year_id)
                       ->where("academic_term_id", $exam->academic_term_id)
                    //    ->where("status", $exam->status) //========to be added
                       ->get();
                       dd($exams);
  }
}

