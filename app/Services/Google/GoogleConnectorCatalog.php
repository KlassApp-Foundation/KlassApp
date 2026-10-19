<?php

namespace App\Services\Google;

use App\Models\SchoolGoogleConnection;

class GoogleConnectorCatalog
{
    /**
     * @return array<string, array{label: string, scopes: list<string>, why: string}>
     */
    public function all(): array
    {
        return config('google.connectors', []);
    }

    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }

    /**
     * @return array{label: string, scopes: list<string>, why: string}
     */
    public function get(string $key): array
    {
        $entry = $this->all()[$key] ?? null;

        if ($entry === null) {
            throw new \InvalidArgumentException("Unknown Google connector [{$key}].");
        }

        return $entry;
    }

    /**
     * Product scopes only. The email scope is added at authorize time.
     *
     * @return list<string>
     */
    public function scopesFor(string $key): array
    {
        return array_values($this->get($key)['scopes']);
    }

    /**
     * Scopes sent on the consent screen for this connector.
     *
     * @return list<string>
     */
    public function authorizeScopes(string $key): array
    {
        $scopes = $this->scopesFor($key);
        $email = (string) config('google.email_scope');

        if ($email !== '' && ! in_array($email, $scopes, true)) {
            $scopes[] = $email;
        }

        return $scopes;
    }

    public function emailScope(): string
    {
        return (string) config('google.email_scope');
    }

    public function isLive(?SchoolGoogleConnection $connection, string $key): bool
    {
        if ($connection === null || ! $connection->isActive()) {
            return false;
        }

        if ($connection->refreshToken() === null && $connection->accessToken() === null) {
            return false;
        }

        $granted = $connection->granted_scopes ?? [];

        return count(array_diff($this->scopesFor($key), $granted)) === 0;
    }
}
