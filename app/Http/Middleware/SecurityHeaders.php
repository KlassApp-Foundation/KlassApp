<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Adds HSTS (secure production requests only), Referrer-Policy,
     * Permissions-Policy and a report-only Content-Security-Policy.
     * A header that is already present (for example set upstream) is
     * left untouched, so running twice changes nothing.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security_headers.enabled')) {
            return $response;
        }

        if ($request->isSecure() && app()->environment('production')) {
            $this->setIfMissing($response, 'Strict-Transport-Security', config('security_headers.hsts'));
        }

        $this->setIfMissing($response, 'Referrer-Policy', config('security_headers.referrer_policy'));
        $this->setIfMissing($response, 'Permissions-Policy', config('security_headers.permissions_policy'));

        if (! $response->headers->has('Content-Security-Policy')) {
            $this->setIfMissing(
                $response,
                'Content-Security-Policy-Report-Only',
                config('security_headers.csp_report_only').'; report-uri '.route('security.csp-report', [], false)
            );
        }

        return $response;
    }

    private function setIfMissing(Response $response, string $name, ?string $value): void
    {
        if ($value !== null && $value !== '' && ! $response->headers->has($name)) {
            $response->headers->set($name, $value);
        }
    }
}
