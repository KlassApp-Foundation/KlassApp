<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * HMAC authentication middleware for internal WhatsApp service calls.
 *
 * n8n, Typebot, and Flowise sign their requests with a shared secret.
 * This middleware verifies the HMAC-SHA256 signature before allowing access.
 *
 * Usage: Add 'whatsapp.hmac' to routes/api.php whatsapp group.
 */
class WhatsAppHmac
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-WhatsApp-Signature');
        $timestamp = $request->header('X-WhatsApp-Timestamp');

        if (!$signature || !$timestamp) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'Missing authentication headers',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Reject requests older than 5 minutes (prevent replay attacks)
        if (time() - (int) $timestamp > 300) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'Request timestamp expired',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $secret = config('services.whatsapp.hmac_secret', env('WHATSAPP_HMAC_SECRET'));

        if (!$secret) {
            \Log::error('WhatsApp HMAC: No secret configured. Set WHATSAPP_HMAC_SECRET in .env');
            return response()->json([
                'error'   => 'Internal Server Error',
                'message' => 'Authentication not configured',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Build the payload that was signed: timestamp + '.' + request body
        $payload = $timestamp . '.' . $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'Invalid signature',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
