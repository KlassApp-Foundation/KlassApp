<?php

namespace App\Services\StudentProfile;

use App\Models\User;

/**
 * Who may act on a student's profile: a school admin or sub-admin, for a student of their own
 * school. Same group list as the `schooladmin` route middleware minus the platform admin, who
 * works through impersonation and so holds a school of their own.
 */
trait AuthorizesStudentProfileActions
{
    protected function authorizeProfileAction(User $actor, User $student): void
    {
        $isAdmin = in_array((int) $actor->usergroup_id, [3, 4], true);
        $sameSchool = $actor->school_id !== null && (int) $actor->school_id === (int) $student->school_id;

        if (! $isAdmin || ! $sameSchool || (int) $student->usergroup_id !== 6 || $student->deleted_at !== null) {
            abort(403, 'Not authorized for this student.');
        }
    }
}
