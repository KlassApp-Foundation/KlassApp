<?php

namespace App\Http\Middleware;

use App\Enums\ToshiScope;
use App\Services\Toshi\ToshiAvailabilityGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce ToshiAvailabilityGate + ToshiScope::Platform for platform ops routes.
 * Stricter than MustBeSuperadmin alone — respects TOSHI_PLATFORM_GATE_ENABLED / allowlist.
 */
class EnsurePlatformToshiAccess
{
    public function __construct(private readonly ToshiAvailabilityGate $gate) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->gate->allows($user, ToshiScope::Platform)) {
            abort(403, 'Platform Toshi access denied.');
        }

        return $next($request);
    }
}
