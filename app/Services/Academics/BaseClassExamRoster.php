<?php

namespace App\Services\Academics;

use App\Models\Academics\Exam;
use App\Models\Section;
use App\Models\Teacherlink;
use App\Models\User;
use App\Services\ClassStructureService;
use App\Services\ExamAuthorization;
use Illuminate\Support\Collection;

/**
 * Marks entry for an exam that sits on a base class.
 *
 * Students live on the class's streams (name-encoded sections such as
 * "Primary One E"). A stream's subject teacher sees only their stream.
 * The class head, from ExamAuthorization::sectionIdsForClassTeacher, sees
 * every stream. An exam that is itself on a stream keeps the old roster.
 */
class BaseClassExamRoster
{
    /** @var array<int, array{base: Section, streams: Collection<int, Section>}> */
    private array $structure = [];

    public function __construct(
        private ClassStructureService $classes,
        private ExamAuthorization $authorization,
    ) {
    }

    public function coversStreams(Exam $exam): bool
    {
        $structure = $this->structure($exam);

        return $structure !== null && $structure['streams']->isNotEmpty();
    }

    public function assertCanEnter(User $actor, Exam $exam): void
    {
        if (! $this->coversStreams($exam)) {
            return;
        }

        if ((int) $actor->school_id !== (int) $exam->school_id) {
            abort(403, 'You are not authorized to enter marks for this exam.');
        }

        if ($this->authorization->managesMarksForSchool($actor, (int) $exam->school_id)) {
            return;
        }

        if ($this->isClassHead($actor, $exam)) {
            return;
        }

        if ($this->taughtStreamSectionIds($actor, $exam) !== []) {
            return;
        }

        if ($this->authorization->canActOnExam($actor, $exam)) {
            return;
        }

        abort(403, 'You are not authorized to enter marks for this exam.');
    }

    /**
     * @param  list<int>  $studentIds
     */
    public function assertStudentsAllowed(User $actor, Exam $exam, array $studentIds): void
    {
        if (! $this->coversStreams($exam) || $studentIds === []) {
            return;
        }

        $allowed = $this->students($exam, $actor)->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($studentIds as $studentId) {
            if (! in_array((int) $studentId, $allowed, true)) {
                abort(403, 'You cannot enter marks for a student outside your stream.');
            }
        }
    }

    public function assertStudentAllowed(User $actor, Exam $exam, int $studentId): void
    {
        $this->assertStudentsAllowed($actor, $exam, [$studentId]);
    }

    /**
     * @return Collection<int, User>
     */
    public function students(Exam $exam, User $actor, ?int $streamSectionId = null): Collection
    {
        if (! $this->coversStreams($exam)) {
            return $this->legacyRoster($exam, (int) $exam->school_id);
        }

        $this->assertCanEnter($actor, $exam);

        $structure = $this->structure($exam);
        $allowed = $this->allowedSectionIds($actor, $exam, $structure);
        $visibleStreams = array_values(array_intersect(
            $structure['streams']->pluck('id')->map(fn ($id) => (int) $id)->all(),
            $allowed,
        ));

        if ($streamSectionId !== null && ! in_array($streamSectionId, $visibleStreams, true)) {
            abort(403, 'You cannot view that stream.');
        }

        $sectionIds = $streamSectionId !== null ? [$streamSectionId] : $allowed;

        return $this->studentsOnSections($exam, $sectionIds, (string) $structure['base']->name);
    }

    /**
     * Stream options the actor may filter by.
     *
     * @return list<array{id: int, label: string}>
     */
    public function streamOptions(Exam $exam, User $actor): array
    {
        if (! $this->coversStreams($exam)) {
            return [];
        }

        $structure = $this->structure($exam);
        $allowed = $this->allowedSectionIds($actor, $exam, $structure);
        $baseName = (string) $structure['base']->name;

        return $structure['streams']
            ->filter(fn (Section $section) => in_array((int) $section->id, $allowed, true))
            ->map(fn (Section $section) => [
                'id' => (int) $section->id,
                'label' => $this->streamLabel($baseName, (string) $section->name),
            ])
            ->values()
            ->all();
    }

    /**
     * Every student on the base class and its streams, for a template or a
     * class-wide save. Does not narrow to one teacher's stream.
     *
     * @return Collection<int, User>
     */
    public function everyStudent(Exam $exam): Collection
    {
        if (! $this->coversStreams($exam)) {
            return $this->legacyRoster($exam, (int) $exam->school_id);
        }

        $structure = $this->structure($exam);
        $sectionIds = array_values(array_unique(array_merge(
            [(int) $structure['base']->id],
            $structure['streams']->pluck('id')->map(fn ($id) => (int) $id)->all(),
        )));

        return $this->studentsOnSections($exam, $sectionIds, (string) $structure['base']->name);
    }

    /**
     * The roster a stream-level exam already used: this standard and this section only.
     *
     * @return Collection<int, User>
     */
    public function legacyRoster(Exam $exam, int $schoolId): Collection
    {
        return User::with(['school', 'marks', 'studentAcademic.standardLink'])
            ->where('usergroup_id', 6)
            ->where('school_id', $schoolId)
            ->whereHas('studentAcademic', function ($q) use ($exam) {
                $q->whereHas('standardLink', function ($q2) use ($exam) {
                    $q2->where('standard_id', $exam->standard_id)
                        ->where('section_id', $exam->section_id);
                });
            })
            ->get();
    }

    /**
     * @param  array{base: Section, streams: Collection<int, Section>}|null  $structure
     * @return list<int>
     */
    private function allowedSectionIds(User $actor, Exam $exam, ?array $structure): array
    {
        if ($structure === null) {
            return [(int) $exam->section_id];
        }

        $all = array_values(array_unique(array_merge(
            [(int) $structure['base']->id],
            $structure['streams']->pluck('id')->map(fn ($id) => (int) $id)->all(),
        )));

        if ($this->isClassHead($actor, $exam)) {
            return $all;
        }

        $taught = $this->taughtStreamSectionIds($actor, $exam);
        if ($taught !== []) {
            return $taught;
        }

        return $all;
    }

    private function isClassHead(User $actor, Exam $exam): bool
    {
        $headed = $this->authorization->sectionIdsForClassTeacher(
            $actor,
            (int) $exam->school_id,
            (int) $exam->academic_year_id,
        );

        return in_array((int) $exam->section_id, $headed, true);
    }

    /**
     * @return list<int>
     */
    private function taughtStreamSectionIds(User $actor, Exam $exam): array
    {
        $structure = $this->structure($exam);
        if ($structure === null) {
            return [];
        }

        $streamIds = $structure['streams']->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($streamIds === []) {
            return [];
        }

        $exam->loadMissing('subject');
        $subjectName = mb_strtolower(trim((string) ($exam->subject->name ?? '')));

        return Teacherlink::query()
            ->where('school_id', $exam->school_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('teacher_id', $actor->id)
            ->whereHas('standardLink', function ($q) use ($exam, $streamIds) {
                $q->where('school_id', $exam->school_id)
                    ->where('academic_year_id', $exam->academic_year_id)
                    ->whereIn('section_id', $streamIds);
            })
            ->get()
            ->filter(function (Teacherlink $link) use ($exam, $subjectName) {
                if ((int) $link->subject_id === (int) $exam->subject_id) {
                    return true;
                }

                $name = mb_strtolower(trim((string) ($link->subject->name ?? '')));

                return $subjectName !== '' && $name === $subjectName;
            })
            ->map(fn (Teacherlink $link) => (int) $link->standardLink->section_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $sectionIds
     * @return Collection<int, User>
     */
    private function studentsOnSections(Exam $exam, array $sectionIds, string $baseName): Collection
    {
        if ($sectionIds === []) {
            return collect();
        }

        $students = User::with(['studentAcademic.standardLink.section'])
            ->where('usergroup_id', 6)
            ->where('school_id', $exam->school_id)
            ->where('status', 'active')
            ->whereHas('studentAcademic', function ($q) use ($exam, $sectionIds) {
                $q->where('school_id', $exam->school_id)
                    ->where('academic_year_id', $exam->academic_year_id)
                    ->whereHas('standardLink', function ($q2) use ($exam, $sectionIds) {
                        $q2->where('school_id', $exam->school_id)
                            ->where('academic_year_id', $exam->academic_year_id)
                            ->where('standard_id', $exam->standard_id)
                            ->whereIn('section_id', $sectionIds)
                            ->where(function ($status) {
                                $status->where('status', 1)->orWhere('status', '1');
                            });
                    });
            })
            ->orderBy('name')
            ->get();

        return $students->each(function (User $student) use ($exam, $baseName) {
            $academic = $student->studentAcademic->first(function ($row) use ($exam) {
                return (int) $row->academic_year_id === (int) $exam->academic_year_id
                    && (int) $row->school_id === (int) $exam->school_id
                    && (int) ($row->standardLink->standard_id ?? 0) === (int) $exam->standard_id;
            });
            $sectionName = (string) ($academic?->standardLink?->section?->name ?? '');
            $student->setAttribute('stream_label', $this->streamLabel($baseName, $sectionName));
            $student->setAttribute('stream_section_id', (int) ($academic?->standardLink?->section_id ?? 0));
        })->sortBy([
            fn (User $student) => mb_strtolower((string) $student->stream_label),
            fn (User $student) => mb_strtolower((string) $student->name),
        ])->values();
    }

    private function streamLabel(string $baseName, string $sectionName): string
    {
        $prefix = trim($baseName).' ';
        if ($prefix !== ' ' && str_starts_with($sectionName, $prefix)) {
            return trim(substr($sectionName, strlen($prefix)));
        }

        return $sectionName;
    }

    /**
     * @return array{base: Section, streams: Collection<int, Section>}|null
     */
    private function structure(Exam $exam): ?array
    {
        $key = (int) $exam->id;
        if (array_key_exists($key, $this->structure)) {
            return $this->structure[$key];
        }

        $section = $exam->relationLoaded('section') ? $exam->section : Section::query()->find($exam->section_id);
        if (! $section || (int) $section->school_id !== (int) $exam->school_id) {
            return $this->structure[$key] = null;
        }

        if (! $this->classes->isBaseSection($section)) {
            return $this->structure[$key] = null;
        }

        $baseName = trim((string) $section->name);
        $prefix = $baseName.' ';
        $streams = Section::query()
            ->where('school_id', $exam->school_id)
            ->where(function ($q) {
                $q->where('status', 1)->orWhere('status', '1');
            })
            ->orderBy('name')
            ->get()
            ->filter(fn (Section $row) => $prefix !== ' ' && str_starts_with((string) $row->name, $prefix))
            ->values();

        return $this->structure[$key] = [
            'base' => $section,
            'streams' => $streams,
        ];
    }
}
