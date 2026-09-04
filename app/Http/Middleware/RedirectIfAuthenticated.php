<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            $ug = (int) Auth::guard($guard)->user()->usergroup_id;
            return match ($ug) {
                1 => redirect('/superadmin/dashboard'),
                4 => redirect('/subadmin/dashboard'),
                5 => redirect('/teacher/dashboard'),
                6 => redirect('/student/dashboard'),
                7 => redirect('/parent/dashboard'),
                8 => redirect('/library/dashboard'),
                10 => redirect('/receptionist/dashboard'),
                11 => redirect('/accountant/dashboard'),
                default => redirect('/admin/dashboard'),
            };
        }

        return $next($request);
    }
}
