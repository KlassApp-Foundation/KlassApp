<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    /**
     * Logs a CSP violation report. Only the directive and the origins are
     * kept: no query strings, paths of the blocked resource, IP addresses,
     * user agents or user ids are logged.
     */
    public function __invoke(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $report = is_array($payload) ? ($payload['csp-report'] ?? $payload[0]['body'] ?? $payload) : [];

        if (is_array($report)) {
            Log::warning('csp.violation', [
                'directive' => $this->clip($report['effective-directive'] ?? $report['effectiveDirective'] ?? $report['violated-directive'] ?? null),
                'blocked' => $this->origin($report['blocked-uri'] ?? $report['blockedURL'] ?? null),
                'page' => $this->path($report['document-uri'] ?? $report['documentURL'] ?? null),
            ]);
        }

        return response()->noContent();
    }

    private function clip($value): ?string
    {
        return is_string($value) ? substr($value, 0, 64) : null;
    }

    private function origin($uri): ?string
    {
        if (! is_string($uri)) {
            return null;
        }
        $parts = parse_url($uri);
        if (! isset($parts['scheme'], $parts['host'])) {
            return $this->clip($uri); // keywords such as "inline" or "eval"
        }

        return $parts['scheme'].'://'.$parts['host'];
    }

    private function path($uri): ?string
    {
        if (! is_string($uri)) {
            return null;
        }
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        // Replace numeric ids so record ids are not logged.
        return substr(preg_replace('#/\d+(?=/|$)#', '/{id}', $path), 0, 128);
    }
}
