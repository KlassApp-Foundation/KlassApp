<?php

namespace App\Http\Middleware;

use App\Support\SchoolAdmin;
use Closure;

/**
 * Settings and the other owner screens. Site admin, school admin, and
 * head teacher (usergroup 4). A head teacher is already limited to their
 * own school by the controllers behind this middleware.
 */
class MustBeFullSchoolAdmin
{
    public function handle($request, Closure $next)
    {
        if (SchoolAdmin::is(\Auth::user())) {
            return $next($request);
        }

        abort(404);
    }
}
