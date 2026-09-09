<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Allow SiteAdmin (1), SchoolAdmin (3), and SchoolSubadmin (4) through /admin/*
 * feature routes. SchoolSubadmin reuses the admin UI; Settings stay blocked by
 * MustBeFullSchoolAdmin (see routes/setting.php).
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

        // SiteAdmin, SchoolAdmin, SchoolSubadmin — admin modules (minus Settings for ug4).
        if (in_array($ug, [1, 3, 4], true)) {
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
