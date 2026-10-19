<?php

namespace App\Services\Google;

use App\Models\SchoolGoogleConnection;
use App\Models\User;

class GoogleConnectionService
{
    public function __construct(
        private readonly GoogleOAuthService $oauth,
        private readonly GoogleConnectorCatalog $catalog,
    ) {
    }

    public function connectFromCode(User $user, string $connector, string $code): SchoolGoogleConnection
    {
        $this->catalog->get($connector);

        $token = $this->oauth->exchangeCode($code);
        $granted = $this->oauth->parseScopes($token['scope'] ?? null);
        $missing = array_diff($this->catalog->scopesFor($connector), $granted);

        if ($missing !== []) {
            throw new GoogleApiException(403, 'Google did not grant the required access.', 'insufficient_scope');
        }

        $profile = $this->oauth->userinfo((string) $token['access_token']);

        $existing = SchoolGoogleConnection::query()->where('school_id', $user->school_id)->first();
        $refreshToken = $token['refresh_token'] ?? $existing?->refreshToken();

        if ($refreshToken === null || $refreshToken === '') {
            throw new GoogleApiException(400, 'Google did not return a refresh token.', 'no_refresh_token');
        }

        return SchoolGoogleConnection::updateOrCreate(
            ['school_id' => $user->school_id],
            [
                'google_sub' => $profile['id'] ?? $existing?->google_sub,
                'google_email' => $profile['email'] ?? $existing?->google_email,
                'credentials' => [
                    'access_token' => $token['access_token'],
                    'refresh_token' => $refreshToken,
                    'token_type' => $token['token_type'] ?? 'Bearer',
                ],
                'granted_scopes' => $granted,
                'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
                'status' => 'active',
                'connected_by' => $user->id,
                'last_refreshed_at' => now(),
            ],
        );
    }

    /**
     * Drop one connector. Google's revoke endpoint is all-or-nothing, so the
     * token is revoked and deleted only when no other connector remains.
     * While another connector is still granted, this one stops being Live
     * and its scopes are removed from the stored grant.
     */
    public function disconnect(User $user, string $connector): void
    {
        $this->catalog->get($connector);

        $connection = SchoolGoogleConnection::query()
            ->where('school_id', $user->school_id)
            ->first();

        if ($connection === null || ! $this->catalog->isLive($connection, $connector)) {
            abort(404);
        }

        $drop = $this->catalog->scopesFor($connector);
        $remaining = array_values(array_diff($connection->granted_scopes ?? [], $drop));

        $anotherLive = false;

        foreach (array_keys($this->catalog->all()) as $key) {
            if ($key === $connector) {
                continue;
            }

            if (count(array_diff($this->catalog->scopesFor($key), $remaining)) === 0) {
                $anotherLive = true;
                break;
            }
        }

        if (! $anotherLive) {
            $this->oauth->revokeAndForget($connection);

            return;
        }

        $connection->update(['granted_scopes' => $remaining]);
    }

    /**
     * @return list<array{key: string, label: string, why: string, live: bool, configured: bool, email: ?string}>
     */
    public function cardRows(int $schoolId): array
    {
        $connection = SchoolGoogleConnection::query()->where('school_id', $schoolId)->first();
        $configured = $this->oauth->isConfigured();
        $rows = [];

        foreach ($this->catalog->all() as $key => $entry) {
            $live = $this->catalog->isLive($connection, $key);
            $rows[] = [
                'key' => $key,
                'label' => $entry['label'],
                'why' => $entry['why'],
                'live' => $live,
                'configured' => $configured,
                'email' => $live ? $connection?->google_email : null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function liveSummaries(int $schoolId): array
    {
        $live = [];

        foreach ($this->cardRows($schoolId) as $row) {
            if ($row['live']) {
                $live[] = ['key' => $row['key'], 'label' => $row['label']];
            }
        }

        return $live;
    }
}
