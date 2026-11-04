<?php

namespace App\Services;

use App\Models\StandardLink;
use App\Models\User;

/**
 * Who may download a whole-class report card PDF.
 *
 * School admins (and deputy admins, who use the same admin pages) may
 * download any class in their own school. A class head may download a class
 * or stream only when ExamAuthorization::sectionIdsForClassTeacher includes
 * that section. Everyone else is forbidden. Another school's class is not
 * found.
 */
class ClassReportCardAuthorization
{
    public function __construct(private ExamAuthorization $exams)
    {
    }

    public function authorize(User $actor, StandardLink $stdLink): void
    {
        if ((int) $actor->school_id !== (int) $stdLink->school_id) {
            abort(404);
        }

        if ($this->isSchoolAdmin($actor)) {
            return;
        }

        $sectionIds = $this->exams->sectionIdsForClassTeacher(
            $actor,
            (int) $actor->school_id,
            (int) $stdLink->academic_year_id
        );

        if (! in_array((int) $stdLink->section_id, $sectionIds, true)) {
            abort(403);
        }
    }

    public function isSchoolAdmin(User $actor): bool
    {
        return in_array((int) $actor->usergroup_id, [3, 4], true);
    }
}
