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
            ->withOAuth(
                (string) config('services.slack_mcp.client_id') ?: null,
                (string) config('services.slack_mcp.client_secret') ?: null,
                // Real Slack granular scopes (env SLACK_MCP_SCOPES) — NOT
                // 'mcp:read mcp:write', which Slack rejects with "Invalid
                // permissions requested" (see go-live checklist §4).
                (string) config('services.slack_mcp.scopes') ?: null,
            )
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

        return redirect('/admin/settings/integrations');
    });
}

/*
|--------------------------------------------------------------------------
| MCP transport era gap — dated re-verification marker (2027-04-28)
|--------------------------------------------------------------------------
|
| Every named client above speaks the pre-2026 MCP protocol era: stateful
| MCP-Session-Id + initialize handshake + SSE-capable POST
| (laravel/mcp 0.8.2 HttpTransport; ProtocolVersion::LATEST = 2025-11-25).
| The 2026-07-28 MCP specification made the protocol stateless and
| deprecated the pre-2026 era under a formal deprecation policy
| (SEP-2596): a minimum 12-month deprecation window from the release
| that first marks it deprecated, so earliest removal eligibility is
| ~2027-07-28 (a 90-day expedited window exists only for security
| advisories).
|
| Currently acceptable because hosted endpoints (mcp.slack.com and
| future catalog connectors) still serve the legacy era, and because
| the upstream fix already exists: laravel/mcp v1.0.0 (2026-09-14)
| speaks the 2026-07-28 era. We cannot adopt it yet because
| laravel/mcp enters this app only through laravel/boost
| (require-dev) at ^0.7.1|^0.8.0 — see plan doc R.8.
|
| TODO: before 2027-04-28, re-verify (1) whether mcp.slack.com and
| other catalog endpoints still serve the legacy era, (2) whether
| laravel/ai and laravel/boost constraints admit laravel/mcp 1.x,
| and (3) any announced legacy-era removal dates. Enforced by
| tests/Architecture/McpTransportEraReverificationTest.php.
| @see docs/plans/toshi-mcp-connector-registry-and-shortlist-reeval-plan.md R.8
*/
