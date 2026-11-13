<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Support\ClassListOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RosterScopeService
{
    /**
     * @return Builder<Section>
     */
    public function visibleSections(User $actor, int $schoolId, int $academicYearId): Builder
    {
        $this->assertActorCanAccessSchool($actor, $schoolId);
        $this->assertAcademicYearBelongsToSchool($academicYearId, $schoolId);

        $query = Section::query()
            ->where('sections.school_id', $schoolId)
            ->where('sections.status', 1)
            ->with([
                'classTeacher' => function ($teacherQuery) use ($schoolId): void {
                    $teacherQuery
                        ->where('users.school_id', $schoolId)
                        ->where('users.usergroup_id', 5);
                },
                'standardLink' => function ($streamQuery) use ($actor, $schoolId, $academicYearId): void {
                    $streamQuery
                        ->where('standards_link.school_id', $schoolId)
                        ->where('standards_link.academic_year_id', $academicYearId)
                        ->where('standards_link.status', 1);
                    $this->withScopedStreamRelations($streamQuery, $schoolId, $academicYearId);

                    if (! $this->isAdmin($actor)) {
                        $this->applyTeacherStreamVisibility($streamQuery->getQuery(), $actor, $schoolId, $academicYearId);
                    }

                    $streamQuery
                        ->orderBy(
                            \App\Models\Standard::query()
                                ->select('standards.order')
                                ->whereColumn('standards.id', 'standards_link.standard_id')
                                ->limit(1)
                        )
                        ->orderBy('standards_link.stream')
                        ->orderBy('standards_link.id');
                },
            ]);
        ClassListOrder::orderSections($query);

        if ($this->isAdmin($actor)) {
            return $query;
        }

        return $query->where(function (Builder $sectionQuery) use ($actor, $schoolId, $academicYearId): void {
            $sectionQuery
                ->where('sections.class_teacher_id', $actor->id)
                ->orWhereHas('standardLink', function (Builder $streamQuery) use ($actor, $schoolId, $academicYearId): void {
                    $streamQuery
                        ->where('standards_link.school_id', $schoolId)
                        ->where('standards_link.academic_year_id', $academicYearId)
                        ->where('standards_link.status', 1);
                    $this->applyTeacherStreamVisibility($streamQuery, $actor, $schoolId, $academicYearId);
                });
        });
    }

    public function visibleStreams(User $actor, Section $section, int $academicYearId): Collection
    {
        $schoolId = (int) $section->school_id;
        $this->assertActorCanAccessSchool($actor, $schoolId);
        $this->assertAcademicYearBelongsToSchool($academicYearId, $schoolId);

        if ((int) $section->school_id !== $schoolId) {
            throw new NotFoundHttpException('Class not found.');
        }

        if ((int) $section->status !== 1) {
            throw new NotFoundHttpException('Class not found.');
        }

        $query = StandardLink::query()
            ->where('standards_link.school_id', $schoolId)
            ->where('standards_link.section_id', $section->id)
            ->where('standards_link.academic_year_id', $academicYearId)
            ->where('standards_link.status', 1);
        ClassListOrder::orderLinks($query);
        $this->withScopedStreamRelations($query, $schoolId, $academicYearId);

        if (! $this->isAdmin($actor)) {
            $this->applyTeacherStreamVisibility($query, $actor, $schoolId, $academicYearId);
        }

        return $query->get();
    }

    /**
     * Whether the actor may open this student's teacher profile surfaces.
     *
     * Teachers: same school + current-year enrollment on a stream visible via
     * {@see applyTeacherStreamVisibility()} (stream CT, section CT fallback, or
     * current-year Teacherlink). Admins: same-school (superadmin: any school).
     * Hard deny otherwise — no partial/limited-info fallback.
     */
    public function actorCanAccessStudent(User $actor, User $student, ?int $academicYearId = null): bool
    {
        if ((int) $student->usergroup_id !== 6) {
            return false;
        }

        $schoolId = (int) $student->school_id;

        try {
            $this->assertActorCanAccessSchool($actor, $schoolId);
        } catch (HttpException) {
            return false;
        }

        if ($this->isAdmin($actor)) {
            return true;
        }

        if ($academicYearId === null) {
            $year = SiteHelper::getAcademicYear($schoolId);
            if ($year === null) {
                return false;
            }
            $academicYearId = (int) $year->id;
        } else {
            try {
                $this->assertAcademicYearBelongsToSchool($academicYearId, $schoolId);
            } catch (NotFoundHttpException) {
                return false;
            }
        }

        return StudentAcademic::query()
            ->where('student_academics.school_id', $schoolId)
            ->where('student_academics.academic_year_id', $academicYearId)
            ->where('student_academics.user_id', $student->id)
            ->whereHas('standardLink', function (Builder $streamQuery) use ($actor, $schoolId, $academicYearId): void {
                $streamQuery
                    ->where('standards_link.school_id', $schoolId)
                    ->where('standards_link.academic_year_id', $academicYearId)
                    ->where('standards_link.status', 1);
                $this->applyTeacherStreamVisibility($streamQuery, $actor, $schoolId, $academicYearId);
            })
            ->exists();
    }

    /**
     * The class page for a base class that has streams (name-encoded sections such as
     * "Primary Four E" under "Primary Four", each with its own standards_link row).
     * Returns null when the requested section has no streams, so the caller keeps the
     * ordinary one-section behaviour.
     *
     * Admins see every stream. A class head (ClassStructureService::headedBaseIds) sees
     * every stream of the class they head. Any other teacher sees only the streams they
     * teach (applyTeacherStreamVisibility). Anyone with nothing visible gets a 404.
     * Everything is scoped by school_id and the chosen academic year.
     *
     * @return array{section: Section, streams: Collection<int, StandardLink>, head: bool, labels: array<int, string>}|null
     */
    public function classPage(User $actor, int $sectionId, int $academicYearId): ?array
    {
        $schoolId = (int) $actor->school_id;
        $this->assertActorCanAccessSchool($actor, $schoolId);
        $this->assertAcademicYearBelongsToSchool($academicYearId, $schoolId);

        $requested = Section::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->find($sectionId);
        if ($requested === null) {
            return null;
        }

        $structure = app(ClassStructureService::class);
        $base = $structure->resolveBaseSection($requested);
        $sectionIds = $structure->classSectionIds($base);
        if (count($sectionIds) < 2) {
            return null;
        }

        $head = $this->isAdmin($actor)
            || in_array((int) $base->id, $structure->headedBaseIds($schoolId, (int) $actor->id), true);

        $query = StandardLink::query()
            ->where('standards_link.school_id', $schoolId)
            ->whereIn('standards_link.section_id', $sectionIds)
            ->where('standards_link.academic_year_id', $academicYearId)
            ->where('standards_link.status', 1);
        ClassListOrder::orderLinks($query);
        $this->withScopedStreamRelations($query, $schoolId, $academicYearId);
        $query->with('section');
        if (! $head) {
            $this->applyTeacherStreamVisibility($query, $actor, $schoolId, $academicYearId);
        }
        $links = $query->get();

        // The undivided base link normally carries nobody on a streamed class; hide it then.
        $baseLink = $links->first(fn (StandardLink $l) => (int) $l->section_id === (int) $base->id);
        if ($baseLink !== null && $links->count() > 1) {
            $enrolled = StudentAcademic::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->where('standardLink_id', $baseLink->id)
                ->exists();
            if (! $enrolled) {
                $links = $links->reject(fn (StandardLink $l) => (int) $l->id === (int) $baseLink->id)->values();
            }
        }

        if ($links->isEmpty()) {
            throw new NotFoundHttpException('Class not found.');
        }

        $labels = [];
        $prefix = trim((string) $base->name).' ';
        foreach ($links as $link) {
            $name = trim((string) ($link->section?->name ?? ''));
            $labels[(int) $link->id] = (int) $link->section_id !== (int) $base->id && str_starts_with($name, $prefix)
                ? trim(substr($name, strlen($prefix)))
                : ((string) ($link->stream ?: 'Main stream'));
        }

        return ['section' => $base, 'streams' => $links, 'head' => $head, 'labels' => $labels];
    }

    public function effectiveClassTeacher(Section $section, ?StandardLink $stream): ?User
    {
        if ($stream !== null) {
            if (
                (int) $stream->section_id !== (int) $section->id
                || (int) $stream->school_id !== (int) $section->school_id
            ) {
                throw new NotFoundHttpException('Stream not found for this class.');
            }

            if ($stream->class_teacher_id !== null) {
                return User::query()
                    ->whereKey($stream->class_teacher_id)
                    ->where('users.school_id', $section->school_id)
                    ->where('users.usergroup_id', 5)
                    ->first();
            }
        }

        if ($section->class_teacher_id === null) {
            return null;
        }

        return User::query()
            ->whereKey($section->class_teacher_id)
            ->where('users.school_id', $section->school_id)
            ->where('users.usergroup_id', 5)
            ->first();
    }

    /**
     * @return Builder<StudentAcademic>
     */
    public function studentsForStream(StandardLink $stream, int $schoolId, ?User $actor = null, bool $classScoped = false): Builder
    {
        if ((int) $stream->school_id !== $schoolId || (int) $stream->status !== 1) {
            throw new NotFoundHttpException('Stream not found.');
        }

        $section = Section::query()
            ->where('school_id', $schoolId)
            ->findOrFail($stream->section_id);

        if ($actor !== null) {
            $this->assertActorCanAccessSchool($actor, $schoolId);

            // $classScoped: the caller already limited the streams with classPage() (a class head
            // opening a stream of their own class), so the per-stream teacher check does not apply.
            if (! $classScoped && ! $this->isAdmin($actor) && ! $this->visibleStreams($actor, $section, (int) $stream->academic_year_id)->contains('id', $stream->id)) {
                throw new HttpException(403, 'You are not authorized to view this stream.');
            }
        }

        $query = StudentAcademic::query()
            ->where('student_academics.school_id', $schoolId)
            ->where('student_academics.academic_year_id', $stream->academic_year_id)
            ->where('student_academics.standardLink_id', $stream->id)
            ->whereHas('user', function (Builder $userQuery) use ($schoolId): void {
                $userQuery
                    ->where('users.school_id', $schoolId)
                    ->where('users.usergroup_id', 6);
            });

        if ($actor !== null && ! $classScoped && $this->isSubjectTeacherOnly($actor, $stream, $section)) {
            $query
                ->select([
                    'student_academics.id',
                    'student_academics.school_id',
                    'student_academics.academic_year_id',
                    'student_academics.user_id',
                    'student_academics.standardLink_id',
                    'student_academics.academic_status',
                ])
                ->with([
                    'user' => function ($userQuery) use ($schoolId): void {
                        $userQuery
                            ->without(['userprofile', 'members', 'children', 'parents'])
                            ->select(['users.id', 'users.school_id', 'users.usergroup_id', 'users.name', 'users.status'])
                            ->where('users.school_id', $schoolId);
                    },
                ]);
        } else {
            $query->with([
                'user' => function ($userQuery) use ($schoolId): void {
                    $userQuery
                        ->without(['userprofile', 'members', 'children', 'parents'])
                        ->where('users.school_id', $schoolId);
                },
            ]);
        }

        return $query->orderBy('student_academics.id');
    }

    /**
     * Streams this teacher may treat as theirs this year: class-teacher
     * assignment on the stream, a subject allocation, or the section's
     * class teacher when the stream has none. Same rule as
     * {@see applyTeacherStreamVisibility()}.
     *
     * @return list<int>
     */
    public function assignedStreamIds(User $actor, int $schoolId, int $academicYearId): array
    {
        $query = StandardLink::query()
            ->where('standards_link.school_id', $schoolId)
            ->where('standards_link.academic_year_id', $academicYearId)
            ->where(function ($status): void {
                $status->where('standards_link.status', 1)
                    ->orWhere('standards_link.status', '1');
            });
        $this->applyTeacherStreamVisibility($query, $actor, $schoolId, $academicYearId);

        return $query->pluck('standards_link.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function applyTeacherStreamVisibility(
        Builder $query,
        User $actor,
        int $schoolId,
        int $academicYearId
    ): void {
        $query->where(function (Builder $streamQuery) use ($actor, $schoolId, $academicYearId): void {
            $streamQuery
                ->where('standards_link.class_teacher_id', $actor->id)
                ->orWhereHas('teacherlink', function (Builder $teacherLinkQuery) use ($actor, $schoolId, $academicYearId): void {
                    $teacherLinkQuery
                        ->where('class_teacher_links.school_id', $schoolId)
                        ->where('class_teacher_links.academic_year_id', $academicYearId)
                        ->where('class_teacher_links.teacher_id', $actor->id);
                })
                ->orWhere(function (Builder $fallbackQuery) use ($actor): void {
                    $fallbackQuery
                        ->whereNull('standards_link.class_teacher_id')
                        ->whereHas('section', function (Builder $sectionQuery) use ($actor): void {
                            $sectionQuery->where('sections.class_teacher_id', $actor->id);
                        });
                });
        });
    }

    private function withScopedStreamRelations($query, int $schoolId, int $academicYearId): void
    {
        $query->with([
            'teacher' => function ($teacherQuery) use ($schoolId): void {
                $teacherQuery
                    ->where('users.school_id', $schoolId)
                    ->where('users.usergroup_id', 5);
            },
            'standard' => function ($standardQuery) use ($schoolId): void {
                $standardQuery->where('standards.school_id', $schoolId);
            },
            'teacherlink' => function ($teacherLinkQuery) use ($schoolId, $academicYearId): void {
                $teacherLinkQuery
                    ->where('class_teacher_links.school_id', $schoolId)
                    ->where('class_teacher_links.academic_year_id', $academicYearId)
                    ->with([
                        'teacher' => function ($teacherQuery) use ($schoolId): void {
                            $teacherQuery
                                ->where('users.school_id', $schoolId)
                                ->where('users.usergroup_id', 5);
                        },
                        'subject' => function ($subjectQuery) use ($schoolId): void {
                            $subjectQuery->where('subjects.school_id', $schoolId);
                        },
                    ]);
            },
        ]);
    }

    private function assertAcademicYearBelongsToSchool(int $academicYearId, int $schoolId): void
    {
        if (! AcademicYear::query()->whereKey($academicYearId)->where('school_id', $schoolId)->exists()) {
            throw new NotFoundHttpException('Academic year not found.');
        }
    }

    private function assertActorCanAccessSchool(User $actor, int $schoolId): void
    {
        if ($this->isSuperAdmin($actor)) {
            return;
        }

        if ((int) $actor->school_id !== $schoolId || ! in_array((int) $actor->usergroup_id, [3, 4, 5], true)) {
            throw new HttpException(403, 'You are not authorized for this school.');
        }
    }

    private function isAdmin(User $actor): bool
    {
        return \App\Support\SchoolAdmin::is($actor);
    }

    private function isSuperAdmin(User $actor): bool
    {
        return (int) $actor->usergroup_id === 1;
    }

    private function isSubjectTeacherOnly(User $actor, StandardLink $stream, Section $section): bool
    {
        if ($this->isAdmin($actor)) {
            return false;
        }

        if ((int) $stream->class_teacher_id === (int) $actor->id) {
            return false;
        }

        if ($stream->class_teacher_id === null && (int) $section->class_teacher_id === (int) $actor->id) {
            return false;
        }

        return true;
    }
}
