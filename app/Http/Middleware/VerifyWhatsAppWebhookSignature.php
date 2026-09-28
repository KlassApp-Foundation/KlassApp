<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies Meta's X-Hub-Signature-256 header on WhatsApp webhook POSTs.
 *
 * GET requests (Meta's verify-token handshake) are passed through unchanged.
 * Verification is controlled by services.whatsapp.verify_signature (default on).
 * With the flag on and no app secret configured, the request is refused and an
 * error is logged (fail closed).
 */
class VerifyWhatsAppWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('get')) {
            return $next($request);
        }

        if (! config('services.whatsapp.verify_signature', true)) {
            return $next($request);
        }

        $secret = (string) config('services.whatsapp.app_secret');

        if ($secret === '') {
            Log::error('WhatsApp webhook refused: app secret not configured while signature verification is enabled.');

            return response('Forbidden', 403);
        }

        $provided = (string) $request->header('X-Hub-Signature-256');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            Log::warning('WhatsApp webhook refused: missing or invalid X-Hub-Signature-256.');

            return response('Forbidden', 403);
        }

        return $next($request);
    }
}
