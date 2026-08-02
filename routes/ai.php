<?php

use App\Mcp\Servers\SpikeSlackMockServer;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Client;
use Laravel\Mcp\Client\OAuth\TokenSet;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| Spike: Slack MCP client wiring (throwaway branch spike/toshi-mcp-slack)
|--------------------------------------------------------------------------
|
| Config shape (config/services.php → slack_mcp):
|   mode         mock|live          default mock
|   url          https://mcp.slack.com/mcp
|   client_id    Slack app OAuth client id (live)
|   client_secret optional / often unused for public native clients
|   token        optional bearer when not using OAuth (dev only)
|
| Per-school note: production must NOT use a single global Slack token.
| Schools have their own workspaces — store TokenSet per school (or per
| connecting staff user) after mcp.oauth.slack.connect.
|
| Audit: named Mcp::client() → AuditingMcpClient(Manager) callTool audit.
| Agent McpTool uses same layer (LogToolInvoked skips McpTool). Prefer
| ToshiMcpClient when you need an explicit acting user. MCP HITL deferred.
*/

$mode = config('services.slack_mcp.mode', 'mock');

if ($mode === 'mock') {
    // Local fixture server + named client via stdio → `php artisan mcp:start spike-slack-mock`
    Mcp::local('spike-slack-mock', SpikeSlackMockServer::class);

    Mcp::registerClient('slack', function () {
        return Client::local(PHP_BINARY, [
            base_path('artisan'),
            'mcp:start',
            'spike-slack-mock',
        ])->withTimeout((float) config('services.slack_mcp.timeout', 30));
    });
} else {
    // Live Slack MCP (blocked without credentials — see spike notes)
    Mcp::registerClient('slack', function () {
        $url = (string) config('services.slack_mcp.url', 'https://mcp.slack.com/mcp');
        $client = Client::web($url)->withTimeout((float) config('services.slack_mcp.timeout', 30));

        $token = config('services.slack_mcp.token');
        if (is_string($token) && $token !== '') {
            return $client->withToken($token);
        }

        return $client->withOAuth(
            clientId: config('services.slack_mcp.client_id'),
            clientSecret: config('services.slack_mcp.client_secret') ?: null,
        );
    });

    // OAuth callback scaffolding — persist TokenSet per authenticated user for now.
    // Production must map team_id → school_id (per-workspace), not a global env secret.
    Mcp::oAuthRoutesFor('slack', function (string $client, TokenSet $token) {
        $user = Auth::user();
        if ($user !== null) {
            // Placeholder: do not reuse users.google_token pattern.
            // Real design: dedicated school_slack_mcp_credentials table.
            logger()->info('spike.slack_mcp.oauth_callback', [
                'client' => $client,
                'user_id' => $user->id,
                'has_access_token' => filled($token->accessToken ?? null),
            ]);
        }

        return redirect('/dashboard');
    });
}
