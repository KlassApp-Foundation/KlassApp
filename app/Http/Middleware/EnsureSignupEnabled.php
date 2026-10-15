<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Sign-up switch (SIGNUP_ENABLED, read once as config klassapp.signup_enabled).
 * While sign-up is closed, sign-up requests go to the early access page and
 * nothing is created. No 404: closed sign-up always shows the early access page.
 */
class EnsureSignupEnabled
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('klassapp.signup_enabled')) {
            return redirect()->route('early-access.show');
        }

        return $next($request);
    }
}
