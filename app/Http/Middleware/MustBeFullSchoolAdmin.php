<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Settings stay with a full school admin (usergroup 1, 2, 3).
 *
 * Exam type settings are the end-of-term report mix. A head teacher
 * (usergroup 4) may open and save that screen for the school they are
 * signed into. Every other settings screen stays closed to them.
 */
class MustBeFullSchoolAdmin
{
    public function handle($request, Closure $next)
    {
        $ug = (int) \Auth::user()->usergroup_id;

        if (in_array($ug, [1, 2, 3], true)) {
            return $next($request);
        }

        if ($ug === 4 && $request->routeIs(
            'admin.settings.exam-types',
            'admin.settings.exam-types.update',
            'admin.settings.exam-types.store',
            'admin.settings.exam-types.profile',
            'admin.settings.exam-types.bands',
        )) {
            return $next($request);
        }

        abort(404);
    }
}
