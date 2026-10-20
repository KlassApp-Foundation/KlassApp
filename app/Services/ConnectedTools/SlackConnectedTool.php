<?php

namespace App\Services\ConnectedTools;

use App\Contracts\ConnectedTools\ConnectedTool;
use App\Models\SchoolMcpConnector;

/**
 * Slack uses the existing per-school MCP connector row. Connected only when
 * this school's slack row is active and still has a usable token. Disconnect
 * posts to the integrations route. Connect is the same live-mode link the
 * integrations page already shows, and it is omitted when that link is not
 * actually available.
 */
class SlackConnectedTool implements ConnectedTool
{
    public function key(): string
    {
        return 'slack';
    }

    public function stateForSchool(int $schoolId): array
    {
        $row = SchoolMcpConnector::query()
            ->forSchool($schoolId)
            ->forType('slack')
            ->first();

        if ($row === null || $row->status === 'disabled') {
            return $this->notConnected();
        }

        if (! $row->isActive() || $this->tokenNeedsRenewal($row)) {
            return [
                'status' => ConnectedToolsRegistry::NEEDS_ATTENTION,
                'attention' => 'The Slack connection needs to be renewed.',
                'last_synced_at' => null,
                'action' => $this->connectable()
                    ? ConnectedToolsRegistry::ACTION_CONNECT
                    : ConnectedToolsRegistry::ACTION_SETTINGS,
                'action_url' => $this->connectable()
                    ? url('mcp/slack/connect')
                    : route('admin.settings.integrations'),
                'action_method' => 'GET',
            ];
        }

        return [
            'status' => ConnectedToolsRegistry::CONNECTED,
            'attention' => null,
            'last_synced_at' => $row->last_used_at,
            'action' => ConnectedToolsRegistry::ACTION_DISCONNECT,
            'action_url' => route('admin.settings.integrations.disconnect', ['type' => 'slack']),
            'action_method' => 'POST',
        ];
    }

    private function tokenNeedsRenewal(SchoolMcpConnector $row): bool
    {
        if ($row->accessToken() === null) {
            return true;
        }

        return $row->isTokenExpired() && ($row->refreshToken() === null || $row->refreshToken() === '');
    }

    /**
     * @return array<string, mixed>
     */
    private function notConnected(): array
    {
        if (! $this->connectable()) {
            return [
                'status' => ConnectedToolsRegistry::NOT_CONNECTED,
                'attention' => null,
                'last_synced_at' => null,
                'action' => null,
                'action_url' => null,
                'action_method' => 'GET',
            ];
        }

        return [
            'status' => ConnectedToolsRegistry::NOT_CONNECTED,
            'attention' => null,
            'last_synced_at' => null,
            'action' => ConnectedToolsRegistry::ACTION_CONNECT,
            'action_url' => url('mcp/slack/connect'),
            'action_method' => 'GET',
        ];
    }

    private function connectable(): bool
    {
        return config('services.slack_mcp.mode') === 'live'
            && filled(config('services.slack_mcp.client_id'));
    }
}
