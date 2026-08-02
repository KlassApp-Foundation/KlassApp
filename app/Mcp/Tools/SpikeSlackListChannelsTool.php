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
 * Read-only mock of channels.list — fixture channels only.
 * Spike only; not a production Slack tool design.
 */
#[Name('spike-slack-list-channels')]
#[Description('Mock Slack channels.list — returns fixture test channels (read-only).')]
class SpikeSlackListChannelsTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $limit = (int) ($request->get('limit') ?? 10);

        $channels = [
            ['id' => 'C_SPIKE_GENERAL', 'name' => 'general', 'is_private' => false],
            ['id' => 'C_SPIKE_TEST', 'name' => 'toshi-mcp-spike', 'is_private' => false],
            ['id' => 'C_SPIKE_STAFF', 'name' => 'staff-only', 'is_private' => true],
        ];

        return Response::structured([
            'ok' => true,
            'channels' => array_slice($channels, 0, max(1, min($limit, 50))),
            'mode' => 'mock',
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Max channels to return (1–50).')
                ->default(10),
        ];
    }
}
