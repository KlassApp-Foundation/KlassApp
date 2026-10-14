<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Sends sign-up submissions to the early access page while sign-up is
 * closed (SIGNUP_ENABLED=false). Nothing is created.
 */
class EnsureSignupOpen
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('klassapp.signup_enabled')) {
            return redirect()->route('early-access.show');
        }

        return $next($request);
    }
}
