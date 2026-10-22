<?php

namespace App\Services\Google;

use App\Models\SchoolGoogleConnectorAllow;
use App\Models\User;
use App\Models\UserGoogleConnection;

class GoogleConnectionService
{
    /** School roles that may connect their own Google account. Platform admins are not in this list. */
    public const SCHOOL_ROLES = [3, 4, 5, 6, 7];

    public function __construct(
        private readonly GoogleOAuthService $oauth,
        private readonly GoogleConnectorCatalog $catalog,
    ) {
    }

    public function isSchoolMember(?User $user): bool
    {
        return $user !== null
            && $user->school_id !== null
            && in_array((int) $user->usergroup_id, self::SCHOOL_ROLES, true);
    }

    public function forUser(User $user): ?UserGoogleConnection
    {
        if ($user->school_id === null) {
            return null;
        }

        return UserGoogleConnection::query()
            ->where('user_id', $user->id)
            ->where('school_id', $user->school_id)
            ->first();
    }

    public function schoolAllows(int $schoolId, string $connector): bool
    {
        return SchoolGoogleConnectorAllow::query()
            ->where('school_id', $schoolId)
            ->where('connector', $connector)
            ->where('allowed', true)
            ->exists();
    }

    public function setAllowed(int $schoolId, string $connector, bool $allowed): void
    {
        $this->catalog->get($connector);

        SchoolGoogleConnectorAllow::query()->updateOrCreate(
            ['school_id' => $schoolId, 'connector' => $connector],
            ['allowed' => $allowed],
        );
    }

    /**
     * People at this school who have this connector on. No names, emails, or tokens.
     */
    public function anonymousCount(int $schoolId, string $connector): int
    {
        $needed = $this->catalog->scopesFor($connector);

        return UserGoogleConnection::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereNotNull('credentials')
            ->get(['granted_scopes'])
            ->filter(fn (UserGoogleConnection $row) => count(array_diff($needed, $row->granted_scopes ?? [])) === 0)
            ->count();
    }

    public function connectFromCode(User $user, string $connector, string $code): UserGoogleConnection
    {
        $this->catalog->get($connector);

        if (! $this->isSchoolMember($user) || ! $this->schoolAllows((int) $user->school_id, $connector)) {
            abort(403);
        }

        $token = $this->oauth->exchangeCode($code);
        $granted = $this->oauth->parseScopes($token['scope'] ?? null);
        $missing = array_diff($this->catalog->scopesFor($connector), $granted);

        if ($missing !== []) {
            throw new GoogleApiException(403, 'Google did not grant the required access.', 'insufficient_scope');
        }

        $profile = $this->oauth->userinfo((string) $token['access_token']);
        $existing = $this->forUser($user);
        $refreshToken = $token['refresh_token'] ?? $existing?->refreshToken();

        if ($refreshToken === null || $refreshToken === '') {
            throw new GoogleApiException(400, 'Google did not return a refresh token.', 'no_refresh_token');
        }

        return UserGoogleConnection::updateOrCreate(
            ['user_id' => $user->id],
            [
                'school_id' => $user->school_id,
                'google_sub' => $profile['id'] ?? $existing?->google_sub,
                'google_email' => $profile['email'] ?? $existing?->google_email,
                'credentials' => [
                    'access_token' => $token['access_token'],
                    'refresh_token' => $refreshToken,
                    'token_type' => $token['token_type'] ?? 'Bearer',
                ],
                'granted_scopes' => $granted,
                'attention_scopes' => [],
                'last_error' => null,
                'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
                'status' => 'active',
                'last_refreshed_at' => now(),
            ],
        );
    }

    /**
     * Drop one connector on this user's own grant.
     *
     * Google's revoke endpoint is all-or-nothing. While another connector is
     * still granted, this one stops being Live and its scopes are removed.
     * When nothing remains, the token is revoked at Google and the row is deleted.
     */
    public function disconnect(User $user, string $connector): void
    {
        $this->catalog->get($connector);

        $connection = $this->forUser($user);

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
            $connection->delete();

            return;
        }

        $connection->update(['granted_scopes' => $remaining]);
    }

    /**
     * @return list<array{key: string, label: string, why: string, live: bool, allowed: bool, configured: bool, email: ?string}>
     */
    public function cardRows(User $user): array
    {
        $connection = $this->forUser($user);
        $configured = $this->oauth->isConfigured();
        $rows = [];

        foreach ($this->catalog->all() as $key => $entry) {
            $live = $this->catalog->isLive($connection, $key);
            $rows[] = [
                'key' => $key,
                'label' => $entry['label'],
                'why' => $entry['why'],
                'live' => $live,
                'allowed' => $user->school_id !== null && $this->schoolAllows((int) $user->school_id, $key),
                'configured' => $configured,
                'email' => $live ? $connection?->google_email : null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{key: string, label: string, allowed: bool, count: int}>
     */
    public function allowRows(int $schoolId): array
    {
        $rows = [];

        foreach ($this->catalog->all() as $key => $entry) {
            $rows[] = [
                'key' => $key,
                'label' => $entry['label'],
                'allowed' => $this->schoolAllows($schoolId, $key),
                'count' => $this->anonymousCount($schoolId, $key),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function liveSummaries(User $user): array
    {
        $live = [];

        foreach ($this->cardRows($user) as $row) {
            if ($row['live']) {
                $live[] = ['key' => $row['key'], 'label' => $row['label']];
            }
        }

        return $live;
    }
}
