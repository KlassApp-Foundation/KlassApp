<?php

namespace App\Services;

class ReportCardCommentService
{
    /**
     * Return a deterministic class-teacher comment for a student's report
     * card based on their total score and standard group.
     *
     * The comment is selected from the score band and seeded deterministically
     * by (student_id + exam_id) so the same student/exam always gets the same
     * comment on reprint rather than a random one each time.
     *
     * @param int    $totalScore     Student's total numeric marks across all subjects
     * @param string $standardName   Standard name (nursery, primary_lower, primary, primary_upper)
     * @param int    $studentId      Student's user ID
     * @param int    $examId         Exam ID for deterministic seeding
     */
    public function commentFor(int $totalScore, string $standardName, int $studentId, int $examId): string
    {
        $group = in_array($standardName, ['primary_lower', 'nursery'], true) ? 'lower' : 'upper';

        $bands = config("report_card_comments.$group", []);

        foreach ($bands as $band) {
            if ($totalScore >= $band['min'] && $totalScore <= $band['max']) {
                $seed = crc32("{$studentId}-{$examId}");
                $index = $seed % count($band['comments']);

                return $band['comments'][$index];
            }
        }

        return '';
    }
}