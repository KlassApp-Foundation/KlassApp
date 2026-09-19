<?php

namespace Tests\Feature\Toshi;

use App\Ai\Tools\Toshi\ApprovableMcpTool;
use App\AiAgents\Skills\SlackSkill;
use App\AiAgents\Tools\RouteToSlackSkillTool;
use App\Exceptions\ConnectorNotConnected;
use App\Mcp\Servers\SpikeSlackMockServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\McpTool;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PR3 Slack wave-1 — SlackSkill surface tests.
 *
 * Covers: disabled connector, tool wrapping (ApprovableMcpTool on every
 * primitive), not-connected fallback tool, routing auth gate.
 * Full pause/resume lives in SlackMcpApprovalFlowTest (end-to-end).
 */
class SlackSkillTest extends TestCase
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
    public function disabled_connector_yields_no_tools_and_disabled_instructions(): void
    {
        config(['toshi.mcp_connectors.slack.enabled' => false]);

        $skill = new SlackSkill;

        $this->assertSame([], [...$skill->tools()]);
        $this->assertStringContainsString('disabled', $skill->instructions());
    }

    #[Test]
    public function enabled_connector_wraps_every_primitive_in_approvable_mcp_tool(): void
    {
        config(['toshi.mcp_connectors.slack.enabled' => true]);

        $tools = [...(new SlackSkill)->tools()];

        $this->assertNotEmpty($tools, 'Enabled connector must expose MCP tools');

        foreach ($tools as $tool) {
            $this->assertInstanceOf(ApprovableMcpTool::class, $tool, 'Every MCP primitive must be wrapped by ApprovableMcpTool');
        }

        $names = array_map(fn ($t) => $t->name(), $tools);
        $this->assertContains('mcp_tools_spike-slack-post-message', $names);
        $this->assertContains('mcp_tools_spike-slack-list-channels', $names);
    }

    #[Test]
    public function wrapped_read_executes_and_write_is_gated(): void
    {
        config(['toshi.mcp_connectors.slack.enabled' => true]);

        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $tools = collect([...(new SlackSkill)->tools()])->keyBy(fn ($t) => $t->name());

        $read = $tools['mcp_tools_spike-slack-auth-test'];
        $this->assertNull($read->shouldRequestApproval(new Request([])));
        $this->assertStringContainsString('T_SPIKE_MOCK', $read->handle(new Request([])));

        $write = $tools['mcp_tools_spike-slack-post-message'];
        $approval = $write->shouldRequestApproval(new Request(['channel' => '#general', 'text' => 'Hi']));
        $this->assertNotNull($approval, 'spike-slack-post-message must pause for approval');
        $this->assertStringContainsString('slack', $approval->reason);
    }

    #[Test]
    public function not_connected_connector_exposes_status_tool_only(): void
    {
        config([
            'toshi.mcp_connectors.slack.enabled' => true,
            'services.slack_mcp.mode' => 'live',
        ]);

        // Re-register the slack client as live mode with no registry row for
        // this school — resolveTokenForRequest throws ConnectorNotConnected.
        Mcp::registerClient('slack', function () {
            $url = (string) config('services.slack_mcp.url', 'https://mcp.slack.com/mcp');

            return Client::web($url)
                ->withTimeout((float) config('services.slack_mcp.timeout', 30))
                ->withToken(fn () => \App\Models\SchoolMcpConnector::resolveTokenForRequest('slack')
                    ?? throw new ConnectorNotConnected(auth()->user()?->school_id ?? 0, 'slack'));
        });

        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $tools = [...(new SlackSkill)->tools()];

        $this->assertCount(1, $tools, 'Unconnected school must only see the slack_status tool');
        $this->assertSame('slack_status', $tools[0]->name());
        $this->assertStringContainsString('not connected', strtolower($tools[0]->handle(new Request([]))));
    }

    #[Test]
    public function route_tool_denies_unauthenticated_users(): void
    {
        config(['toshi.mcp_connectors.slack.enabled' => true]);

        $result = (new RouteToSlackSkillTool)->handle(new Request(['query' => 'list channels']));

        $this->assertStringContainsString('❌', $result);
    }

    #[Test]
    public function route_tool_denies_roles_without_school_scope(): void
    {
        config(['toshi.mcp_connectors.slack.enabled' => true]);

        $teacher = User::factory()->create(['usergroup_id' => 5]);
        $this->actingAs($teacher);

        $result = (new RouteToSlackSkillTool)->handle(new Request(['query' => 'list channels']));

        $this->assertStringContainsString('❌', $result);
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
