<?php

namespace App\Services;

use App\Models\Academics\Exam;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\User;

/**
 * Additive exam authorization for teachers.
 *
 * Subject teachers keep access via exam.teacher_id.
 * Class teachers (standards_link / section class_teacher_id) may act on
 * any exam in their class without replacing subject-teacher access.
 */
class ExamAuthorization
{
    public function canActOnExam(User $actor, Exam $exam): bool
    {
        if ((int) $actor->school_id !== (int) $exam->school_id) {
            return false;
        }

        if ((int) $exam->teacher_id === (int) $actor->id) {
            return true;
        }

        return $this->isClassTeacherForExam($actor, $exam);
    }

    public function isClassTeacherForExam(User $actor, Exam $exam): bool
    {
        if ((int) $actor->school_id !== (int) $exam->school_id) {
            return false;
        }

        if ((int) $actor->usergroup_id !== 5) {
            return false;
        }

        $streamMatch = StandardLink::query()
            ->where('school_id', $exam->school_id)
            ->where('section_id', $exam->section_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('class_teacher_id', $actor->id)
            ->where(function ($q) {
                $q->where('status', 1)->orWhere('status', '1');
            })
            ->exists();

        if ($streamMatch) {
            return true;
        }

        return Section::query()
            ->whereKey($exam->section_id)
            ->where('school_id', $exam->school_id)
            ->where('class_teacher_id', $actor->id)
            ->where('status', 1)
            ->exists();
    }

    /**
     * Section IDs where the actor is class teacher for the academic year.
     *
     * @return list<int>
     */
    public function sectionIdsForClassTeacher(User $actor, int $schoolId, int $academicYearId): array
    {
        if ((int) $actor->school_id !== $schoolId || (int) $actor->usergroup_id !== 5) {
            return [];
        }

        $fromStreams = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('class_teacher_id', $actor->id)
            ->where(function ($q) {
                $q->where('status', 1)->orWhere('status', '1');
            })
            ->pluck('section_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $fromSections = Section::query()
            ->where('school_id', $schoolId)
            ->where('class_teacher_id', $actor->id)
            ->where('status', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($fromStreams, $fromSections)));
    }

    public function authorizeOrAbort(User $actor, Exam $exam, string $message = 'Not Authorized'): void
    {
        if (! $this->canActOnExam($actor, $exam)) {
            abort(403, $message);
        }
    }
}
