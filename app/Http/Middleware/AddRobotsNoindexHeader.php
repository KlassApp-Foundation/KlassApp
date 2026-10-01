<?php

namespace App\Http\Middleware;

use Closure;

class AddRobotsNoindexHeader
{
    /**
     * Stamp X-Robots-Tag: noindex, nofollow on every outgoing response while
     * ROBOTS_NOINDEX is enabled. Global placement (app/Http/Kernel.php) is
     * what makes it also cover exception-handler responses (404/500).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (config('app.robots_noindex')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
