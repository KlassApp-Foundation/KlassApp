<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\Skills\SlackSkill;
use App\AiAgents\Tools\RouteToSlackSkillTool;
use App\AiAgents\ToshiSdkV2Service;
use App\Exceptions\PendingMcpApprovalException;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Structural gap from §6c: nested SlackSkill pause must reach the panel
 * even when the parent Orchestrator would otherwise continue / fail into
 * fallbackMessage(). RouteToSlackSkillTool aborts via PendingMcpApprovalException
 * after filling the side-channel; ToshiSdkV2Service must return __tier2_confirm.
 */
class NestedMcpApprovalSurfacingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.slack_mcp.mode' => 'mock',
            'toshi.llm_env' => [
                'openai_compatible_model' => 'deepseek-chat',
                'toshi_llm_model' => null,
                'openai_compatible_url' => 'https://api.deepseek.com/v1',
                'toshi_llm_base_url' => null,
            ],
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com/v1',
            'ai.providers.openai-compatible.key' => 'test-key',
            'toshi.model' => 'deepseek-chat',
            'toshi.sdk_v2_enabled' => true,
            'toshi.mcp_write_gates.master_switch' => true,
            'toshi.mcp_write_gates.connectors.slack.mode' => 'classify',
            'toshi.mcp_connectors.slack.enabled' => true,
            'toshi.mcp_connectors.slack.read_tools' => ['spike-slack-auth-test', 'spike-slack-list-channels'],
            'toshi.mcp_connectors.slack.write_tools' => ['spike-slack-post-message'],
        ]);

        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->ensureMockSlackClient();
    }

    #[Test]
    public function route_tool_throws_after_populating_side_channel_on_nested_pause(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        ToshiActionService::$pendingConfirmPayload = null;

        SlackSkill::fake([
            new ToolCall('call_nested_pause', 'mcp_tools_spike-slack-post-message', [
                'channel' => '#general',
                'text' => 'Nested pause probe',
            ]),
            'should not reach',
        ]);

        try {
            (new RouteToSlackSkillTool)->handle(new Request([
                'query' => 'Post Nested pause probe to #general',
            ]));
            $this->fail('Expected PendingMcpApprovalException');
        } catch (PendingMcpApprovalException) {
            // expected
        }

        $payload = ToshiActionService::$pendingConfirmPayload;
        $this->assertNotNull($payload);
        $this->assertSame('mcp_tools_spike-slack-post-message', $payload['tool']);
        $this->assertArrayHasKey('mcp_resume', $payload);
        $this->assertNotEmpty($payload['mcp_resume']['conversation_id'] ?? null);
        $this->assertNotEmpty($payload['mcp_resume']['approval_id'] ?? null);
    }

    #[Test]
    public function sdk_v2_consume_encodes_tier2_confirm_from_side_channel(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        ToshiActionService::$pendingConfirmPayload = [
            'tool' => 'mcp_tools_slack_send_message',
            'args' => ['channel_id' => 'C1', 'message' => 'probe'],
            'preview' => 'Approve Slack action: mcp_tools_slack_send_message',
            'mcp_resume' => [
                'agent_class' => SlackSkill::class,
                'conversation_id' => 'conv-ex',
                'approval_id' => 'call_ex',
            ],
        ];

        $service = app(ToshiSdkV2Service::class);
        $method = new ReflectionMethod(ToshiSdkV2Service::class, 'consumePendingConfirmPayload');
        $method->setAccessible(true);

        $json = $method->invoke($service);
        $decoded = json_decode((string) $json, true);

        $this->assertTrue($decoded['__tier2_confirm'] ?? false);
        $this->assertSame('mcp_tools_slack_send_message', $decoded['tool']);
        $this->assertSame('conv-ex', $decoded['mcp_resume']['conversation_id']);
        $this->assertSame('call_ex', $decoded['mcp_resume']['approval_id']);
        $this->assertNull(ToshiActionService::$pendingConfirmPayload);
    }

    #[Test]
    public function throwable_catch_prefers_side_channel_over_null_fallback(): void
    {
        // Mirrors the §6c failure: outer ask() Throwable catch must return
        // __tier2_confirm when the nested pause already filled the side-channel,
        // instead of returning null → Livewire fallbackMessage().
        ToshiActionService::$pendingConfirmPayload = [
            'tool' => 'mcp_tools_slack_search_channels',
            'args' => ['query' => 'channels'],
            'preview' => 'Approve Slack action: mcp_tools_slack_search_channels',
            'mcp_resume' => [
                'agent_class' => SlackSkill::class,
                'conversation_id' => 'conv-stranded',
                'approval_id' => 'call_stranded',
            ],
        ];

        $service = app(ToshiSdkV2Service::class);
        $method = new ReflectionMethod(ToshiSdkV2Service::class, 'consumePendingConfirmPayload');
        $method->setAccessible(true);

        // Same method both PendingMcpApprovalException and Throwable catches use.
        $json = $method->invoke($service);
        $decoded = json_decode((string) $json, true);

        $this->assertTrue($decoded['__tier2_confirm']);
        $this->assertSame('mcp_tools_slack_search_channels', $decoded['tool']);
        $this->assertArrayHasKey('mcp_resume', $decoded);
    }

    private function makeAdmin(): User
    {
        $schoolId = \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
            'name' => 'Nested Pause School',
            'slug' => 'nested-pause-'.uniqid(),
            'email' => 'nested-pause-'.uniqid().'@test.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolId]);
    }

    private function ensureMockSlackClient(): void
    {
        try {
            Mcp::client('slack');

            return;
        } catch (\Laravel\Mcp\Exceptions\ClientException) {
        }

        Mcp::local('spike-slack-mock', \App\Mcp\Servers\SpikeSlackMockServer::class);
        Mcp::registerClient('slack', function () {
            return Client::local(PHP_BINARY, [
                base_path('artisan'),
                'mcp:start',
                'spike-slack-mock',
            ])->withTimeout(30);
        });
    }
}
