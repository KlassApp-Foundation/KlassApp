<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
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
                },
            ])
            ->orderBy('sections.name');

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
            ->where('standards_link.status', 1)
            ->orderBy('standards_link.stream');
        $this->withScopedStreamRelations($query, $schoolId, $academicYearId);

        if (! $this->isAdmin($actor)) {
            $this->applyTeacherStreamVisibility($query, $actor, $schoolId, $academicYearId);
        }

        return $query->get();
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
    public function studentsForStream(StandardLink $stream, int $schoolId, ?User $actor = null): Builder
    {
        if ((int) $stream->school_id !== $schoolId || (int) $stream->status !== 1) {
            throw new NotFoundHttpException('Stream not found.');
        }

        $section = Section::query()
            ->where('school_id', $schoolId)
            ->findOrFail($stream->section_id);

        if ($actor !== null) {
            $this->assertActorCanAccessSchool($actor, $schoolId);

            if (! $this->isAdmin($actor) && ! $this->visibleStreams($actor, $section, (int) $stream->academic_year_id)->contains('id', $stream->id)) {
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

        if ($actor !== null && $this->isSubjectTeacherOnly($actor, $stream, $section)) {
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

        if ((int) $actor->school_id !== $schoolId || ! in_array((int) $actor->usergroup_id, [3, 5], true)) {
            throw new HttpException(403, 'You are not authorized for this school.');
        }
    }

    private function isAdmin(User $actor): bool
    {
        return in_array((int) $actor->usergroup_id, [1, 3], true);
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
