<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Google\GoogleApiException;
use App\Services\Google\GoogleConnectionService;
use App\Services\Google\GoogleConnectorCatalog;
use App\Services\Google\GoogleOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * One Google account per user. Sign-in stays on GoogleAuthController.
 * This redirect is GOOGLE_CONNECTOR_REDIRECT_URI and does not use Socialite.
 */
class GoogleConnectController extends Controller
{
    public function connect(string $connector, GoogleConnectorCatalog $catalog, GoogleOAuthService $oauth, GoogleConnectionService $connections)
    {
        $user = $this->member($connections);

        if (! $catalog->has($connector) || ! $connections->schoolAllows((int) $user->school_id, $connector)) {
            abort(404);
        }

        if (! $oauth->isConfigured()) {
            return redirect()
                ->route('google.mine')
                ->with('failmessage', 'Google connectors are not configured on this instance.');
        }

        return redirect()->away($oauth->authorizationUrl($user, $connector));
    }

    public function callback(Request $request, GoogleConnectionService $connections, GoogleConnectorCatalog $catalog)
    {
        $user = $this->member($connections);
        $pending = $request->session()->pull('google_connector_oauth');

        if (! is_array($pending) || empty($pending['state']) || ! hash_equals((string) $pending['state'], (string) $request->query('state', ''))) {
            return redirect()
                ->route('google.mine')
                ->with('failmessage', 'Google connection could not be verified. Please try again.');
        }

        if ((int) $pending['school_id'] !== (int) $user->school_id || (int) $pending['user_id'] !== (int) $user->id) {
            abort(404);
        }

        $connector = (string) ($pending['connector'] ?? '');

        if (! $catalog->has($connector) || ! $connections->schoolAllows((int) $user->school_id, $connector)) {
            abort(404);
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('google.mine')
                ->with('failmessage', 'Google connection was cancelled.');
        }

        try {
            $connections->connectFromCode($user, $connector, (string) $request->query('code', ''));
        } catch (GoogleApiException $e) {
            Log::warning('Google connector callback failed', [
                'connector' => $connector,
                'user_id' => $user->id,
                'status' => $e->status,
                'error' => $e->googleError,
                'exception' => $e::class,
            ]);

            return redirect()
                ->route('google.mine')
                ->with('failmessage', 'Google connection failed. Please try again.');
        }

        return redirect()
            ->route('google.mine')
            ->with('successmessage', $catalog->get($connector)['label'].' is connected.');
    }

    public function disconnect(string $connector, GoogleConnectionService $connections, GoogleConnectorCatalog $catalog)
    {
        $user = $this->member($connections);

        if (! $catalog->has($connector)) {
            abort(404);
        }

        $connections->disconnect($user, $connector);

        return redirect()
            ->route('google.mine')
            ->with('successmessage', $catalog->get($connector)['label'].' disconnected.');
    }

    private function member(GoogleConnectionService $connections): User
    {
        $user = Auth::user();

        if (! $connections->isSchoolMember($user)) {
            abort(404);
        }

        return $user;
    }
}
