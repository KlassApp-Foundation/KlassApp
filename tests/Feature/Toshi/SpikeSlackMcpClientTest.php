<?php

namespace Tests\Feature\Toshi;

use App\Ai\Agents\SpikeSlackMcpTestAgent;
use App\Mcp\Servers\SpikeSlackMockServer;
use App\Mcp\Tools\SpikeSlackAuthTestTool;
use App\Mcp\Tools\SpikeSlackListChannelsTool;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Toshi\AuditingMcpClient;
use App\Services\Toshi\ToshiMcpClient;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Tools\McpTool;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * MCP Slack mock + structurally audited named-client callTool.
 * MCP Approvable/HITL for write tools is deferred.
 */
class SpikeSlackMcpClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.slack_mcp.mode' => 'mock',
            'services.slack_mcp.url' => 'https://mcp.slack.com/mcp',
        ]);

        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    #[Test]
    public function mock_server_auth_test_tool_returns_fixture(): void
    {
        $response = SpikeSlackMockServer::tool(SpikeSlackAuthTestTool::class, []);

        $response
            ->assertOk()
            ->assertSee('T_SPIKE_MOCK')
            ->assertSee('KlassApp Spike');
    }

    #[Test]
    public function mock_server_list_channels_tool_returns_fixture(): void
    {
        $response = SpikeSlackMockServer::tool(SpikeSlackListChannelsTool::class, [
            'limit' => 2,
        ]);

        $response
            ->assertOk()
            ->assertSee('toshi-mcp-spike')
            ->assertSee('general');
    }

    #[Test]
    public function named_slack_client_lists_mock_tools(): void
    {
        $this->ensureMockSlackClient();

        $client = Mcp::client('slack');
        $tools = $client->tools();

        $this->assertTrue($tools->has('spike-slack-auth-test'), 'Expected mock auth tool in tools()');
        $this->assertTrue($tools->has('spike-slack-list-channels'), 'Expected mock channels tool in tools()');
    }

    #[Test]
    public function named_mcp_client_is_structurally_auditing_wrapper(): void
    {
        $this->ensureMockSlackClient();

        $client = Mcp::client('slack');

        $this->assertInstanceOf(
            AuditingMcpClient::class,
            $client,
            'Mcp::client() must return AuditingMcpClient so callTool cannot bypass audit'
        );
    }

    #[Test]
    public function test_agent_spreads_mcp_client_tools(): void
    {
        $this->ensureMockSlackClient();

        $agent = new SpikeSlackMcpTestAgent;
        $tools = iterator_to_array($agent->tools(), false);

        $this->assertNotEmpty($tools, 'Agent should receive MCP tools from named client');

        $names = [];
        foreach ($tools as $tool) {
            if ($tool instanceof McpTool) {
                $names[] = $tool->name();
            } elseif (is_object($tool) && isset($tool->name)) {
                $names[] = $tool->name;
            }
        }

        $this->assertTrue(
            collect($names)->contains(fn ($n) => str_contains((string) $n, 'spike-slack-auth-test')
                || str_contains((string) $n, 'mcp_tools_spike-slack-auth-test')),
            'Expected wrapped MCP auth tool; got: '.implode(', ', $names)
        );
    }

    #[Test]
    public function raw_named_client_call_tool_writes_read_shaped_audit_when_authenticated(): void
    {
        $this->ensureMockSlackClient();

        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $before = ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count();

        // Habitual raw path — must still audit because AuditingMcpClientManager wraps callTool.
        $result = Mcp::client('slack')->callTool('spike-slack-auth-test', []);
        $this->assertFalse($result->isError ?? false);

        $this->assertSame(
            $before + 1,
            ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count(),
            'Raw Mcp::client()->callTool must produce an audit row (structural closure)'
        );

        $log = ActivityLog::query()
            ->where('log_name', ToshiAuditService::LOG_NAME)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->properties['acting_user_id']);
        $this->assertArrayHasKey('approver_id', $log->properties);
        $this->assertNull($log->properties['approver_id']);
        $this->assertSame('mcp_tools_spike-slack-auth-test', $log->properties['tool']);
        $this->assertSame('success', $log->properties['status']);
    }

    #[Test]
    public function toshi_mcp_client_call_tool_writes_read_shaped_audit(): void
    {
        $this->ensureMockSlackClient();

        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $before = ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count();

        $result = ToshiMcpClient::named('slack')->callTool('spike-slack-auth-test', []);
        $this->assertFalse($result->isError ?? false);

        $this->assertSame(
            $before + 1,
            ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count(),
            'ToshiMcpClient must not double-log on top of AuditingMcpClient'
        );

        $log = ActivityLog::query()
            ->where('log_name', ToshiAuditService::LOG_NAME)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->properties['acting_user_id']);
        $this->assertNull($log->properties['approver_id']);
        $this->assertSame('mcp_tools_spike-slack-auth-test', $log->properties['tool']);
    }

    #[Test]
    public function toshi_mcp_client_call_tool_requires_acting_user(): void
    {
        $this->ensureMockSlackClient();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('requires an acting user');

        ToshiMcpClient::named('slack')->callTool('spike-slack-auth-test', []);
    }

    #[Test]
    public function agent_mediated_mcp_handle_writes_read_shaped_audit_once(): void
    {
        $this->ensureMockSlackClient();

        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $agent = new SpikeSlackMcpTestAgent;
        $primitives = Mcp::client('slack')->tools();
        $this->assertTrue($primitives->has('spike-slack-auth-test'));

        $mcpTool = new McpTool($primitives->get('spike-slack-auth-test'));

        $before = ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count();

        // Real agent path: McpTool::handle → Tool::call → Client::callTool (audited).
        $result = $mcpTool->handle(new Request([]));
        $this->assertIsString($result);

        $this->assertSame(
            $before + 1,
            ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count()
        );

        $log = ActivityLog::query()
            ->where('log_name', ToshiAuditService::LOG_NAME)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->properties['acting_user_id']);
        $this->assertNull(
            $log->properties['approver_id'],
            'callTool audit path must leave approver_id null (HITL deferred for MCP writes)'
        );
        $this->assertSame('mcp_tools_spike-slack-auth-test', $log->properties['tool']);
        $this->assertSame('success', $log->properties['status']);

        // ToolInvoked listener skips McpTool — no second row.
        event(new ToolInvoked(
            'test-invocation',
            'test-tool-invocation',
            $agent,
            $mcpTool,
            [],
            $result,
        ));

        $this->assertSame(
            $before + 1,
            ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count(),
            'LogToolInvoked must not double-log McpTool after AuditingMcpClient'
        );
    }

    private function ensureMockSlackClient(): void
    {
        try {
            Mcp::client('slack');

            return;
        } catch (\Laravel\Mcp\Exceptions\ClientException) {
            // Register below when routes/ai.php did not run (or client missing).
        }

        Mcp::local('spike-slack-mock', SpikeSlackMockServer::class);
        Mcp::registerClient('slack', function () {
            return Client::local(PHP_BINARY, [
                base_path('artisan'),
                'mcp:start',
                'spike-slack-mock',
            ])->withTimeout(30);
        });
    }
}
