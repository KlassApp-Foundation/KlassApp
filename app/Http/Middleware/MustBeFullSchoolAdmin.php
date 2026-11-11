<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Settings stay with a full school admin.
 *
 * Usergroups 1, 3, and 4, and usergroup 2, may open them. A head teacher
 * manages the school they are signed into. Scope stays on school_id.
 */
class MustBeFullSchoolAdmin
{
    public function handle($request, Closure $next)
    {
        $user = \Auth::user();
        $ug = (int) $user->usergroup_id;

        if ($ug === 2 || \App\Support\SchoolAdmin::is($user)) {
            return $next($request);
        }

        abort(404);
    }
}
