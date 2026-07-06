<?php

namespace App\Http\Middleware;

use Closure;

class MustHaveDesignation
{
    public function handle($request, Closure $next, $designation)
    {
        if (!$request->user() || !$request->user()->hasDesignation($designation)) {
            return response('Forbidden', 403);
        }

        return $next($request);
    }
}
