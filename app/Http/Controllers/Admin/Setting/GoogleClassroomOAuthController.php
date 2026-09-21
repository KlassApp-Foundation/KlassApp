<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\SchoolMcpConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * OAuth connect/disconnect for the Google Classroom connector (wave-1).
 *
 * Google's REST APIs are plain OAuth 2.0 authorization-code — NOT a remote
 * MCP server, so the vendor's Mcp::oAuthRoutesFor (which requires a WebClient
 * + RFC 7591-capable endpoints) does not apply. This is the GoogleAuthController
 * precedent (Socialite google driver) pointed at the Classroom connector's own
 * OAuth client (separate from the sign-in client: different scopes — a scope
 * mismatch on the shared client would break Google sign-in).
 *
 * Connect: GET mcp/google-classroom/connect (route registered in routes/ai.php)
 *   → Google consent (classroom.courses.readonly +
 *   classroom.coursework.students.readonly, access_type=offline for a
 *   refresh token) → callback upserts the school_mcp_connectors row.
 *
 * Disconnect: reuse IntegrationsController@disconnect (connector-type agnostic).
 *
 * Wave-1 has NO write tools, so write_mode stays 'deny' on every row and the
 * empty write_tools catalog means nothing can ever be classified as a write.
 */
class GoogleClassroomOAuthController extends Controller
{
    /**
     * Scopes are hardcoded (not env): wave-1 is fixed by the approved plan —
     * classroom.courses.readonly + classroom.coursework.students.readonly,
     * NO roster/email/guardian scopes. A scope change is a plan change.
     */
    public const SCOPES = [
        'https://www.googleapis.com/auth/classroom.courses.readonly',
        'https://www.googleapis.com/auth/classroom.coursework.students.readonly',
    ];

    public function connect()
    {
        $user = Auth::user();

        if ($user?->school_id === null) {
            return redirect('/dashboard');
        }

        if (blank(config('services.google_classroom.client_id'))) {
            // Same guard as the Slack connect button: without a first-party
            // client id the consent redirect would fail. Fail loudly but
            // gracefully instead of a raw 500 at Google.
            return redirect()
                ->route('admin.settings.integrations')
                ->with('failmessage', 'Google Classroom connector is not configured on this instance.');
        }

        return Socialite::driver('google')
            ->scopes(self::SCOPES)
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    public function callback(Request $request)
    {
        $user = Auth::user();

        if ($user?->school_id === null) {
            return redirect('/dashboard');
        }

        try {
            // NOT stateless: Google CSRF state must be verified (session-backed).
            $tokenResponse = Socialite::driver('google')->getAccessTokenResponse($request->input('code'));
        } catch (Throwable $e) {
            Log::error('Google Classroom connector OAuth failed', ['message' => $e->getMessage()]);

            return redirect()
                ->route('admin.settings.integrations')
                ->with('failmessage', 'Google Classroom connection failed. Please try again.');
        }

        $accessToken = $tokenResponse['access_token'] ?? null;
        $refreshToken = $tokenResponse['refresh_token'] ?? null;
        $expiresIn = $tokenResponse['expires_in'] ?? null;

        if ($accessToken === null) {
            return redirect()
                ->route('admin.settings.integrations')
                ->with('failmessage', 'Google Classroom connection failed (no access token).');
        }

        $expiresAt = $expiresIn !== null ? now()->addSeconds((int) $expiresIn) : null;

        SchoolMcpConnector::updateOrCreate(
            [
                'school_id' => $user->school_id,
                'connector_type' => 'google-classroom',
                // Google user tokens are per-account; use the school's row as
                // the single per-school identity (one Classroom connection
                // per school — the connecting admin's account is the token
                // owner, recorded via connected_by).
                'external_team_id' => 'google-user:'.$user->id,
            ],
            [
                'external_team_name' => $user->name ?? 'Google Classroom',
                'credentials' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => $tokenResponse['token_type'] ?? 'Bearer',
                    'scope' => $tokenResponse['scope'] ?? implode(' ', self::SCOPES),
                ],
                'token_expires_at' => $expiresAt,
                'auth_mode' => 'oauth_remote',
                'status' => 'active',
                'write_mode' => 'deny',
                'connected_by' => $user->id,
            ]
        );

        return redirect()
            ->route('admin.settings.integrations')
            ->with('successmessage', 'Google Classroom connected. Toshi can now list courses and coursework.');
    }
}
