<?php

namespace App\Http\Middleware;

use App\Support\SchoolAdmin;
use Closure;

/**
 * Settings and the other owner screens.
 *
 * Site admin, school admin, and the previous full-admin group may open
 * every settings screen. A head teacher (usergroup 4) has those same
 * powers inside the school they are signed into. Controllers still check
 * school_id.
 */
class MustBeFullSchoolAdmin
{
    public function handle($request, Closure $next)
    {
        $user = \Auth::user();
        $ug = (int) $user->usergroup_id;

        if (in_array($ug, [1, 2, 3], true) || SchoolAdmin::is($user)) {
            return $next($request);
        }

        abort(404);
    }
}
