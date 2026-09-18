<?php

namespace Tests\Feature\Toshi;

use App\Services\Toshi\McpWriteGate;
use App\Services\Toshi\ToshiMcpClient;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Defense-in-depth: raw Client::callTool on a write-classified MCP tool
 * fails closed even when the Approvable gate in the agent loop is bypassed.
 *
 * Read tools are unaffected — they execute and audit as before.
 */
#[Group('mcp-write-gate')]
class McpWriteGateDefenseInDepthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.slack_mcp.mode' => 'mock',
            'services.slack_mcp.url' => 'https://mcp.slack.com/mcp',
            'toshi.mcp_connectors.slack.read_tools' => [
                'spike-slack-auth-test',
                'spike-slack-list-channels',
            ],
            'toshi.mcp_connectors.slack.write_tools' => [
                'spike-slack-post-message',
            ],
            'toshi.mcp_write_gates.master_switch' => true,
            'toshi.mcp_write_gates.connectors.slack.mode' => 'classify',
        ]);

        $this->ensureMockSlackClient();
    }

    #[Test]
    public function read_tool_continues_to_execute_and_audit(): void
    {
        $user = \App\Models\User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $before = \App\Models\ActivityLog::query()
            ->where('log_name', ToshiAuditService::LOG_NAME)
            ->count();

        $result = Mcp::client('slack')->callTool('spike-slack-auth-test', []);

        $this->assertFalse($result->isError ?? false);
        $this->assertSame(
            $before + 1,
            \App\Models\ActivityLog::query()
                ->where('log_name', ToshiAuditService::LOG_NAME)
                ->count(),
            'Read tool must still produce audit row'
        );
    }

    #[Test]
    public function write_tool_blocked_outside_approved_turn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('write-classified');

        Mcp::client('slack')->callTool('spike-slack-post-message', [
            'channel' => '#general',
            'text' => 'Blocked',
        ]);
    }

    #[Test]
    public function write_tool_allowed_inside_bypass_context(): void
    {
        $user = \App\Models\User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $before = \App\Models\ActivityLog::query()
            ->where('log_name', ToshiAuditService::LOG_NAME)
            ->count();

        $result = McpWriteGate::bypassFor(function () {
            return Mcp::client('slack')->callTool('spike-slack-post-message', [
                'channel' => '#general',
                'text' => 'Allowed via bypass',
            ]);
        });

        // Gate opened: call reached the client. Mock result shape is irrelevant to gate behavior.
        $this->assertInstanceOf(\Laravel\Mcp\Client\Schema\ToolResult::class, $result);
        $this->assertSame(
            $before + 1,
            \App\Models\ActivityLog::query()
                ->where('log_name', ToshiAuditService::LOG_NAME)
                ->count(),
            'Bypass context must still audit after execution'
        );
    }

    #[Test]
    public function master_switch_off_disables_gate_entirely(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => false]);

        $user = \App\Models\User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $result = Mcp::client('slack')->callTool('spike-slack-post-message', [
            'channel' => '#general',
            'text' => 'Gate off',
        ]);

        // Gate disabled: call reached the client regardless of mock result shape.
        $this->assertInstanceOf(\Laravel\Mcp\Client\Schema\ToolResult::class, $result);
    }

    private function ensureMockSlackClient(): void
    {
        $mockServer = \App\Mcp\Servers\SpikeSlackMockServer::class;

        Mcp::registerClient('slack', function () {
            return Client::local(PHP_BINARY, [
                base_path('artisan'),
                'mcp:start',
                'spike-slack-mock',
            ])->withTimeout(30);
        });
    }
}
