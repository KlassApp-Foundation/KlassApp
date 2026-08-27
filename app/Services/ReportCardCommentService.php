<?php

namespace App\Services;

use App\Models\Standard;

class ReportCardCommentService
{
    /**
     * Determine the comment group ('lower' or 'upper') for a standard.
     *
     * Uses grading_style when set on the Standard model:
     *   - 'total_marks' → 'lower' (descriptive/total-marks grading)
     *   - 'aggregate'   → 'upper' (aggregate/points grading)
     *
     * Falls back to legacy name-based logic when grading_style is NULL
     * (backward-compatible for schools that haven't set it yet).
     */
    private function resolveGroup(string $standardName, ?Standard $standard = null): string
    {
        if ($standard && $standard->grading_style !== null) {
            return $standard->grading_style === 'total_marks' ? 'lower' : 'upper';
        }

        // Legacy fallback: name-based classification
        return in_array($standardName, ['primary_lower', 'nursery'], true) ? 'lower' : 'upper';
    }

    /**
     * Return a deterministic class-teacher comment for a student's report
     * card based on their total score and standard group.
     *
     * The comment is selected from the score band and seeded deterministically
     * by (student_id + exam_id) so the same student/exam always gets the same
     * comment on reprint rather than a random one each time.
     *
     * @param int      $totalScore     Student's total numeric marks across all subjects
     * @param string   $standardName   Standard name (nursery, primary_lower, primary, primary_upper)
     * @param int      $studentId      Student's user ID
     * @param int      $examId         Exam ID for deterministic seeding
     * @param Standard|null $standard  Optional Standard model for grading_style-based group resolution
     */
    public function commentFor(int $totalScore, string $standardName, int $studentId, int $examId, ?Standard $standard = null): string
    {
        $group = $this->resolveGroup($standardName, $standard);

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

    /**
     * Return a deterministic head-teacher comment for a student's report card.
     *
     * Mirrors commentFor() (same score bands, seeded by a distinct salt) but
     * enforces that the returned phrase never equals the class-teacher comment
     * on the same report. If the seed lands on a colliding phrase, the next
     * option in the same band is tried; when every option in the band collides
     * (or the bank has no phrases), an empty string is returned so a report can
     * never show identical text for both signature blocks.
     *
     * @param int      $totalScore           Student's total numeric marks
     * @param string   $standardName         Standard name
     * @param int      $studentId            Student's user ID
     * @param int      $examId               Exam ID for deterministic seeding
     * @param string   $classTeacherComment  The class-teacher comment to avoid duplicating
     * @param Standard|null $standard        Optional Standard model for grading_style-based group resolution
     */
    public function headTeacherCommentFor(int $totalScore, string $standardName, int $studentId, int $examId, string $classTeacherComment, ?Standard $standard = null): string
    {
        $group = $this->resolveGroup($standardName, $standard);

        $bands = config("report_card_head_comments.$group", []);

        foreach ($bands as $band) {
            if ($totalScore >= $band['min'] && $totalScore <= $band['max']) {
                $options = $band['comments'];

                if (empty($options)) {
                    return '';
                }

                $seed = crc32("head-{$studentId}-{$examId}");
                $count = count($options);
                $index = $seed % $count;

                for ($attempt = 0; $attempt < $count; $attempt++) {
                    $candidate = $options[($index + $attempt) % $count];

                    if ($candidate !== $classTeacherComment) {
                        return $candidate;
                    }
                }

                \Log::warning('Head-teacher comment bank collided with class-teacher comment in every tier option', [
                    'student_id' => $studentId,
                    'exam_id' => $examId,
                    'band' => $band['min'] . '-' . $band['max'],
                ]);

                return '';
            }
        }

        return '';
    }
}
