<?php

namespace App\Services\Results;

use App\Contracts\Results\DivisionRule;
use App\Contracts\Results\GradingScale;
use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * Read-only grades, totals, positions and divisions for one class and term.
 *
 * Marks are grouped by exam type, term, class and subject. One exam row
 * per subject and one exam row carrying several subjects both work.
 * This service does not decide whether a parent may see the sheet.
 */
final class ResultsService
{
    public function __construct(
        private readonly GradingScale $gradingScale,
        private readonly DivisionRule $divisionRule,
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

        $typeMeta = $this->typeMeta($exams);
        $marks = $this->marksFor($query->schoolId, $exams);
        $chosen = $this->choose($exams, $marks);
        $roster = $this->roster($query, (int) $term->academic_year_id);

        $mainTypeIds = $query->examTypeId !== null
            ? (isset($typeMeta[$query->examTypeId]) ? [$query->examTypeId] : [])
            : array_values(array_map(
                fn (array $type) => $type['id'],
                array_filter($typeMeta, fn (array $type) => $type['contributes'])
            ));

        $subjects = $this->subjectRows($query->schoolId, $this->subjectIdsFor($exams, $marks, $mainTypeIds));
        $students = $this->buildStudents($policy, $query->schoolId, $roster, $chosen, $mainTypeIds, $subjects);
        $summary = $this->summary($subjects, $students);

        $sittings = [];
        foreach ($typeMeta as $type) {
            $sittingSubjects = $this->subjectRows(
                $query->schoolId,
                $this->subjectIdsFor($exams, $marks, [$type['id']])
            );
            $sittingStudents = $this->buildStudents(
                $policy,
                $query->schoolId,
                $roster,
                $chosen,
                [$type['id']],
                $sittingSubjects,
            );
            $sittings[] = new ResultSitting(
                $type['id'],
                $type['name'],
                $type['code'],
                $type['contributes'],
                $sittingSubjects,
                $this->visible($sittingStudents, $query),
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
     * @return array<int, array{id: int, name: string, code: string, contributes: bool}>
     */
    private function typeMeta(Collection $exams): array
    {
        $types = [];
        foreach ($exams as $exam) {
            $type = $exam->examType;
            if ($type === null) {
                continue;
            }
            $types[(int) $type->id] = [
                'id' => (int) $type->id,
                'name' => (string) $type->name,
                'code' => (string) ($type->code ?? ''),
                'contributes' => (bool) $type->contributes_to_report_total,
            ];
        }
        ksort($types);

        return $types;
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
     * @return list<array{id: int, name: string, code: string}>
     */
    private function subjectRows(int $schoolId, array $subjectIds): array
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
            ])
            ->all();
    }

    private function roster(ResultsQuery $query, int $academicYearId): Collection
    {
        return StudentAcademic::query()
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
            ->unique('user_id')
            ->values();
    }

    /**
     * @param array<int, array<int, array<int, array{mark: float, grade: string, scheduled: int, examId: int}>>> $chosen
     * @param list<int> $typeIds
     * @param list<array{id: int, name: string, code: string}> $subjects
     * @return list<ResultStudent>
     */
    private function buildStudents(ResultsPolicy $policy, int $schoolId, Collection $roster, array $chosen, array $typeIds, array $subjects): array
    {
        $drafts = [];
        foreach ($roster as $academic) {
            $user = $academic->user;
            $link = $academic->standardLink;
            if ($user === null || $link === null) {
                continue;
            }
            $studentId = (int) $user->id;
            $standardId = (int) $link->standard_id;
            $cells = [];
            foreach ($subjects as $subject) {
                $choice = $this->merged($chosen, $typeIds, $studentId, (int) $subject['id']);
                $cells[(int) $subject['id']] = $this->cell($policy, $schoolId, $standardId, (int) $subject['id'], $choice);
            }
            [$total, $counted, $average, $aggregate, $division, $displayTotal, $displayAverage] = $this->totals($policy, $cells);
            $drafts[] = [
                'studentId' => $studentId,
                'name' => (string) $user->name,
                'linkId' => (int) $link->id,
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
     * @param array<int, array<int, array<int, array{mark: float, grade: string, scheduled: int, examId: int}>>> $chosen
     * @param list<int> $typeIds
     * @return array{mark: float, grade: string, scheduled: int, examId: int}|null
     */
    private function merged(array $chosen, array $typeIds, int $studentId, int $subjectId): ?array
    {
        $best = null;
        foreach ($typeIds as $typeId) {
            $row = $chosen[$typeId][$studentId][$subjectId] ?? null;
            if ($row !== null && ($best === null || $this->isLater($row, $best))) {
                $best = $row;
            }
        }

        return $best;
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
     * @param list<array{id: int, name: string, code: string}> $subjects
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
