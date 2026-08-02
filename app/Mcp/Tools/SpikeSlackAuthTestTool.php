<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

/**
 * Read-only mock of Slack auth.test — used when SLACK_MCP_MODE=mock.
 * Spike only; not a production Slack tool design.
 */
#[Name('spike-slack-auth-test')]
#[Description('Mock Slack auth.test — returns fixture workspace identity (read-only).')]
class SpikeSlackAuthTestTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        return Response::structured([
            'ok' => true,
            'url' => 'https://klassapp-spike.slack.com/',
            'team' => 'KlassApp Spike',
            'team_id' => 'T_SPIKE_MOCK',
            'user' => 'spike-bot',
            'user_id' => 'U_SPIKE_MOCK',
            'mode' => 'mock',
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
