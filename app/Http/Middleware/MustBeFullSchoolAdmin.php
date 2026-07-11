<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Middleware that only allows full school admins (usergroup 1, 2, 3)
 * and blocks school subadmins (usergroup 4). Used for Settings routes
 * where owner-level access is required.
 */
class MustBeFullSchoolAdmin
{
    public function handle($request, Closure $next)
    {
        $ug = \Auth::user()->usergroup_id;

        if (in_array($ug, [1, 2, 3])) {
            return $next($request);
        }

        abort(404);
    }
}
