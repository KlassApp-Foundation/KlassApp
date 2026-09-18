<?php

namespace Tests\Feature\Toshi;

use App\Ai\Tools\Toshi\ApprovableMcpTool;
use App\Mcp\Servers\SpikeSlackMockServer;
use App\Mcp\Tools\SpikeSlackAuthTestTool;
use App\Mcp\Tools\SpikeSlackPostMessageTool;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Toshi\McpWriteGate;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Tools\McpTool;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * HITL gate: ApprovableMcpTool + McpWriteGate.
 *
 * Write-classified tools pause for approval; reads execute immediately.
 * Raw Client::callTool on a write-classified tool fails closed.
 * Approval resume re-enters the loop via ApprovableMcpTool::handle.
 */
class HITLMcpWriteGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.slack_mcp.mode' => 'mock',
            'toshi.mcp_write_gates.master_switch' => true,
            'toshi.mcp_write_gates.connectors.slack.mode' => 'classify',
            'toshi.mcp_connectors.slack.read_tools' => ['spike-slack-auth-test', 'spike-slack-list-channels'],
            'toshi.mcp_connectors.slack.write_tools' => ['spike-slack-post-message'],
        ]);

        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->ensureMockSlackClient();
    }

    #[Test]
    public function read_tool_is_not_classified_as_write(): void
    {
        $this->assertFalse(McpWriteGate::isWrite('slack', 'spike-slack-auth-test'));
        $this->assertFalse(McpWriteGate::isWrite('slack', 'spike-slack-list-channels'));
    }

    #[Test]
    public function write_tool_is_classified_as_write(): void
    {
        $this->assertTrue(McpWriteGate::isWrite('slack', 'spike-slack-post-message'));
    }

    #[Test]
    public function unknown_tool_in_classify_mode_is_write(): void
    {
        $this->assertTrue(McpWriteGate::isWrite('slack', 'unknown-tool-name'));
    }

    #[Test]
    public function deny_mode_treats_all_tools_as_write(): void
    {
        config(['toshi.mcp_write_gates.connectors.slack.mode' => 'deny']);

        $this->assertTrue(McpWriteGate::isWrite('slack', 'spike-slack-auth-test'));
        $this->assertTrue(McpWriteGate::isWrite('slack', 'spike-slack-post-message'));
    }

    #[Test]
    public function allowlist_mode_gates_only_write_tools(): void
    {
        config(['toshi.mcp_write_gates.connectors.slack.mode' => 'allowlist']);

        $this->assertFalse(McpWriteGate::isWrite('slack', 'spike-slack-auth-test'));
        $this->assertTrue(McpWriteGate::isWrite('slack', 'spike-slack-post-message'));
    }

    #[Test]
    public function master_switch_off_skips_all_gating(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => false]);

        $this->assertFalse(McpWriteGate::isWrite('slack', 'spike-slack-post-message'));
    }

    #[Test]
    public function missing_connector_config_fails_closed(): void
    {
        $this->assertTrue(McpWriteGate::isWrite('nonexistent-connector', 'any-tool'));
    }

    #[Test]
    public function approvable_mcp_tool_wrap_inherits_name_description_schema(): void
    {
        $primitive = $this->mockPrimitive('spike-slack-auth-test');
        $wrapped = ApprovableMcpTool::wrap('slack', $primitive);

        $this->assertSame('mcp_tools_spike-slack-auth-test', $wrapped->name());
        $this->assertStringContainsString('Mock Slack auth.test', $wrapped->description());
    }

    #[Test]
    public function approvable_mcp_tool_read_returns_no_approval(): void
    {
        $primitive = $this->mockPrimitive('spike-slack-auth-test');
        $wrapped = ApprovableMcpTool::wrap('slack', $primitive);

        $approval = $wrapped->shouldRequestApproval(new Request([]));

        $this->assertNull($approval);
    }

    #[Test]
    public function approvable_mcp_tool_write_returns_required_approval(): void
    {
        $primitive = $this->mockPrimitive('spike-slack-post-message');
        $wrapped = ApprovableMcpTool::wrap('slack', $primitive);

        $approval = $wrapped->shouldRequestApproval(new Request(['channel' => '#general', 'text' => 'Hello']));

        $this->assertInstanceOf(Approval::class, $approval);
        $this->assertStringContainsString('slack', $approval->reason);
        $this->assertStringContainsString('spike-slack-post-message', $approval->reason);
    }

    #[Test]
    public function raw_client_call_tool_on_write_throws(): void
    {
        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('write-classified');

        Mcp::client('slack')->callTool('spike-slack-post-message', ['channel' => '#general', 'text' => 'Hello']);
    }

    #[Test]
    public function raw_client_call_tool_on_read_succeeds(): void
    {
        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $result = Mcp::client('slack')->callTool('spike-slack-auth-test', []);

        $this->assertFalse($result->isError ?? false);
    }

    #[Test]
    public function approvable_mcp_tool_handle_executes_write_when_bypass_active(): void
    {
        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $before = ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count();

        $primitive = Mcp::client('slack')->tools()->get('spike-slack-post-message');
        $wrapped = ApprovableMcpTool::wrap('slack', $primitive);

        $result = $wrapped->handle(new Request(['channel' => '#general', 'text' => 'Approved message']));

        $this->assertStringContainsString('"ok":true', $result);

        $this->assertSame(
            $before + 1,
            ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count(),
            'ApprovableMcpTool::handle must still produce an audit row via the auditing client'
        );
    }

    #[Test]
    public function approved_write_that_returns_mcp_error_result_still_audits_as_failure(): void
    {
        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $before = ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count();

        $primitive = Mcp::client('slack')->tools()->get('spike-slack-post-message');
        $wrapped = ApprovableMcpTool::wrap('slack', $primitive);

        // Human approved the write; the "server" (mock, simulating a real Slack
        // rejection) returns an MCP error result for the execution anyway.
        $result = $wrapped->handle(new Request([
            'channel' => '#general',
            'text' => 'MOCK_FORCE_ERROR approved but rejected',
        ]));

        $this->assertStringContainsString('MCP tool error', $result);
        $this->assertStringContainsString('mock_forced_error', $result);

        $this->assertSame(
            $before + 1,
            ActivityLog::query()->where('log_name', ToshiAuditService::LOG_NAME)->count(),
            'Errored execution must still produce exactly one audit row'
        );

        $log = ActivityLog::query()
            ->where('log_name', ToshiAuditService::LOG_NAME)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringStartsWith('❌', (string) $log->properties['result']);
        $this->assertStringContainsString('mock_forced_error', (string) $log->properties['result']);
    }

    #[Test]
    public function approvable_mcp_tool_handle_executes_read_normally(): void
    {
        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $primitive = Mcp::client('slack')->tools()->get('spike-slack-auth-test');
        $wrapped = ApprovableMcpTool::wrap('slack', $primitive);

        $result = $wrapped->handle(new Request([]));

        $this->assertStringContainsString('T_SPIKE_MOCK', $result);
    }

    #[Test]
    public function mcp_write_gate_bypass_restores_state(): void
    {
        $executed = false;

        $result = McpWriteGate::bypassFor(function () use (&$executed) {
            McpWriteGate::assertExecutable('slack', 'spike-slack-post-message');
            $executed = true;

            return 'bypassed';
        });

        $this->assertTrue($executed);
        $this->assertSame('bypassed', $result);

        // After bypassFor returns, gate is closed again.
        $this->assertTrue(McpWriteGate::isWrite('slack', 'spike-slack-post-message'));
    }

    #[Test]
    public function mcp_write_gate_bypass_for_nested_calls_stack_correctly(): void
    {
        $outer = McpWriteGate::bypassFor(function () {
            McpWriteGate::assertExecutable('slack', 'spike-slack-post-message');

            return McpWriteGate::bypassFor(function () {
                McpWriteGate::assertExecutable('slack', 'spike-slack-post-message');

                return 'inner';
            });
        });

        $this->assertSame('inner', $outer);
        $this->assertTrue(McpWriteGate::isWrite('slack', 'spike-slack-post-message'));
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

    private function mockPrimitive(string $toolName): object
    {
        $tools = Mcp::client('slack')->tools();
        $this->assertTrue($tools->has($toolName), "Mock server must expose {$toolName}");

        return $tools->get($toolName);
    }
}
