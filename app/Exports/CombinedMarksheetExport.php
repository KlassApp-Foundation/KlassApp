<?php

namespace App\Exports;

use App\Contracts\Results\DivisionRule;
use App\Contracts\Results\GradingScale;
use App\Models\AcademicTerm;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultSitting;
use App\Services\Results\ResultStudent;
use App\Services\Results\ResultsPolicy;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Two-sheet workbook: "MONTHLY RESULTS" (subjects as columns, one row per
 * student per mid-term month) and "END OF TERM" (subjects as columns, one
 * row per student). Raw marks, full active roster, blank cells for missing marks.
 *
 * Monthly rows come from non-contributing sittings split by calendar month.
 * The end-of-term sheet is the contributing sitting. Subject columns stay in
 * report order for the whole class, including a subject with no mark.
 */
class CombinedMarksheetExport implements WithMultipleSheets
{
    public function __construct(
        private StandardLink $stdLink,
        private AcademicTerm $term
    ) {}

    public function sheets(): array
    {
        $schoolId = (int) $this->stdLink->school_id;
        $sectionId = (int) $this->stdLink->section_id;

        $subjects = Subject::sortByReportOrder(
            Subject::where('school_id', $schoolId)
                ->where('section_id', $sectionId)
                ->get()
        );
        $subjectNames = $subjects->pluck('name')->all();

        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: $schoolId,
            sectionId: $sectionId,
            academicTermId: (int) $this->term->id,
            standardLinkId: (int) $this->stdLink->id,
        ), new ResultsPolicy(
            app(GradingScale::class),
            app(DivisionRule::class),
            splitMidTermByMonth: true,
        ));

        $students = $sheet->students;
        usort($students, function (ResultStudent $left, ResultStudent $right) {
            $byName = strcasecmp($left->name, $right->name);
            if ($byName !== 0) {
                return $byName;
            }

            return $left->studentId <=> $right->studentId;
        });

        $monthlySittings = array_values(array_filter(
            $sheet->sittings,
            fn (ResultSitting $sitting) => ! $sitting->contributesToReport
        ));
        usort($monthlySittings, function (ResultSitting $left, ResultSitting $right) {
            return [$left->periodKey, $left->examTypeId, $left->name]
                <=> [$right->periodKey, $right->examTypeId, $right->name];
        });

        $monthlyHeadings = array_merge(['STUDENT NAME', 'MONTH'], $subjectNames);
        $monthlyRows = [];
        foreach ($students as $student) {
            foreach ($monthlySittings as $sitting) {
                $rowStudent = $sitting->student($student->studentId);
                $row = [$student->name, $sitting->periodLabel];
                foreach ($subjects as $subject) {
                    $row[] = $this->cellValue($rowStudent?->cell((int) $subject->id));
                }
                $monthlyRows[] = $row;
            }
        }

        $contributing = null;
        foreach ($sheet->sittings as $sitting) {
            if ($sitting->contributesToReport) {
                $contributing = $sitting;
                break;
            }
        }

        $eotHeadings = array_merge(['STUDENT NAME'], $subjectNames);
        $eotRows = [];
        foreach ($students as $student) {
            $source = $contributing?->student($student->studentId);
            $row = [$student->name];
            foreach ($subjects as $subject) {
                $row[] = $this->cellValue($source?->cell((int) $subject->id));
            }
            $eotRows[] = $row;
        }

        return [
            new MarksheetExport($monthlyHeadings, $monthlyRows, 'MONTHLY RESULTS'),
            new MarksheetExport($eotHeadings, $eotRows, 'END OF TERM'),
        ];
    }

    private function cellValue(?ResultCell $cell): float|string
    {
        if ($cell === null || $cell->status !== ResultCell::MARKED || $cell->mark === null) {
            return '';
        }

        return (float) $cell->mark;
    }
}
