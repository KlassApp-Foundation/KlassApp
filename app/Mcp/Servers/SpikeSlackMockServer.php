<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\SpikeSlackAuthTestTool;
use App\Mcp\Tools\SpikeSlackListChannelsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * Local fixture MCP server standing in for https://mcp.slack.com/mcp.
 * Registered as handle `spike-slack-mock` when SLACK_MCP_MODE=mock.
 */
#[Name('Spike Slack Mock')]
#[Version('0.0.1')]
#[Instructions('Throwaway mock Slack MCP for KlassApp Mcp::client(\'slack\') plumbing spike. Not real Slack.')]
class SpikeSlackMockServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        SpikeSlackAuthTestTool::class,
        SpikeSlackListChannelsTool::class,
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [];
}
