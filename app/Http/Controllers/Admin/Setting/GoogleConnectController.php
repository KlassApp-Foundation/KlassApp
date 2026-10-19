<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Services\Google\GoogleApiException;
use App\Services\Google\GoogleConnectionService;
use App\Services\Google\GoogleConnectorCatalog;
use App\Services\Google\GoogleOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * School Google account connect. One row per school, incremental scopes.
 *
 * Sign-in stays on GoogleAuthController / Socialite. This controller uses its
 * own redirect (GOOGLE_CONNECTOR_REDIRECT_URI) and never changes the login driver.
 */
class GoogleConnectController extends Controller
{
    public function connect(string $connector, GoogleConnectorCatalog $catalog, GoogleOAuthService $oauth)
    {
        $user = Auth::user();

        if ($user?->school_id === null || ! $catalog->has($connector)) {
            abort(404);
        }

        if (! $oauth->isConfigured()) {
            return redirect()
                ->route('admin.settings.integrations')
                ->with('failmessage', 'Google connectors are not configured on this instance.');
        }

        return redirect()->away($oauth->authorizationUrl($user, $connector));
    }

    public function callback(Request $request, GoogleConnectionService $connections, GoogleConnectorCatalog $catalog)
    {
        $user = Auth::user();

        if ($user?->school_id === null) {
            abort(404);
        }

        $pending = $request->session()->pull('google_connector_oauth');

        if (! is_array($pending) || empty($pending['state']) || ! hash_equals((string) $pending['state'], (string) $request->query('state', ''))) {
            return redirect()
                ->route('admin.settings.integrations')
                ->with('failmessage', 'Google connection could not be verified. Please try again.');
        }

        if ((int) $pending['school_id'] !== (int) $user->school_id || (int) $pending['user_id'] !== (int) $user->id) {
            abort(404);
        }

        $connector = (string) ($pending['connector'] ?? '');

        if (! $catalog->has($connector)) {
            abort(404);
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('admin.settings.integrations')
                ->with('failmessage', 'Google connection was cancelled.');
        }

        try {
            $connections->connectFromCode($user, $connector, (string) $request->query('code', ''));
        } catch (GoogleApiException $e) {
            Log::warning('Google connector callback failed', [
                'connector' => $connector,
                'status' => $e->status,
                'error' => $e->googleError,
                'exception' => $e::class,
            ]);

            return redirect()
                ->route('admin.settings.integrations')
                ->with('failmessage', 'Google connection failed. Please try again.');
        }

        return redirect()
            ->route('admin.settings.integrations')
            ->with('successmessage', $catalog->get($connector)['label'].' is connected for this school.');
    }

    public function disconnect(string $connector, GoogleConnectionService $connections, GoogleConnectorCatalog $catalog)
    {
        $user = Auth::user();

        if ($user?->school_id === null || ! $catalog->has($connector)) {
            abort(404);
        }

        $connections->disconnect($user, $connector);

        return redirect()
            ->route('admin.settings.integrations')
            ->with('successmessage', $catalog->get($connector)['label'].' disconnected.');
    }
}
