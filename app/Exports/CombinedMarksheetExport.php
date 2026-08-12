<?php

namespace App\Exports;

use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Two-sheet workbook: "MONTHLY RESULTS" (subjects as columns, one row per
 * student per MID month) and "END OF TERM" (subjects as columns, one row per
 * student). Raw marks, full active roster, blank cells for missing marks.
 */
class CombinedMarksheetExport implements WithMultipleSheets
{
    public function __construct(
        private StandardLink $stdLink,
        private AcademicTerm $term
    ) {}

    public function sheets(): array
    {
        $schoolId = $this->stdLink->school_id;
        $sectionId = $this->stdLink->section_id;
        $termId = $this->term->id;

        $subjects = Subject::sortByReportOrder(
            Subject::where('school_id', $schoolId)
                ->where('section_id', $sectionId)
                ->get()
        );

        $students = User::where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereHas('studentAcademic', fn ($q) => $q->where('standardLink_id', $this->stdLink->id))
            ->orderBy('name')
            ->get();

        $exams = Exam::where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->where('academic_term_id', $termId)
            ->with('examType')
            ->get();

        $midExams = $exams->filter(fn ($e) => $e->examType?->code === 'MID')
            ->sortBy('scheduled_at')
            ->values();

        $eotExam = $exams->filter(fn ($e) => (int) $e->examType?->contributes_to_report_total === 1)
            ->sortByDesc('id')
            ->first();

        // Preload marks into a [student][exam][subject] => marks lookup to avoid N+1.
        $markMap = [];
        foreach (Marks::whereIn('exam_id', $exams->pluck('id'))->get() as $m) {
            $markMap[$m->student_id][$m->exam_id][$m->subject_id] = $m->marks;
        }

        $subjectNames = $subjects->pluck('name')->all();

        // Sheet 1: MONTHLY RESULTS.
        $monthlyHeadings = array_merge(['STUDENT NAME', 'MONTH'], $subjectNames);
        $monthlyRows = [];
        foreach ($students as $student) {
            foreach ($midExams as $midExam) {
                $month = $midExam->scheduled_at ? strtoupper($midExam->scheduled_at->format('F')) : '';
                $row = [$student->name, $month];
                foreach ($subjects as $subject) {
                    $row[] = $this->markValue($markMap, $student->id, $midExam->id, $subject->id);
                }
                $monthlyRows[] = $row;
            }
        }

        // Sheet 2: END OF TERM.
        $eotHeadings = array_merge(['STUDENT NAME'], $subjectNames);
        $eotRows = [];
        foreach ($students as $student) {
            $row = [$student->name];
            foreach ($subjects as $subject) {
                $row[] = $eotExam
                    ? $this->markValue($markMap, $student->id, $eotExam->id, $subject->id)
                    : '';
            }
            $eotRows[] = $row;
        }

        return [
            new MarksheetExport($monthlyHeadings, $monthlyRows, 'MONTHLY RESULTS'),
            new MarksheetExport($eotHeadings, $eotRows, 'END OF TERM'),
        ];
    }

    private function markValue(array $map, int $studentId, int $examId, int $subjectId)
    {
        $value = $map[$studentId][$examId][$subjectId] ?? null;

        return $value !== null ? (float) $value : '';
    }
}
