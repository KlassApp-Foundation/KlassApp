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
        ->pluck('points', 'grade');

    $aggregates = 0;
    foreach ($student->marks as $mark) {
        $aggregates += $gradingMap[$mark->grade] ?? 0;
    }

    return $aggregates;
}
}