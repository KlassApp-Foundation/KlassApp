<?php

namespace App\Services\ReportCards;

use App\Contracts\Results\ReleaseGate;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\ReportGeneration;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Support\Collection;

/**
 * The admin report cards class screen: one class, one sitting, released marks only.
 *
 * The numbers come from ResultsService (positions, totals, aggregate, division). Which students
 * appear comes from the release gate, so a class or stream whose sitting is not released shows
 * the "not released yet" state and no marks.
 */
class ClassReportScreen
{
    private const DIVISION_LABELS = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', 'U' => 'U'];

    public function __construct(private ResultsService $results, private ReleaseGate $gate)
    {
    }

    /**
     * Classes (sections) that have a stream in the current academic year.
     *
     * @return list<array{id: int, label: string, links: list<array{id: int, stream: string}>}>
     */
    public function classes(int $schoolId): array
    {
        $years = AcademicYear::query()->where('school_id', $schoolId)->where('status', 1)->pluck('id');

        return StandardLink::query()
            ->where('school_id', $schoolId)
            ->whereIn('academic_year_id', $years)
            ->whereNotNull('section_id')
            ->with(['section', 'standard'])
            ->orderBy('id')
            ->get()
            ->filter(fn (StandardLink $l) => $l->section && (int) $l->section->school_id === $schoolId)
            ->groupBy('section_id')
            ->map(fn (Collection $links) => [
                'id' => (int) $links->first()->section_id,
                'label' => (string) $links->first()->section->name,
                // Class level order (#1269), then the name.
                'level' => (int) ($links->first()->standard?->order ?? 999999),
                'links' => $links->map(fn (StandardLink $l) => ['id' => (int) $l->id, 'stream' => trim((string) $l->stream)])->values()->all(),
            ])
            ->sort(fn (array $a, array $b) => [$a['level'], mb_strtolower($a['label'])] <=> [$b['level'], mb_strtolower($b['label'])])
            ->values()
            ->all();
    }

    /**
     * Sittings (exam type in a term) for the class, newest term first.
     *
     * @return list<array{key: string, exam_type_id: int, term_id: int, label: string}>
     */
    public function sittings(int $schoolId, int $sectionId): array
    {
        $sectionIds = $this->sectionIdsFor($schoolId, $sectionId);

        return Exam::query()
            ->where('school_id', $schoolId)
            ->whereIn('section_id', $sectionIds)
            ->whereNull('deleted_at')
            ->whereNotNull('exam_type_id')
            ->with(['examType', 'academicTerm'])
            ->get()
            ->filter(fn (Exam $e) => $e->academicTerm && (int) $e->academicTerm->school_id === $schoolId)
            ->groupBy(fn (Exam $e) => $e->exam_type_id.':'.$e->academic_term_id)
            ->map(function (Collection $group, string $key) {
                $exam = $group->first();
                $term = $exam->academicTerm;

                return [
                    'key' => $key,
                    'exam_type_id' => (int) $exam->exam_type_id,
                    'term_id' => (int) $exam->academic_term_id,
                    'label' => $this->sentenceCase((string) ($exam->examType?->name ?: 'Exam')).' · '.$term->name,
                    'sort' => (string) $term->getRawOriginal('starts_on').'-'.str_pad((string) $term->id, 9, '0', STR_PAD_LEFT),
                ];
            })
            ->sortByDesc('sort')
            ->map(fn (array $s) => array_diff_key($s, ['sort' => 1]))
            ->values()
            ->all();
    }

    /**
     * @param  array{id: int, label: string, links: list<array{id: int, stream: string}>}  $class
     * @param  array{exam_type_id: int, term_id: int, label: string}  $sitting
     * @return array<string, mixed>
     */
    public function build(int $schoolId, array $class, array $sitting, ?int $streamLinkId = null, string $sort = 'position'): array
    {
        // A class exam sits on the class's own section; its students are enrolled
        // in the class's streams. So both the links and the sheet follow the
        // exam, not the picker row.
        $links = $this->classLinks($schoolId, $class);
        $linkIds = array_column($links, 'id');
        $streams = array_column($links, 'stream', 'id');
        $exam = $this->examForSitting($schoolId, (int) $class['id'], $sitting);
        $releasedLinks = $this->releasedLinks($schoolId, $class, $sitting, $links, $exam);
        $type = ExamType::query()->whereKey((int) $sitting['exam_type_id'])->first();

        $base = [
            'class' => $class['label'],
            'sitting' => $sitting['label'],
            'term_id' => $sitting['term_id'],
            'streams' => array_filter($streams, fn ($s) => $s !== ''),
            'released_links' => $releasedLinks,
            'composition_line' => app(EndOfTermCompositionLine::class)->lineFor(
                $schoolId,
                EndOfTermCompositionLine::isEndOfTerm($type?->name, $type?->code),
                $exam?->id,
            ),
        ];

        if ($releasedLinks === []) {
            return $base + ['state' => 'unreleased', 'rows' => [], 'subjects' => [], 'kpis' => null, 'divisions' => [], 'subject_averages' => [], 'generations' => []];
        }

        $sheet = $this->results->sheet(new ResultsQuery(
            $schoolId,
            $exam?->section_id ?? (int) $class['id'],
            $sitting['term_id'],
            null,
            $sitting['exam_type_id'],
        ));

        // The sheet is built on the class section, so it reports the class link for
        // every student. Their own stream is where they are enrolled.
        $enrollment = $this->enrollmentLinks($schoolId, $sheet->students, $linkIds);
        $linkOf = fn ($student) => $enrollment[(int) $student->studentId] ?? (int) $student->standardLinkId;

        $students = array_values(array_filter($sheet->students, fn ($s) => in_array($linkOf($s), $releasedLinks, true)));
        if ($students === []) {
            return $base + ['state' => 'empty', 'rows' => [], 'subjects' => [], 'kpis' => null, 'divisions' => [], 'subject_averages' => [], 'generations' => []];
        }

        $subjects = array_map(fn (array $s) => ['id' => $s['id'], 'name' => $s['name'], 'short' => $this->shortName($s['name'])], $sheet->subjects);

        $divisions = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, 'U' => 0];
        $averages = [];
        $subjectTotals = [];
        foreach ($students as $student) {
            if ($student->division !== null && isset($divisions[$student->division])) {
                $divisions[$student->division]++;
            }
            if ($student->displayAverage !== null) {
                $averages[] = $student->displayAverage;
            }
            foreach ($sheet->subjects as $subject) {
                $cell = $student->cell($subject['id']);
                if ($cell && $cell->mark !== null) {
                    $subjectTotals[$subject['id']][] = $cell->mark;
                }
            }
        }

        $subjectAverages = [];
        foreach ($subjects as $subject) {
            $marks = $subjectTotals[$subject['id']] ?? [];
            $subjectAverages[] = ['name' => $subject['name'], 'value' => $marks === [] ? null : round(array_sum($marks) / count($marks), 1)];
        }

        $shown = array_values(array_filter($students, fn ($s) => $streamLinkId === null || $linkOf($s) === $streamLinkId));
        usort($shown, $sort === 'name'
            ? fn ($a, $b) => strcasecmp($a->name, $b->name)
            : fn ($a, $b) => ($a->classPosition ?? PHP_INT_MAX) <=> ($b->classPosition ?? PHP_INT_MAX) ?: strcasecmp($a->name, $b->name));

        $studentIds = array_map(fn ($s) => (int) $s->studentId, $shown);
        $klsById = $studentIds === [] ? [] : User::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereIn('id', $studentIds)
            ->pluck('registration_number', 'id')
            ->all();

        $rows = array_map(function ($s) use ($subjects, $streams, $class, $sitting, $linkOf, $klsById) {
            $linkId = $linkOf($s);

            return [
                'student_id' => $s->studentId,
                'name' => $s->name,
                'kls' => trim((string) ($klsById[(int) $s->studentId] ?? '')),
                'position' => $s->classPosition,
                'out_of' => $s->classOutOf,
                'stream' => $streams[$linkId] ?? '',
                'marks' => array_map(fn (array $sub) => $this->displayCell($s->cell($sub['id'])), $subjects),
                'total' => $s->displayTotal !== null ? $this->number($s->displayTotal) : '—',
                'aggregate' => $s->aggregate ?? '—',
                'division' => $s->division !== null ? (self::DIVISION_LABELS[$s->division] ?? $s->division) : '—',
                'download_url' => url('/admin/reports/cards/'.$linkId.'/student/'.$s->studentId.'/download?term='.$sitting['term_id']),
            ];
        }, $shown);

        return $base + [
            'state' => 'ready',
            'link_counts' => array_count_values(array_map(fn ($s) => $linkOf($s), $students)),
            'rows' => $rows,
            'subjects' => $subjects,
            'subject_averages' => $subjectAverages,
            'divisions' => $divisions,
            'division_labels' => self::DIVISION_LABELS,
            'kpis' => [
                'ready' => count($students),
                'average' => $averages === [] ? null : number_format(round(array_sum($averages) / count($averages), 1), 1),
                'division_one' => $divisions['1'],
                'ungraded' => $divisions['U'],
            ],
            'generations' => $this->generations($schoolId, $releasedLinks, $sitting['term_id']),
        ];
    }

    /**
     * A section and, when it is a stream, its class's section. Exam sets create
     * exams on the class; a stream reads its class's sitting.
     *
     * @return list<int>
     */
    private function sectionIdsFor(int $schoolId, int $sectionId): array
    {
        $sectionIds = [$sectionId];
        $section = Section::query()->where('school_id', $schoolId)->find($sectionId);
        if ($section !== null) {
            $base = app(\App\Services\ClassStructureService::class)->resolveBaseSection($section);
            $sectionIds[] = (int) $base->id;
        }

        return array_values(array_unique(array_map('intval', $sectionIds)));
    }

    /**
     * Each student's own enrolment link among the class's links (their stream).
     * The results sheet reports the class link for everyone, so this is where the
     * real stream comes from.
     *
     * @param  array<int, mixed>  $students
     * @param  list<int>  $linkIds
     * @return array<int, int>
     */
    private function enrollmentLinks(int $schoolId, array $students, array $linkIds): array
    {
        $userIds = array_values(array_unique(array_map(fn ($s) => (int) $s->studentId, $students)));
        if ($userIds === [] || $linkIds === []) {
            return [];
        }

        return StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->whereNull('deleted_at')
            ->whereIn('user_id', $userIds)
            ->whereIn('standardLink_id', $linkIds)
            ->orderBy('id')
            ->get(['user_id', 'standardLink_id'])
            ->mapWithKeys(fn ($row) => [(int) $row->user_id => (int) $row->standardLink_id])
            ->all();
    }

    /**
     * The class's own link plus the links of its streams. A class exam sits on the
     * class section ("Primary Seven") and its students are enrolled in the streams
     * ("Primary Seven E" / "W"), so the screen has to look at both.
     *
     * @param  array{id:int, label:string, links:list<array{id:int, stream:string}>}  $class
     * @return list<array{id:int, stream:string}>
     */
    private function classLinks(int $schoolId, array $class): array
    {
        $links = $class['links'] ?? [];
        $label = trim((string) ($class['label'] ?? ''));
        if ($label === '') {
            return $links;
        }

        $sections = Section::query()->where('school_id', $schoolId)->get(['id', 'name']);
        $streamSectionIds = $sections
            ->filter(fn ($section) => str_starts_with(trim((string) $section->name), $label.' '))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($streamSectionIds === []) {
            return $links;
        }

        $streamLinks = StandardLink::query()
            ->with('section')
            ->where('school_id', $schoolId)
            ->whereIn('section_id', $streamSectionIds)
            ->whereIn('academic_year_id', AcademicYear::query()->where('school_id', $schoolId)->where('status', 1)->pluck('id'))
            ->orderBy('id')
            ->get();

        $existing = array_map('intval', array_column($links, 'id'));
        foreach ($streamLinks as $link) {
            if (in_array((int) $link->id, $existing, true)) {
                continue;
            }

            $name = trim((string) ($link->section->name ?? ''));
            $stream = $name !== '' && str_starts_with($name, $label.' ')
                ? trim(substr($name, strlen($label)))
                : trim((string) ($link->stream ?? ''));

            $links[] = ['id' => (int) $link->id, 'stream' => $stream];
        }

        return $links;
    }

    /**
     * The exam behind a sitting for this class: it sits on the class's section.
     *
     * @param  array{exam_type_id:int, term_id:int}  $sitting
     */
    private function examForSitting(int $schoolId, int $sectionId, array $sitting): ?Exam
    {
        return Exam::query()
            ->where('school_id', $schoolId)
            ->whereIn('section_id', $this->sectionIdsFor($schoolId, $sectionId))
            ->where('exam_type_id', $sitting['exam_type_id'])
            ->where('academic_term_id', $sitting['term_id'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();
    }

    /**
     * Streams of the class whose sitting is released. One enrolled student stands for the stream:
     * a class release names the stream, a school release covers all of them. A class exam sits on
     * the class's section, so a stream's student answers for that stream.
     *
     * @param  list<array{id:int, stream:string}>  $links
     * @return list<int>
     */
    private function releasedLinks(int $schoolId, array $class, array $sitting, array $links, ?Exam $exam): array
    {
        if (! $exam) {
            return [];
        }

        $released = [];
        foreach ($links as $link) {
            $studentId = StudentAcademic::query()
                ->where('school_id', $schoolId)
                ->where('standardLink_id', $link['id'])
                ->whereNull('deleted_at')
                ->whereIn('user_id', User::query()->where('school_id', $schoolId)->where('usergroup_id', 6)->whereNull('deleted_at')->select('id'))
                ->orderBy('id')
                ->value('user_id');
            $person = $studentId ? User::find($studentId) : null;
            if ($person && (int) $person->school_id === $schoolId && $this->gate->isReleased($person, $exam)) {
                $released[] = $link['id'];
            }
        }

        return $released;
    }

    /**
     * Latest class PDF job per stream for this term: pending, completed or failed.
     *
     * @param  list<int>  $linkIds
     * @return array<int, array{status: string, id: int, error: ?string}>
     */
    private function generations(int $schoolId, array $linkIds, int $termId): array
    {
        return ReportGeneration::query()
            ->where('school_id', $schoolId)
            ->whereIn('standard_link_id', $linkIds)
            ->where('academic_term_id', $termId)
            ->where('mode', 'merged')
            ->orderByDesc('id')
            ->get()
            ->unique('standard_link_id')
            ->mapWithKeys(fn (ReportGeneration $g) => [(int) $g->standard_link_id => [
                'status' => in_array($g->status, ['pending', 'processing'], true) ? 'pending' : (string) $g->status,
                'id' => (int) $g->id,
                'error' => $g->status === 'failed' ? 'The class PDF did not finish.' : null,
            ]])
            ->all();
    }

    private function displayCell(?ResultCell $cell): string
    {
        if ($cell === null) {
            return '—';
        }
        $grade = strtoupper(trim((string) $cell->grade));
        if ($cell->status === ResultCell::ABSENT || in_array($grade, ['AB', 'ABS'], true)) {
            return 'ABS';
        }
        if ($cell->mark !== null) {
            return $this->number($cell->mark);
        }

        return '—';
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    private function shortName(string $name): string
    {
        $name = trim($name);

        return mb_strlen($name) > 8 ? mb_substr($name, 0, 6).'.' : $name;
    }

    private function sentenceCase(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value !== mb_strtoupper($value)) {
            return $value;
        }
        $lower = mb_strtolower($value);

        return mb_strtoupper(mb_substr($lower, 0, 1)).mb_substr($lower, 1);
    }
}
