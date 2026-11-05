<?php

namespace App\Services\Results;

use App\Contracts\Results\DivisionRule;
use App\Contracts\Results\GradingScale;
use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Read-only grades, totals, positions and divisions for one class and term.
 *
 * Marks are grouped by exam type, term, class and subject. One exam row
 * per subject and one exam row carrying several subjects both work.
 * A policy may keep each non-contributing exam type as one sitting per
 * calendar month. Totals use the school's exam-type setting, or the
 * global flag when the school has not chosen. The letter is the scale.
 * This service does not decide whether a parent may see the sheet.
 */
final class ResultsService
{
    public function __construct(
        private readonly GradingScale $gradingScale,
        private readonly DivisionRule $divisionRule,
        private readonly ExamTypeContribution $examTypeContribution,
    ) {
    }

    public function sheet(ResultsQuery $query, ?ResultsPolicy $policy = null): ResultSheet
    {
        $policy ??= new ResultsPolicy($this->gradingScale, $this->divisionRule);

        $term = AcademicTerm::query()
            ->where('id', $query->academicTermId)
            ->where('school_id', $query->schoolId)
            ->first();
        if ($term === null || ! $this->sectionBelongsToSchool($query) || ! $this->streamBelongsToClass($query)) {
            return ResultSheet::empty($query);
        }

        $exams = Exam::query()
            ->where('school_id', $query->schoolId)
            ->where('section_id', $query->sectionId)
            ->where('academic_term_id', $term->id)
            ->whereNull('deleted_at')
            ->when($query->examTypeId !== null, fn ($builder) => $builder->where('exam_type_id', $query->examTypeId))
            ->with('examType')
            ->get();

        $school = School::query()->find($query->schoolId);
        $preferences = $school?->exam_type_preferences;
        $composition = $school?->report_composition;
        $typeMeta = $this->typeMeta($exams, $preferences, $composition);
        $marks = $this->marksFor($query->schoolId, $exams);
        $chosen = $this->choose($exams, $marks);
        $roster = $this->roster($query, (int) $term->academic_year_id, $exams);

        $mainTypeIds = $query->examTypeId !== null
            ? (isset($typeMeta[$query->examTypeId]) ? [$query->examTypeId] : [])
            : array_values(array_map(
                fn (array $type) => $type['id'],
                array_filter($typeMeta, fn (array $type) => $type['contributes'])
            ));

        // Weights only apply on the term report: a weighted mean across the
        // contributing types that carry a weight; types without one keep the
        // latest-mark behaviour for the merge below.
        $weights = $query->examTypeId === null
            ? array_filter(array_map(
                fn (array $type) => $type['weight'],
                array_filter($typeMeta, fn (array $type) => $type['contributes'] && $type['weight'] !== null)
            ), fn ($weight) => $weight !== null)
            : [];
        if (count($weights) < 2) {
            $weights = [];
        }

        $subjects = $this->subjectRows(
            $query->schoolId,
            $this->subjectIdsFor($exams, $marks, $mainTypeIds),
            $this->submissionLabels($query->schoolId, $exams, $mainTypeIds),
        );
        $students = $this->buildStudents($policy, $query->schoolId, $roster, $chosen, $mainTypeIds, $subjects, $weights);
        $summary = $this->summary($subjects, $students);

        $sittings = [];
        foreach ($this->sittingPlans($exams, $policy->splitMidTermByMonth, $preferences) as $plan) {
            if ($query->examTypeId === null && ! ($typeMeta[$plan['typeId']]['included'] ?? true)) {
                continue;
            }
            $bucketExams = $exams->whereIn('id', $plan['examIds'])->values();
            $bucketMarks = $marks->whereIn('exam_id', $plan['examIds'])->values();
            $bucketChosen = $this->choose($bucketExams, $bucketMarks);
            $sittingSubjects = $this->subjectRows(
                $query->schoolId,
                $this->subjectIdsFor($bucketExams, $bucketMarks, [$plan['typeId']]),
                $this->submissionLabels($query->schoolId, $bucketExams, [$plan['typeId']]),
            );
            $sittingStudents = $this->buildStudents(
                $policy,
                $query->schoolId,
                $roster,
                $bucketChosen,
                [$plan['typeId']],
                $sittingSubjects,
            );
            $sittings[] = new ResultSitting(
                $plan['typeId'],
                $plan['name'],
                $plan['code'],
                $plan['contributes'],
                $sittingSubjects,
                $this->visible($sittingStudents, $query),
                $plan['periodKey'],
                $plan['periodLabel'],
            );
        }

        return new ResultSheet(
            $query->schoolId,
            $query->sectionId,
            $query->academicTermId,
            $query->standardLinkId,
            $query->examTypeId,
            $subjects,
            $this->visible($students, $query),
            $sittings,
            $summary,
        );
    }

    private function sectionBelongsToSchool(ResultsQuery $query): bool
    {
        return Section::query()
            ->where('id', $query->sectionId)
            ->where('school_id', $query->schoolId)
            ->exists();
    }

    private function streamBelongsToClass(ResultsQuery $query): bool
    {
        if ($query->standardLinkId === null) {
            return true;
        }

        return StandardLink::query()
            ->where('id', $query->standardLinkId)
            ->where('school_id', $query->schoolId)
            ->where('section_id', $query->sectionId)
            ->exists();
    }

    /**
     * @param array<int|string, mixed>|null $preferences
     * @return array<int, array{id: int, name: string, code: string, contributes: bool}>
     */
    private function typeMeta(Collection $exams, ?array $preferences, ?array $composition = null): array
    {
        $types = [];
        foreach ($exams as $exam) {
            $type = $exam->examType;
            if ($type === null) {
                continue;
            }
            $settings = $composition[(string) $type->id] ?? $composition[(int) $type->id] ?? [];
            $types[(int) $type->id] = [
                'id' => (int) $type->id,
                'name' => (string) $type->name,
                'code' => (string) ($type->code ?? ''),
                'contributes' => $this->examTypeContribution->contributes($type, $preferences),
                'included' => ($settings['include'] ?? true) === false ? false : (bool) ($settings['include'] ?? true),
                'weight' => isset($settings['weight']) && is_numeric($settings['weight']) && (float) $settings['weight'] > 0
                    ? (float) $settings['weight']
                    : null,
            ];
        }
        ksort($types);

        return $types;
    }

    /**
     * One plan per exam type, unless the policy splits non-contributing
     * types by the calendar month of scheduled_at. An undated exam in that
     * mode stays its own sitting. Contributing types stay one sitting, and
     * the main total is unaffected.
     *
     * @param array<int|string, mixed>|null $preferences
     * @return list<array{typeId: int, name: string, code: string, contributes: bool, periodKey: string, periodLabel: string, examIds: list<int>}>
     */
    private function sittingPlans(Collection $exams, bool $splitMidTermByMonth, ?array $preferences): array
    {
        $plans = [];
        foreach ($exams as $exam) {
            $type = $exam->examType;
            if ($type === null) {
                continue;
            }
            $contributes = $this->examTypeContribution->contributes($type, $preferences);
            $scheduled = $exam->scheduled_at;
            if ($splitMidTermByMonth && ! $contributes && $scheduled !== null) {
                $key = 'type:'.$type->id.'|month:'.$scheduled->format('Y-m');
                $periodKey = $scheduled->format('Y-m');
                $periodLabel = strtoupper($scheduled->format('F'));
            } elseif ($splitMidTermByMonth && ! $contributes) {
                $key = 'type:'.$type->id.'|exam:'.$exam->id;
                $periodKey = '';
                $periodLabel = '';
            } else {
                $key = 'type:'.$type->id;
                $periodKey = '';
                $periodLabel = '';
            }
            if (! isset($plans[$key])) {
                $plans[$key] = [
                    'typeId' => (int) $type->id,
                    'name' => (string) $type->name,
                    'code' => (string) ($type->code ?? ''),
                    'contributes' => $contributes,
                    'periodKey' => $periodKey,
                    'periodLabel' => $periodLabel,
                    'examIds' => [],
                    'sortExamId' => (int) $exam->id,
                ];
            }
            $plans[$key]['examIds'][] = (int) $exam->id;
            $plans[$key]['sortExamId'] = min($plans[$key]['sortExamId'], (int) $exam->id);
        }

        $list = array_values($plans);
        usort($list, function (array $left, array $right) {
            return [$left['typeId'], $left['periodKey'], $left['sortExamId']]
                <=> [$right['typeId'], $right['periodKey'], $right['sortExamId']];
        });

        return $list;
    }

    private function marksFor(int $schoolId, Collection $exams): Collection
    {
        $examIds = $exams->pluck('id');
        if ($examIds->isEmpty()) {
            return collect();
        }

        return Marks::query()
            ->where('school_id', $schoolId)
            ->whereIn('exam_id', $examIds)
            ->get();
    }

    /**
     * @return array<int, array<int, array<int, array{mark: float, grade: string, scheduled: int, examId: int}>>>
     */
    private function choose(Collection $exams, Collection $marks): array
    {
        $examsById = $exams->keyBy('id');
        $chosen = [];
        foreach ($marks as $mark) {
            $exam = $examsById->get($mark->exam_id);
            if ($exam === null || $exam->exam_type_id === null) {
                continue;
            }
            $candidate = [
                'mark' => (float) $mark->marks,
                'grade' => trim((string) $mark->grade),
                'scheduled' => $exam->scheduled_at?->getTimestamp() ?? 0,
                'examId' => (int) $exam->id,
            ];
            $typeId = (int) $exam->exam_type_id;
            $studentId = (int) $mark->student_id;
            $subjectId = (int) $mark->subject_id;
            $current = $chosen[$typeId][$studentId][$subjectId] ?? null;
            if ($current === null || $this->isLater($candidate, $current)) {
                $chosen[$typeId][$studentId][$subjectId] = $candidate;
            }
        }

        return $chosen;
    }

    /**
     * @param array{mark: float, grade: string, scheduled: int, examId: int} $candidate
     * @param array{mark: float, grade: string, scheduled: int, examId: int} $current
     */
    private function isLater(array $candidate, array $current): bool
    {
        if ($candidate['scheduled'] !== $current['scheduled']) {
            return $candidate['scheduled'] > $current['scheduled'];
        }

        return $candidate['examId'] > $current['examId'];
    }

    /**
     * @param list<int> $typeIds
     * @return list<int>
     */
    private function subjectIdsFor(Collection $exams, Collection $marks, array $typeIds): array
    {
        $typeSet = array_fill_keys($typeIds, true);
        $examTypes = [];
        $ids = [];
        foreach ($exams as $exam) {
            $typeId = (int) $exam->exam_type_id;
            $examTypes[(int) $exam->id] = $typeId;
            if (! isset($typeSet[$typeId]) || ! $exam->subject_id) {
                continue;
            }
            $ids[(int) $exam->subject_id] = true;
        }
        foreach ($marks as $mark) {
            $typeId = $examTypes[(int) $mark->exam_id] ?? null;
            if ($typeId === null || ! isset($typeSet[$typeId])) {
                continue;
            }
            $ids[(int) $mark->subject_id] = true;
        }

        return array_keys($ids);
    }

    /**
     * @param list<int> $subjectIds
     * @param array<int, string> $submissionLabels
     * @return list<array{id: int, name: string, code: string, submission: string}>
     */
    private function subjectRows(int $schoolId, array $subjectIds, array $submissionLabels = []): array
    {
        if ($subjectIds === []) {
            return [];
        }

        return Subject::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (Subject $subject) => [
                'id' => (int) $subject->id,
                'name' => (string) $subject->name,
                'code' => (string) ($subject->code ?? ''),
                'submission' => $submissionLabels[(int) $subject->id] ?? 'Not started',
            ])
            ->all();
    }

    /**
     * One label per subject from the submission row for these exams.
     * A later row for the same subject replaces an earlier one.
     *
     * @param list<int> $typeIds
     * @return array<int, string>
     */
    private function submissionLabels(int $schoolId, Collection $exams, array $typeIds): array
    {
        if ($typeIds === []) {
            return [];
        }

        $typeSet = array_fill_keys($typeIds, true);
        $examIds = [];
        foreach ($exams as $exam) {
            if (isset($typeSet[(int) $exam->exam_type_id])) {
                $examIds[] = (int) $exam->id;
            }
        }
        if ($examIds === []) {
            return [];
        }

        $labels = [];
        $rows = ExamMarksSubmission::query()
            ->where('school_id', $schoolId)
            ->whereIn('exam_id', $examIds)
            ->orderBy('id')
            ->get();
        foreach ($rows as $row) {
            $labels[(int) $row->subject_id] = $row->reviewLabel();
        }

        return $labels;
    }

    /**
     * Learners enrolled in this class for the term's year, plus learners
     * who have a mark on this sitting even after an in-place promotion
     * moved their academic row to another class.
     *
     * @return Collection<int, array{user: User, linkId: int, standardId: int, stream: string}>
     */
    private function roster(ResultsQuery $query, int $academicYearId, Collection $exams): Collection
    {
        $current = StudentAcademic::query()
            ->where('school_id', $query->schoolId)
            ->where('academic_year_id', $academicYearId)
            ->whereHas('standardLink', function ($builder) use ($query) {
                $builder->where('school_id', $query->schoolId)
                    ->where('section_id', $query->sectionId);
            })
            ->whereHas('user', function ($builder) {
                $builder->where('status', 'active')->where('usergroup_id', 6);
            })
            ->with(['user', 'standardLink'])
            ->orderBy('user_id')
            ->orderBy('id')
            ->get()
            ->unique('user_id');

        $entries = [];
        foreach ($current as $academic) {
            $user = $academic->user;
            $link = $academic->standardLink;
            if ($user === null || $link === null) {
                continue;
            }
            $entries[(int) $user->id] = $this->rosterEntry(
                $user,
                (int) $link->id,
                (int) $link->standard_id,
                (string) ($link->stream ?? ''),
            );
        }

        $examIds = $exams->pluck('id')->filter()->values();
        if ($examIds->isEmpty()) {
            ksort($entries);

            return collect(array_values($entries));
        }

        $sitterIds = Marks::query()
            ->where('school_id', $query->schoolId)
            ->whereIn('exam_id', $examIds)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => isset($entries[$id]))
            ->values();

        if ($sitterIds->isEmpty()) {
            ksort($entries);

            return collect(array_values($entries));
        }

        $users = User::query()
            ->where('school_id', $query->schoolId)
            ->where('status', 'active')
            ->where('usergroup_id', 6)
            ->whereIn('id', $sitterIds)
            ->get()
            ->keyBy('id');

        $links = StandardLink::query()
            ->where('school_id', $query->schoolId)
            ->where('section_id', $query->sectionId)
            ->orderBy('id')
            ->get();

        $academics = StudentAcademic::query()
            ->where('school_id', $query->schoolId)
            ->whereIn('user_id', $users->keys())
            ->with('standardLink')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

        foreach ($users as $user) {
            $rows = $academics->get($user->id) ?? collect();
            $onClass = $rows->first(function ($row) use ($query) {
                $link = $row->standardLink;

                return $link !== null
                    && (int) $link->school_id === $query->schoolId
                    && (int) $link->section_id === $query->sectionId;
            });
            if ($onClass !== null && $onClass->standardLink !== null) {
                $link = $onClass->standardLink;
                $entries[(int) $user->id] = $this->rosterEntry(
                    $user,
                    (int) $link->id,
                    (int) $link->standard_id,
                    (string) ($link->stream ?? ''),
                );
                continue;
            }
            if ($links->count() === 1) {
                $link = $links->first();
                $entries[(int) $user->id] = $this->rosterEntry(
                    $user,
                    (int) $link->id,
                    (int) $link->standard_id,
                    (string) ($link->stream ?? ''),
                );
                continue;
            }
            $entries[(int) $user->id] = $this->rosterEntry(
                $user,
                0,
                (int) ($links->first()?->standard_id ?? 0),
                '',
            );
        }

        ksort($entries);

        return collect(array_values($entries));
    }

    /**
     * @return array{user: User, linkId: int, standardId: int, stream: string}
     */
    private function rosterEntry(User $user, int $linkId, int $standardId, string $stream): array
    {
        return [
            'user' => $user,
            'linkId' => $linkId,
            'standardId' => $standardId,
            'stream' => $stream,
        ];
    }

    /**
     * @param array<int, array<int, array<int, array{mark: float, grade: string, scheduled: int, examId: int}>>> $chosen
     * @param list<int> $typeIds
     * @param list<array{id: int, name: string, code: string, submission: string}> $subjects
     * @return list<ResultStudent>
     */
    private function buildStudents(ResultsPolicy $policy, int $schoolId, Collection $roster, array $chosen, array $typeIds, array $subjects, array $weights = []): array
    {
        $drafts = [];
        foreach ($roster as $entry) {
            $user = $entry['user'];
            $studentId = (int) $user->id;
            $standardId = (int) $entry['standardId'];
            $cells = [];
            foreach ($subjects as $subject) {
                $choice = $this->merged($chosen, $typeIds, $studentId, (int) $subject['id'], $weights);
                $cells[(int) $subject['id']] = $this->cell($policy, $schoolId, $standardId, (int) $subject['id'], $choice);
            }
            [$total, $counted, $average, $aggregate, $division, $displayTotal, $displayAverage] = $this->totals($policy, $cells);
            $drafts[] = [
                'studentId' => $studentId,
                'name' => (string) $user->name,
                'linkId' => (int) $entry['linkId'],
                'stream' => (string) $entry['stream'],
                'standardId' => $standardId,
                'cells' => $cells,
                'total' => $total,
                'counted' => $counted,
                'average' => $average,
                'aggregate' => $aggregate,
                'division' => $division,
                'displayTotal' => $displayTotal,
                'displayAverage' => $displayAverage,
                'key' => $this->rankKey($policy, $total, $average, $aggregate),
            ];
        }

        $positions = $this->positions($policy, $drafts);
        $students = [];
        foreach ($drafts as $draft) {
            $id = $draft['studentId'];
            $students[] = new ResultStudent(
                $id,
                $draft['name'],
                $draft['linkId'],
                $draft['stream'],
                $draft['standardId'],
                $draft['cells'],
                $draft['total'],
                $draft['counted'],
                $draft['average'],
                $draft['aggregate'],
                $draft['division'],
                $positions['class'][$id]['position'],
                $positions['stream'][$id]['position'],
                $positions['class'][$id]['outOf'],
                $positions['stream'][$id]['outOf'],
                $draft['displayTotal'],
                $draft['displayAverage'],
            );
        }

        return $students;
    }

    /**
     * One merged mark per subject: the latest contributing mark, or a
     * weighted mean when the school's report composition configures weights
     * for at least two contributing types (types without marks are dropped
     * from both numerator and denominator).
     *
     * @param array<int, array<int, array<int, array{mark: float, grade: string, scheduled: int, examId: int}>>> $chosen
     * @param list<int> $typeIds
     * @param array<int, float> $weights
     * @return array{mark: float, grade: string, scheduled: int, examId: int}|null
     */
    private function merged(array $chosen, array $typeIds, int $studentId, int $subjectId, array $weights = []): ?array
    {
        $best = null;
        foreach ($typeIds as $typeId) {
            $row = $chosen[$typeId][$studentId][$subjectId] ?? null;
            if ($row !== null && ($best === null || $this->isLater($row, $best))) {
                $best = $row;
            }
        }

        if ($weights === []) {
            return $best;
        }

        if ($best === null) {
            return null;
        }

        // Absent and descriptive (grade-only) results keep the latest
        // behaviour; a weighted mean only makes sense for numeric marks.
        if ($best['grade'] !== '') {
            return $best;
        }

        $sum = 0.0;
        $weightSum = 0.0;
        foreach ($typeIds as $typeId) {
            $row = $chosen[$typeId][$studentId][$subjectId] ?? null;
            $weight = (float) ($weights[$typeId] ?? 0);
            if ($row === null || $weight <= 0 || $row['mark'] === null) {
                continue;
            }
            $sum += (float) $row['mark'] * $weight;
            $weightSum += $weight;
        }

        if ($weightSum <= 0) {
            return $best;
        }

        return [
            'mark' => round($sum / $weightSum, 2),
            'grade' => '',
            'scheduled' => $best['scheduled'],
            'examId' => $best['examId'],
        ];
    }

    /**
     * @param array{mark: float, grade: string, scheduled: int, examId: int}|null $choice
     */
    private function cell(ResultsPolicy $policy, int $schoolId, int $standardId, int $subjectId, ?array $choice): ResultCell
    {
        if ($choice === null) {
            return new ResultCell($subjectId, null, null, null, ResultCell::MISSING);
        }
        if ($policy->isAbsentGrade($choice['grade'])) {
            $label = $choice['grade'] === '' ? null : $choice['grade'];

            return new ResultCell($subjectId, null, $label, null, ResultCell::ABSENT);
        }

        $mark = $choice['mark'];

        return new ResultCell(
            $subjectId,
            $mark,
            $policy->gradingScale->labelFor($schoolId, $standardId, $mark),
            $policy->gradingScale->pointsFor($schoolId, $standardId, $mark),
            ResultCell::MARKED,
        );
    }

    /**
     * @param array<int, ResultCell> $cells
     * @return array{0: ?float, 1: int, 2: ?float, 3: ?int, 4: ?string, 5: ?float, 6: ?float}
     */
    private function totals(ResultsPolicy $policy, array $cells): array
    {
        $total = 0.0;
        $counted = 0;
        $points = 0;
        $pointsSeen = false;
        foreach ($cells as $cell) {
            if ($cell->status !== ResultCell::MARKED || $cell->mark === null) {
                continue;
            }
            $total += $cell->mark;
            $counted++;
            if ($cell->points !== null) {
                $points += $cell->points;
                $pointsSeen = true;
            }
        }
        if ($counted === 0) {
            return [null, 0, null, null, null, null, null];
        }

        $average = $total / $counted;
        $aggregate = $pointsSeen ? $points : null;

        return [
            $total,
            $counted,
            $average,
            $aggregate,
            $policy->divisionRule->division($aggregate, $counted),
            $policy->round($total),
            $policy->round($average),
        ];
    }

    private function rankKey(ResultsPolicy $policy, ?float $total, ?float $average, ?int $aggregate): ?float
    {
        return match ($policy->rankingBasis) {
            ResultsPolicy::BASIS_AGGREGATE => $aggregate === null ? null : (float) $aggregate,
            ResultsPolicy::BASIS_AVERAGE => $average,
            default => $total,
        };
    }

    /**
     * @param list<array{studentId: int, linkId: int, key: ?float}> $drafts
     * @return array{class: array<int, array{position: ?int, outOf: int}>, stream: array<int, array{position: ?int, outOf: int}>}
     */
    private function positions(ResultsPolicy $policy, array $drafts): array
    {
        $ranked = array_values(array_filter($drafts, fn (array $draft) => $draft['key'] !== null));
        usort($ranked, function (array $left, array $right) use ($policy) {
            $compared = $left['key'] <=> $right['key'];
            if ($policy->higherIsBetter) {
                $compared = -$compared;
            }
            if ($compared !== 0) {
                return $compared;
            }

            return $left['studentId'] <=> $right['studentId'];
        });

        $classPositions = $this->assignShared($ranked);
        $classOutOf = count($ranked);
        $byStream = [];
        foreach ($ranked as $entry) {
            $byStream[$entry['linkId']][] = $entry;
        }

        $streamPositions = [];
        $streamOutOf = [];
        foreach ($byStream as $linkId => $group) {
            foreach ($this->assignShared($group) as $studentId => $position) {
                $streamPositions[$studentId] = $position;
            }
            $streamOutOf[$linkId] = count($group);
        }

        $class = [];
        $stream = [];
        foreach ($drafts as $draft) {
            $id = $draft['studentId'];
            $class[$id] = [
                'position' => $classPositions[$id] ?? null,
                'outOf' => $classOutOf,
            ];
            $stream[$id] = [
                'position' => $streamPositions[$id] ?? null,
                'outOf' => $streamOutOf[$draft['linkId']] ?? 0,
            ];
        }

        return ['class' => $class, 'stream' => $stream];
    }

    /**
     * Shared places: 1, 2, 2, 4.
     *
     * @param list<array{studentId: int, key: ?float}> $sorted
     * @return array<int, int>
     */
    private function assignShared(array $sorted): array
    {
        $map = [];
        $position = 0;
        $index = 0;
        $previous = null;
        foreach ($sorted as $entry) {
            $index++;
            if ($previous === null || $entry['key'] !== $previous) {
                $position = $index;
                $previous = $entry['key'];
            }
            $map[$entry['studentId']] = $position;
        }

        return $map;
    }

    /**
     * @param list<array{id: int, name: string, code: string, submission: string}> $subjects
     * @param list<ResultStudent> $students
     */
    private function summary(array $subjects, array $students): ResultSummary
    {
        $expected = count($students);
        $rows = [];
        foreach ($subjects as $subject) {
            $sum = 0.0;
            $marked = 0;
            foreach ($students as $student) {
                $cell = $student->cell($subject['id']);
                if ($cell !== null && $cell->status === ResultCell::MARKED && $cell->mark !== null) {
                    $sum += $cell->mark;
                    $marked++;
                }
            }
            $rows[] = [
                'subjectId' => $subject['id'],
                'name' => $subject['name'],
                'average' => $marked > 0 ? $sum / $marked : null,
                'markedCount' => $marked,
                'expectedCount' => $expected,
            ];
        }

        return new ResultSummary(
            $rows,
            count(array_filter($rows, fn (array $row) => $row['markedCount'] > 0)),
            count($subjects),
        );
    }

    /**
     * @param list<ResultStudent> $students
     * @return list<ResultStudent>
     */
    private function visible(array $students, ResultsQuery $query): array
    {
        return array_values(array_filter($students, function (ResultStudent $student) use ($query) {
            if ($query->standardLinkId !== null && $student->standardLinkId !== $query->standardLinkId) {
                return false;
            }
            if ($query->studentId !== null && $student->studentId !== $query->studentId) {
                return false;
            }

            return true;
        }));
    }
}
