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

    /**
     * Return a deterministic head-teacher comment for a student's report card.
     *
     * Mirrors commentFor() (same score bands, seeded by a distinct salt) but
     * enforces that the returned phrase never equals the class-teacher comment
     * on the same report. If the seed lands on a colliding phrase, the next
     * option in the same band is tried; when every option in the band collides
     * (or the bank has no phrases), an empty string is returned so a report can
     * never show identical text for both signature blocks.
     */
    public function headTeacherCommentFor(int $totalScore, string $standardName, int $studentId, int $examId, string $classTeacherComment): string
    {
        $group = in_array($standardName, ['primary_lower', 'nursery'], true) ? 'lower' : 'upper';

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