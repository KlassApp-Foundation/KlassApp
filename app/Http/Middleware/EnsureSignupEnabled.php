<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSignupEnabled
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('app.signup_enabled')) {
            abort(404);
        }

        return $next($request);
    }
}
