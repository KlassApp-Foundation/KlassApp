<?php

namespace App\View\Composers;

use App\Services\Schools\SchoolIdentity;
use Illuminate\View\View;

/**
 * Gives every report card template two things without touching the card's own code:
 *  - $logoPath, resolved from the disk the crest was saved to (when none is found the value the card
 *    already worked out is left alone, and the template falls back to the school's initials);
 *  - $headTeacherName, the head teacher's name from school settings (null when not set).
 * The school comes from the view's own data ($school, or $schoolId), never from the signed-in user, so
 * a PDF built in a queued job gets the right school.
 */
class ReportIdentityComposer
{
    public function compose(View $view): void
    {
        $data = $view->getData();
        $schoolId = (int) (($data['school']->id ?? null) ?: ($data['schoolId'] ?? 0));
        if ($schoolId <= 0) {
            return;
        }

        $identity = SchoolIdentity::forCard($schoolId);
        $view->with('logoPath', $identity['crest_path'] ?? ($data['logoPath'] ?? null));
        $view->with('headTeacherName', $identity['head_teacher_name']);
    }
}
