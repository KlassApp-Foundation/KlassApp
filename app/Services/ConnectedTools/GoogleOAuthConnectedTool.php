<?php

namespace App\Services\ConnectedTools;

use App\Contracts\ConnectedTools\ConnectedTool;
use App\Models\User;
use App\Models\UserGoogleConnection;
use App\Services\Google\GoogleConnectionService;
use App\Services\Google\GoogleConnectorCatalog;
use App\Services\Google\GoogleOAuthService;
use DateTimeInterface;

/**
 * Status from the signed-in user's own Google row.
 *
 * Connected only when that row is active, still has a refresh token, and
 * Google granted every scope this connector needs. A revoked or missing
 * refresh token, a partial scope grant, or a recorded sync error is Needs
 * attention. No row, or a clean disconnect, is Not connected. Connect is
 * offered only when this school allows the connector and the OAuth client
 * is configured.
 */
class GoogleOAuthConnectedTool implements ConnectedTool
{
    public function __construct(
        private readonly string $key,
        private readonly GoogleConnectorCatalog $catalog,
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function stateFor(User $user): array
    {
        $connections = app(GoogleConnectionService::class);
        $connection = $connections->forUser($user);
        $allowed = $user->school_id !== null && $connections->schoolAllows((int) $user->school_id, $this->key);
        $connectUrl = route('admin.google.connect', ['connector' => $this->key]);
        $required = $this->catalog->scopesFor($this->key);

        if (! $allowed) {
            return $this->notConnected(null, 'Your school has not turned this on.');
        }

        if (! app(GoogleOAuthService::class)->isConfigured()) {
            return $this->notConnected(null, 'Connect unavailable');
        }

        if ($connection === null) {
            return $this->notConnected($connectUrl);
        }

        $granted = $connection->granted_scopes ?? [];
        $missing = array_values(array_diff($required, $granted));
        $hasAll = $missing === [];
        $hasSome = count(array_intersect($required, $granted)) > 0;
        $error = $connection->last_error;

        if ($this->isTokenFailure($error)) {
            $affected = $connection->attention_scopes ?? [];
            $wasThisTool = count(array_intersect($required, $affected)) > 0;

            if ($wasThisTool) {
                return $this->attention(
                    'The Google connection needs to be renewed.',
                    $connectUrl,
                    ConnectedToolsRegistry::ACTION_CONNECT,
                );
            }
        }

        if ($connection->isActive() && $hasAll && $this->isSyncFailure($error)) {
            return $this->attention(
                'The last sync did not finish.',
                route('admin.settings.integrations'),
                ConnectedToolsRegistry::ACTION_SETTINGS,
            );
        }

        if ($connection->isActive() && $hasSome && ! $hasAll) {
            return $this->attention(
                'Google did not grant the access this tool needs.',
                $connectUrl,
                ConnectedToolsRegistry::ACTION_CONNECT,
            );
        }

        if ($connection->isActive() && $hasAll && $connection->refreshToken() === null) {
            return $this->attention(
                'The Google connection needs to be renewed.',
                $connectUrl,
                ConnectedToolsRegistry::ACTION_CONNECT,
            );
        }

        if ($connection->isActive() && $hasAll && $connection->refreshToken() !== null) {
            return [
                'status' => ConnectedToolsRegistry::CONNECTED,
                'attention' => null,
                'note' => null,
                'account_email' => $connection->google_email,
                'last_synced_at' => $this->lastSync($connection),
                'action' => ConnectedToolsRegistry::ACTION_DISCONNECT,
                'action_url' => route('admin.google.disconnect', ['connector' => $this->key]),
                'action_method' => 'POST',
            ];
        }

        return $this->notConnected($connectUrl);
    }

    private function isTokenFailure(?string $error): bool
    {
        return in_array($error, ['invalid_grant', 'no_refresh_token'], true);
    }

    private function isSyncFailure(?string $error): bool
    {
        return is_string($error) && str_starts_with($error, 'sync_');
    }

    private function lastSync(UserGoogleConnection $connection): ?DateTimeInterface
    {
        return $connection->last_used_at ?? $connection->last_refreshed_at;
    }

    /**
     * @return array<string, mixed>
     */
    private function notConnected(?string $connectUrl, ?string $note = null): array
    {
        return [
            'status' => ConnectedToolsRegistry::NOT_CONNECTED,
            'attention' => null,
            'note' => $note,
            'account_email' => null,
            'last_synced_at' => null,
            'action' => $connectUrl === null ? null : ConnectedToolsRegistry::ACTION_CONNECT,
            'action_url' => $connectUrl,
            'action_method' => 'GET',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attention(string $message, string $url, string $action): array
    {
        return [
            'status' => ConnectedToolsRegistry::NEEDS_ATTENTION,
            'attention' => $message,
            'note' => null,
            'account_email' => null,
            'last_synced_at' => null,
            'action' => $action,
            'action_url' => $url,
            'action_method' => 'GET',
        ];
    }
}
