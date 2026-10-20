<?php

namespace App\Services\Results;

use App\Contracts\Results\GradingScale;
use App\Models\Academics\Exam;
use Illuminate\Support\Collection;

/**
 * A grade letter for a mark that is already on a results sheet.
 * The school scale wins. A stored letter is only a fallback when the
 * scale has no band, so an older row does not go blank. The overall
 * column has no stored letter: it is the scale or nothing.
 */
final class DisplayedGrade
{
    public function __construct(private readonly GradingScale $gradingScale)
    {
    }

    public function subject(int $schoolId, int $standardId, float $mark, ?string $stored): string
    {
        $label = $this->gradingScale->labelFor($schoolId, $standardId, $mark);
        if ($label !== null && $label !== '') {
            return $label;
        }
        $stored = trim((string) $stored);

        return $stored !== '' ? $stored : '—';
    }

    public function overall(int $schoolId, int $standardId, float $score): string
    {
        $label = $this->gradingScale->labelFor($schoolId, $standardId, $score);

        return ($label !== null && $label !== '') ? $label : '—';
    }

    /**
     * Keep the bundle's subject names and order. Marks, totals and letters
     * come from the sitting. A stored letter remains only when the scale
     * has no band for that mark.
     *
     * @param  array{label:string, subjects:array<int, array{id:int,name:string}>, marks:Collection}  $exam
     * @return array{label:string, subjects:array<int, array{id:int,name:string}>, marks:Collection}
     */
    public function applyToBundle(int $schoolId, Exam $anchor, int $sectionId, int $studentId, array $exam): array
    {
        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: $schoolId,
            sectionId: $sectionId,
            academicTermId: (int) $anchor->academic_term_id,
            examTypeId: (int) $anchor->exam_type_id,
            studentId: $studentId,
        ));
        $row = $sheet->student($studentId);
        if ($row === null) {
            $exam['marks'] = collect();

            return $exam;
        }
        $marks = collect();
        foreach ($exam['subjects'] as $subject) {
            $cell = $row->cell((int) $subject['id']);
            if ($cell === null || $cell->status !== ResultCell::MARKED || $cell->mark === null) {
                continue;
            }
            $stored = $exam['marks']->first(fn ($mark) => (int) $mark->subject_id === (int) $subject['id']);
            $marks->push((object) [
                'subject_id' => (int) $subject['id'],
                'student_id' => $studentId,
                'marks' => $cell->mark,
                'grade' => $this->subject($schoolId, $row->standardId, $cell->mark, $stored->grade ?? null),
            ]);
        }
        $exam['marks'] = $marks;

        return $exam;
    }
}
