<?php

namespace App\Exports;

use App\Models\AcademicTerm;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultSitting;
use App\Services\Results\ResultStudent;
use App\Services\Reports\ReportScoring;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * One sheet per exam set. The sheet title is the name the school gave that
 * set. A set split by month keeps the month in the title. A set that counts
 * toward the term total also has a division column. A blank cell stays blank.
 * An absence is the word ABS.
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
        ), app(ReportScoring::class)->displayPolicy($schoolId, $sectionId, (int) $this->term->id, (int) $this->stdLink->id, true));

        $students = $sheet->students;
        usort($students, function (ResultStudent $left, ResultStudent $right) {
            $byName = strcasecmp($left->name, $right->name);
            if ($byName !== 0) {
                return $byName;
            }

            return $left->studentId <=> $right->studentId;
        });

        $used = [];
        $exports = [];
        foreach ($sheet->sittings as $sitting) {
            $headings = array_merge(['STUDENT NAME'], $subjectNames);
            if ($sitting->contributesToReport) {
                $headings[] = 'DIVISION';
            }
            $rows = [];
            foreach ($students as $student) {
                $source = $sitting->student($student->studentId);
                $row = [$student->name];
                foreach ($subjects as $subject) {
                    $row[] = $this->cellValue($source?->cell((int) $subject->id));
                }
                if ($sitting->contributesToReport) {
                    $row[] = (string) ($student->division ?? '');
                }
                $rows[] = $row;
            }
            $exports[] = new MarksheetExport($headings, $rows, $this->sheetTitle($sitting, $used));
        }

        return $exports;
    }

    /**
     * @param array<string, true> $used
     */
    private function sheetTitle(ResultSitting $sitting, array &$used): string
    {
        $name = trim($sitting->name);
        if ($sitting->periodLabel !== '') {
            $name = trim($name.' '.$sitting->periodLabel);
        }
        $name = str_replace(['\\', '/', '*', '?', ':', '[', ']'], ' ', $name);
        $name = trim((string) preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            $name = 'Exam set';
        }
        $base = mb_substr($name, 0, 31);
        $title = $base;
        $n = 2;
        while (isset($used[mb_strtolower($title)])) {
            $suffix = ' '.$n;
            $title = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
            $n++;
        }
        $used[mb_strtolower($title)] = true;

        return $title;
    }

    private function cellValue(?ResultCell $cell): float|string
    {
        if ($cell !== null && $cell->status === ResultCell::ABSENT) {
            return 'ABS';
        }
        if ($cell === null || $cell->status !== ResultCell::MARKED || $cell->mark === null) {
            return '';
        }

        return (float) $cell->mark;
    }
}
