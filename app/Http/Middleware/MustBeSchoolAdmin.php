<?php

namespace App\Http\Middleware;

use App\Support\SchoolAdmin;
use Closure;

/**
 * Allow a site admin, school admin, or head teacher through /admin/*.
 * The same people pass Settings via MustBeFullSchoolAdmin.
 */
class MustBeSchoolAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $ug = (int) \Auth::user()->usergroup_id;

        if (SchoolAdmin::is(\Auth::user())) {
            return $next($request);
        }

        if ($ug === 5) {
            return redirect('/teacher/dashboard');
        }

        if ($ug === 6) {
            return redirect('/student/dashboard');
        }

        if ($ug === 8) {
            return redirect('/library/dashboard');
        }

        if ($ug === 9) {
            return redirect('/alumni/dashboard');
        }

        if ($ug === 10) {
            return redirect('/receptionist/dashboard');
        }

        if ($ug === 11) {
            return redirect('/accountant/dashboard');
        }

        abort(404);
    }
}
