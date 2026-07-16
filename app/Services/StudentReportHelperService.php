<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\FeesCategories;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;

class StudentReportHelperService
{

public function learner($schoolId, $user, $exam){
    // dd($exam);
    $scopedExamIds = \App\Models\Academics\Exam::where('school_id', $schoolId)
        ->where('section_id', $exam->section_id)
        ->where('academic_year_id', $exam->academic_year_id)
        ->where('academic_term_id', $exam->academic_term_id)
        ->pluck('id');

    $learner = User::with([
                    'marks' => function ($query) use ($scopedExamIds) {
                        $query->with(['subject', 'exam', 'student', 'teacher', 'school'])
                              ->whereIn('exam_id', $scopedExamIds);
                    },
                    'school',
                    'userprofile',
                   ])
                   ->whereHas('marks', function ($query) use ($scopedExamIds) {
                       $query->whereIn('exam_id', $scopedExamIds);
                   })
                   ->where('id', $user->id)
                   ->where('usergroup_id', 6)
                   ->first();
          
        
         return $learner;        
}

// subjects
public function subjects($schoolId, $section, $learner, $exam){
    $subjects =  Subject::where("school_id", $schoolId)
                                 ->where("section_id", $section)
                                 ->with("mark", function($q) use($learner, $exam){
                                    $q->where("student_id", $learner->id)
                                    ->with("exam", function ($q2) use($exam){
                                        $q2->where("section_id", $exam->section_id)
                                          ->where("academic_term_id", $exam->academic_term_id)
                                          ->where("academic_year_id", $exam->academic_year_id);
                                    });
                                 })
                                 ->get();
                                
          return $subjects;
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
            $learner->average = $learner->marks->average("marks");
            return $learner;
        });
return $learners;
}

public function learnersTotal($exam, $learner){
    // dd($learner);
    $total = Exam::with("marks", "subject") 
            ->where("school_id", $exam->school_id)
            ->where("academic_year_id", $exam->academic_year_id)
            ->where("academic_term_id", $exam->academic_term_id)
            ->with("marks", function($q) use( $learner, $exam) {
                    $q->where("student_id", $learner->id);
                    // ->where("subject_id", $exam->subject_id);
                    })
                    ->get();
   
return $total;
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
                       ->with(["examType"])
                       ->where("section_id", $exam->section_id)
                       ->where("academic_year_id", $exam->academic_year_id)
                       ->where("academic_term_id", $exam->academic_term_id)
                    //    ->where("status", $exam->status) //========to be added
                       ->get();
  }

  public function examsDone($schoolId, $exam){
    // to add exam type filter
    return Exam::where("school_id", $schoolId)
                       ->where("section_id", $exam->section_id)
                       ->where("academic_term_id", $exam->academic_term_id)
                       ->where("academic_year_id", $exam->academic_year_id)
                       ->count();
  }

   public function grade($learner, $exam){
        $agg = 0;   
        $remark = null;
        foreach($learner->marks as $mark){
            if (empty($mark->grade)) continue; // skip marks with no grade (shouldn't happen, but handle gracefully)
            $gradeMapping = SchoolGradingSystem::where('school_id', $learner->school_id)
                    ->where('standard_id', $exam->standard_id)
                    ->where('grade', $mark->grade)
                    ->first();
            if (!$gradeMapping) continue; // grade not found in grading system (shouldn't happen, but handle gracefully)
                    
            $agg += $gradeMapping->points;
            $remark[] = $gradeMapping;
        }         
        return ["agg" => $agg, "remark" => $remark];
    }
//   grading school system
// public function grade(int $mark, int $schoolId, Exam $exam){
//         // dd($exam);
//         return SchoolGradingSystem::where('school_id', $schoolId)
//                 ->where('standard_id', $exam->standard_id)
//                 ->where('min_score', '<=', $mark)
//                 ->where('max_score', '>=', $mark)
//                 ->value("grade");
    
//     }
}

