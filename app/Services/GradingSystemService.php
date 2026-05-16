<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\Academics\Exam;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Section;
use App\Models\Subject;

class GradingSystemService
{
    // give grades to marks
    public function grade(int $mark, int $schoolId, Exam $exam){
        // dd($exam);
        return SchoolGradingSystem::where('school_id', $schoolId)
                ->where('standard_id', $exam->standard_id)
                ->where('min_score', '<=', $mark)
                ->where('max_score', '>=', $mark)
                ->value("grade");
    
    }

    // give grades to marks
    public function aggregates($student, $exam)
{
    $gradingMap = SchoolGradingSystem::where("school_id", $student->school_id)
        ->where("standard_id", $exam->standard_id)
        ->pluck('rank', 'grade');

    $aggregates = 0;

    foreach ($student->marks as $mark) {
        $aggregates += $gradingMap[$mark->grade] ?? 0;
    }

    return $aggregates;
}
    public function defaults(int $schoolId, int $standardId){
        $grades = [
    ["school_id"=>$schoolId, "standard_id"=>$standardId, "grade"=>"D1", "min_score"=>95, "max_score"=>100, "remark"=>"Excellent"],
    ["school_id"=>$schoolId, "standard_id"=>$standardId, "grade"=>"D2", "min_score"=>90, "max_score"=>94, "remark"=>"Very Good"],
    ["school_id"=>$schoolId, "standard_id"=>$standardId, "grade"=>"C3", "min_score"=>75, "max_score"=>89, "remark"=>"Good"],
    ["school_id"=>$schoolId, "standard_id"=>$standardId, "grade"=>"C4", "min_score"=>65, "max_score"=>74, "remark"=>"Fair"],
    ["school_id"=>$schoolId, "standard_id"=>$standardId, "grade"=>"C5", "min_score"=>60, "max_score"=>64, "remark"=>"Tried"],
    ["school_id"=>$schoolId, "standard_id"=>$standardId, "grade"=>"C6", "min_score"=>50, "max_score"=>59, "remark"=>"Improve"],
    ["school_id"=>$schoolId, "standard_id"=>$standardId, "grade"=>"P7", "min_score"=>45, "max_score"=>49, "remark"=>"Improve"],
    ["school_id"=>$schoolId, "standard_id"=>$standardId, "grade"=>"P8", "min_score"=>40, "max_score"=>44, "remark"=>"Poor"],
    ["school_id"=>$schoolId, "standard_id"=>$standardId, "grade"=>"F9", "min_score"=>0,  "max_score"=>39, "remark"=>"Very Poor"],
];

    return $grades;
    }
}