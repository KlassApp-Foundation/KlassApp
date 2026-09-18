<?php

use App\Mcp\Servers\SpikeSlackMockServer;
use App\Models\SchoolMcpConnector;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Client;
use Laravel\Mcp\Client\OAuth\TokenSet;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP connector client wiring
|--------------------------------------------------------------------------
|
| Named clients are the ONLY construction site (McpClientConstructionTest).
| Mock mode: local fixture server via stdio.
| Live mode: per-school token resolved from school_mcp_connectors registry.
*/

$mode = config('services.slack_mcp.mode', 'mock');

if ($mode === 'mock') {
    Mcp::local('spike-slack-mock', SpikeSlackMockServer::class);

    Mcp::registerClient('slack', function () {
        return Client::local(PHP_BINARY, [
            base_path('artisan'),
            'mcp:start',
            'spike-slack-mock',
        ])->withTimeout((float) config('services.slack_mcp.timeout', 30));
    });
} else {
    Mcp::registerClient('slack', function () {
        $url = (string) config('services.slack_mcp.url', 'https://mcp.slack.com/mcp');
        $timeout = (float) config('services.slack_mcp.timeout', 30);

        $connectorType = 'slack';

        return Client::web($url)
            ->withTimeout($timeout)
            ->withToken(fn () => SchoolMcpConnector::resolveTokenForRequest($connectorType)
                ?? throw new \App\Exceptions\ConnectorNotConnected(
                    auth()->user()?->school_id ?? 0,
                    $connectorType,
                ));
    });

    Mcp::oAuthRoutesFor('slack', function (string $client, TokenSet $token) {
        $user = Auth::user();

        if ($user?->school_id === null) {
            return redirect('/dashboard');
        }

        $teamId = $token->teamId ?? $token->team_id ?? $user->id;
        $teamName = $token->teamName ?? $token->team_name ?? null;

        SchoolMcpConnector::updateOrCreate(
            [
                'school_id' => $user->school_id,
                'connector_type' => 'slack',
                'external_team_id' => (string) $teamId,
            ],
            [
                'external_team_name' => $teamName,
                'credentials' => [
                    'access_token' => $token->accessToken,
                    'refresh_token' => $token->refreshToken ?? null,
                    'token_type' => $token->tokenType ?? 'Bearer',
                    'scope' => $token->scope ?? null,
                ],
                'token_expires_at' => $token->expiresAt ?? null,
                'auth_mode' => 'oauth_remote',
                'status' => 'active',
                'write_mode' => 'deny',
                'connected_by' => $user->id,
            ]
        );

        return redirect('/dashboard');
    });
}
