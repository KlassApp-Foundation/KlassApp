<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public demo admin cannot change its own profile or password (config/public_demo.php
 * refuses the per-role pages the same way). The shared account pages are refused here, with
 * the same "coming soon" page, so no config pattern has to be kept in step with them.
 */
class RefuseForPublicDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (PublicDemoLockdown::actor() === null) {
            return $next($request);
        }

        $message = "This isn't available in the public demo yet. It's coming soon.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return response()->view('demo.public-demo-soon', ['message' => $message]);
    }
}
