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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * One sheet per exam set. The sheet name is the school's own set name.
 * A set that is split by month keeps a month column on that set's sheet.
 * A set that counts toward the combined mark has one row per student.
 * Raw marks, full active roster, blank cells for missing marks.
 * Subject columns stay in report order, including a subject with no mark.
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

        $labels = $this->labels($schoolId);
        $used = [];
        $sheets = [];
        $monthlyHeadings = array_merge(['STUDENT NAME', 'MONTH'], $subjectNames);

        $alone = [];
        foreach ($monthlySittings as $sitting) {
            $alone[$sitting->examTypeId]['title'] = $this->sittingTitle($sitting, $labels);
            $alone[$sitting->examTypeId]['sittings'][] = $sitting;
        }
        foreach ($alone as $group) {
            $rows = [];
            foreach ($students as $student) {
                foreach ($group['sittings'] as $sitting) {
                    $rowStudent = $sitting->student($student->studentId);
                    $row = [$student->name, $sitting->periodLabel];
                    foreach ($subjects as $subject) {
                        $row[] = $this->cellValue($rowStudent?->cell((int) $subject->id));
                    }
                    $rows[] = $row;
                }
            }
            $sheets[] = new MarksheetExport($monthlyHeadings, $rows, $this->sheetTitle($group['title'], $used));
        }

        $counting = [];
        foreach ($sheet->sittings as $sitting) {
            if (! $sitting->contributesToReport) {
                continue;
            }
            $counting[$sitting->examTypeId] = $sitting;
        }
        $countHeadings = array_merge(['STUDENT NAME'], $subjectNames);
        foreach ($counting as $sitting) {
            $rows = [];
            foreach ($students as $student) {
                $source = $sitting->student($student->studentId);
                $row = [$student->name];
                foreach ($subjects as $subject) {
                    $row[] = $this->cellValue($source?->cell((int) $subject->id));
                }
                $rows[] = $row;
            }
            $sheets[] = new MarksheetExport($countHeadings, $rows, $this->sheetTitle($this->sittingTitle($sitting, $labels), $used));
        }

        return $sheets;
    }

    /**
     * @param  array<string, true>  $used
     */
    private function sheetTitle(string $name, array &$used): string
    {
        $clean = trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[\\\\\\/?*\\[\\]:]/', ' ', $name)));
        if ($clean === '') {
            $clean = 'Exam set';
        }
        $base = mb_substr($clean, 0, 31);
        $title = $base;
        $n = 2;
        while (isset($used[$title])) {
            $suffix = ' '.$n;
            $title = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
            $n++;
        }
        $used[$title] = true;

        return $title;
    }

    /**
     * @param  array<int|string, string>  $labels
     */
    private function sittingTitle(ResultSitting $sitting, array $labels): string
    {
        $custom = trim((string) ($labels[(string) $sitting->examTypeId] ?? $labels[$sitting->examTypeId] ?? ''));

        return $custom !== '' ? $custom : $sitting->name;
    }

    /**
     * @return array<int|string, string>
     */
    private function labels(int $schoolId): array
    {
        if (! Schema::hasColumn('schools', 'exam_type_labels')) {
            return [];
        }

        $raw = DB::table('schools')->where('id', $schoolId)->value('exam_type_labels');
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function cellValue(?ResultCell $cell): float|string
    {
        if ($cell !== null && $cell->status === ResultCell::ABSENT) {
            return 'AB';
        }
        if ($cell === null || $cell->status !== ResultCell::MARKED || $cell->mark === null) {
            return '';
        }

        return (float) $cell->mark;
    }
}
