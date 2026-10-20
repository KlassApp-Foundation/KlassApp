<?php

namespace App\Services\Google;

use App\Models\SchoolGoogleConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleOAuthService
{
    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.connector_redirect'));
    }

    public function authorizationUrl(User $user, string $connector): string
    {
        $state = Str::random(40);

        session([
            'google_connector_oauth' => [
                'state' => $state,
                'connector' => $connector,
                'school_id' => (int) $user->school_id,
                'user_id' => (int) $user->id,
            ],
        ]);

        $query = [
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.connector_redirect'),
            'response_type' => 'code',
            'scope' => implode(' ', app(GoogleConnectorCatalog::class)->authorizeScopes($connector)),
            'state' => $state,
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            // Forces a refresh token on first consent and on each added connector.
            'prompt' => 'consent',
        ];

        return config('services.google.authorize_url').'?'.http_build_query($query);
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->timeout(20)->post((string) config('services.google.token_url'), [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.connector_redirect'),
            'grant_type' => 'authorization_code',
        ]);

        return $this->tokenPayload($response->status(), $response->json());
    }

    /**
     * @return array<string, mixed>
     */
    public function userinfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(20)
            ->get((string) config('services.google.userinfo_url'));

        if (! $response->successful()) {
            throw new GoogleApiException($response->status(), 'Google account lookup failed.', $this->errorCode($response->json()));
        }

        return $response->json() ?? [];
    }

    public function refresh(SchoolGoogleConnection $connection): SchoolGoogleConnection
    {
        $refreshToken = $connection->refreshToken();

        if ($refreshToken === null) {
            $this->forgetTokens($connection, 'no_refresh_token');

            throw new GoogleAccessRevokedException(400, 'Google connection has no refresh token.', 'invalid_grant');
        }

        try {
            $response = Http::asForm()->timeout(20)->post((string) config('services.google.token_url'), [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Google token refresh failed', [
                'connection_id' => $connection->id,
                'exception' => $e::class,
            ]);

            throw new GoogleApiException(0, 'Google token refresh failed.');
        }

        $body = $response->json();
        $error = $this->errorCode($body);

        if ($response->status() === 400 && $error === 'invalid_grant') {
            $this->forgetTokens($connection, 'invalid_grant');

            throw new GoogleAccessRevokedException(400, 'Google access was revoked.', 'invalid_grant');
        }

        if (! $response->successful()) {
            throw new GoogleApiException($response->status(), 'Google token refresh failed.', $error);
        }

        $credentials = $connection->credentials ?? [];
        $credentials['access_token'] = $body['access_token'];
        $credentials['token_type'] = $body['token_type'] ?? 'Bearer';

        if (! empty($body['refresh_token'])) {
            $credentials['refresh_token'] = $body['refresh_token'];
        }

        $scopes = $this->parseScopes($body['scope'] ?? null);
        if ($scopes === []) {
            $scopes = $connection->granted_scopes ?? [];
        }

        $connection->fill([
            'credentials' => $credentials,
            'granted_scopes' => $scopes,
            'token_expires_at' => now()->addSeconds((int) ($body['expires_in'] ?? 3600)),
            'last_refreshed_at' => now(),
            'status' => 'active',
        ])->save();

        return $connection->refresh();
    }

    public function validConnection(SchoolGoogleConnection $connection): SchoolGoogleConnection
    {
        if (! $connection->isActive() || ($connection->accessToken() === null && $connection->refreshToken() === null)) {
            throw new GoogleAccessRevokedException(401, 'Google is not connected for this school.', 'not_connected');
        }

        if ($connection->accessToken() === null || $connection->isTokenExpired()) {
            return $this->refresh($connection);
        }

        return $connection;
    }

    public function revokeAndForget(SchoolGoogleConnection $connection): void
    {
        $token = $connection->refreshToken() ?: $connection->accessToken();

        if ($token !== null) {
            try {
                Http::asForm()->timeout(15)->post((string) config('services.google.revoke_url'), [
                    'token' => $token,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Google revoke request failed', [
                    'connection_id' => $connection->id,
                    'exception' => $e::class,
                ]);
            }
        }

        $this->forgetTokens($connection);
    }

    public function refreshExpiring(int $withinMinutes = 30): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        $connections = SchoolGoogleConnection::query()
            ->active()
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', now()->addMinutes($withinMinutes))
            ->get();

        $refreshed = 0;

        foreach ($connections as $connection) {
            try {
                $this->refresh($connection);
                $refreshed++;
            } catch (GoogleAccessRevokedException) {
                // Tokens already cleared.
            } catch (GoogleApiException $e) {
                Log::warning('Google scheduled refresh skipped', [
                    'connection_id' => $connection->id,
                    'status' => $e->status,
                    'error' => $e->googleError,
                ]);
            }
        }

        return $refreshed;
    }

    public function forgetTokens(SchoolGoogleConnection $connection, ?string $reason = null): void
    {
        $previousScopes = $connection->granted_scopes ?? [];

        $connection->forceFill([
            'credentials' => null,
            'granted_scopes' => [],
            'attention_scopes' => $reason ? array_values($previousScopes) : [],
            'token_expires_at' => null,
            'status' => 'disabled',
            'last_error' => $reason,
        ])->save();
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    private function tokenPayload(int $status, ?array $body): array
    {
        $body ??= [];
        $error = $this->errorCode($body);

        if ($status === 400 && $error === 'invalid_grant') {
            throw new GoogleAccessRevokedException($status, 'Google authorization code was rejected.', 'invalid_grant');
        }

        if ($status < 200 || $status >= 300 || empty($body['access_token'])) {
            throw new GoogleApiException($status, 'Google token exchange failed.', $error);
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function errorCode(?array $body): ?string
    {
        $error = $body['error'] ?? null;

        if (is_string($error)) {
            return $error;
        }

        if (is_array($error) && isset($error['status'])) {
            return (string) $error['status'];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function parseScopes(mixed $scope): array
    {
        if (! is_string($scope) || trim($scope) === '') {
            return [];
        }

        return array_values(array_filter(preg_split('/\s+/', trim($scope)) ?: []));
    }
}
