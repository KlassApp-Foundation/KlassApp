<?php

namespace App\Services\Google\Sheets;

use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\User;
use App\Services\MarksImport\MarksImportService;

/**
 * Builds the mark sheet from the marks-import template plus saved marks.
 *
 * ResultsService is on main, and it is the term sheet: one chosen mark per
 * subject and exam type. This export is one exam, so it keeps that exam's
 * own marks. The roster and the save path stay in MarksImportService.
 */
class ExamMarkSheetReader implements ClassMarkSheetReader
{
    public function __construct(private readonly MarksImportService $imports)
    {
    }

    public function forExam(Exam $exam, User $actor): array
    {
        $rows = $this->imports->templateRows($exam, $actor);
        $registrations = array_values(array_filter(array_map(
            fn ($row) => $row[0] !== null && $row[0] !== '' ? (string) $row[0] : null,
            $rows,
        )));

        $students = User::query()
            ->where('school_id', $exam->school_id)
            ->where('status', 'active')
            ->whereIn('registration_number', $registrations)
            ->get()
            ->groupBy('registration_number');

        $marks = Marks::query()
            ->where('school_id', $exam->school_id)
            ->where('exam_id', $exam->id)
            ->where('section_id', $exam->section_id)
            ->get()
            ->keyBy('student_id');

        $filled = [];
        foreach ($rows as $row) {
            $registration = (string) ($row[0] ?? '');
            $matches = $students->get($registration, collect());
            $mark = null;
            if ($matches->count() === 1) {
                $saved = $marks->get($matches->first()->id);
                $mark = $saved?->marks;
            }
            $filled[] = [$row[0], $row[1], $mark];
        }

        $exam->loadMissing('section', 'subject', 'academicTerm');
        $title = trim(implode(' ', array_filter([
            $exam->section?->name,
            $exam->subject?->name,
            $exam->academicTerm?->name,
        ])));

        return [
            'title' => $title !== '' ? $title : 'Mark sheet',
            'headings' => $this->imports->templateHeadings(),
            'rows' => $filled,
            'info' => $this->imports->templateInfoRows($exam),
        ];
    }
}
