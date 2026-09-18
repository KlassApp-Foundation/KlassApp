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
 * Mock write tool for the Slack wave-1 test suite.
 * Mimics slack_post_message; fixture response only.
 * Text prefixed with MOCK_FORCE_ERROR returns an MCP error result,
 * simulating a real Slack rejection on a human-approved write.
 */
#[Name('spike-slack-post-message')]
#[Description('Mock Slack post_message — writes to a channel (mock only, no real Slack).')]
class SpikeSlackPostMessageTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $channel = $request->get('channel') ?? '#general';
        $text = $request->get('text') ?? 'Hello from mock!';

        if (str_starts_with($text, 'MOCK_FORCE_ERROR')) {
            return Response::make([Response::error('mock_forced_error: Slack rejected the message')]);
        }

        return Response::structured([
            'ok' => true,
            'channel' => $channel,
            'ts' => (string) now()->timestamp,
            'text' => $text,
            'mode' => 'mock',
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'channel' => $schema->string()->description('Channel name (e.g. #general)')->required(),
            'text' => $schema->string()->description('Message text')->required(),
        ];
    }
}
