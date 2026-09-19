<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\Skills\SlackSkill;
use App\AiAgents\Tools\RouteToSlackSkillTool;
use App\Mcp\Servers\SpikeSlackMockServer;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ToshiActionService;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Ai;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCase;

/**
 * PR3 Slack wave-1 — end-to-end MCP write approval flow.
 *
 * Proves the write path pauses for approval end-to-end through the REAL
 * surfaces (not just isolated ApprovableMcpTool unit tests):
 *
 *  1. RouteToSlackSkillTool.handle() → nested SlackSkill prompt pauses on a
 *     write-classified tool call (fake LLM emits the call; tools + gate are real).
 *  2. No audit execution row exists while paused; an approval_requested row does.
 *  3. The pause surfaces the side-channel payload with mcp_resume (conversation
 *     id + approval id) — exactly what the Toshi panel decodes.
 *  4. Resuming with Decisions::approve() executes the write through the single
 *     legal path (ApprovableMcpTool::handle under McpWriteGate bypass) and
 *     audits approval_resolved with approver identity.
 *  5. Reject path: nothing executes, denial audited.
 */
class SlackMcpApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.slack_mcp.mode' => 'mock',
            // Neutralize dual-config guard + host/model mismatch from the
            // developer's local .env (same class of fix Platform tests rely on).
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
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->ensureMockSlackClient();
    }

    #[Test]
    public function write_pauses_for_approval_end_to_end_and_resumes_on_approve(): void
    {
        $schoolId = $this->createSchool();
        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolId]);
        $this->actingAs($admin);

        ToshiActionService::$pendingConfirmPayload = null;

        // Fake LLM: the (real) SlackSkill is prompted by the (real)
        // RouteToSlackSkillTool and emits a write tool call. The tool itself,
        // the write gate, and the approval loop are all real.
        SlackSkill::fake([
            new ToolCall('call_slack_post_1', 'mcp_tools_spike-slack-post-message', [
                'channel' => '#general',
                'text' => 'Fee reminders go out Friday.',
            ]),
            'Slack message posted after approval.',
        ]);

        $result = (new RouteToSlackSkillTool)->handle(new Request([
            'query' => 'Post "Fee reminders go out Friday." to #general on Slack.',
        ]));

        // ── Paused, not executed ──
        $decoded = json_decode($result, true);
        $this->assertTrue(($decoded['__tier2_confirm'] ?? false) === true, 'Write must surface a confirmation payload, got: '.substr($result, 0, 200));
        $this->assertSame('mcp_tools_spike-slack-post-message', $decoded['tool']);
        $this->assertSame('#general', $decoded['args']['channel']);

        // Side-channel carries the resume coordinates for the panel.
        $payload = ToshiActionService::$pendingConfirmPayload;
        $this->assertNotNull($payload, 'RouteToSlackSkillTool must populate the side-channel on pause');
        $this->assertSame(SlackSkill::class, $payload['mcp_resume']['agent_class']);
        $this->assertNotNull($payload['mcp_resume']['conversation_id'], 'Paused conversation must be persisted for resume');
        $this->assertNotNull($payload['mcp_resume']['approval_id']);
        ToshiActionService::$pendingConfirmPayload = null;

        // No write execution audit row while paused; the request IS audited.
        $this->assertSame(
            0,
            ActivityLog::where('log_name', ToshiAuditService::LOG_NAME)
                ->where('properties->tool', 'mcp_tools_spike-slack-post-message')
                ->where('properties->status', 'success')
                ->count(),
            'No write execution audit row may exist while paused'
        );
        $this->assertTrue(
            ActivityLog::where('log_name', ToshiAuditService::LOG_NAME)
                ->where('properties->tool', 'mcp_tools_spike-slack-post-message')
                ->where('properties->status', 'pending_approval')
                ->exists(),
            'The pause must be audited as pending_approval'
        );

        // ── Resume with an approve decision (panel path) ──
        $this->clearAgentFake(SlackSkill::class);
        $this->fakeOpenAiCompatibleCompletion('Slack message posted after approval.');

        $resumed = (new SlackSkill)
            ->continue($payload['mcp_resume']['conversation_id'], as: $admin)
            ->prompt(Decisions::from([
                $payload['mcp_resume']['approval_id'] => Decision::approve(),
            ]));

        $this->assertFalse($resumed->hasPendingApprovals());

        // The approved write executed through the single legal path + audited.
        $this->assertTrue(
            ActivityLog::where('log_name', ToshiAuditService::LOG_NAME)
                ->where('properties->tool', 'mcp_tools_spike-slack-post-message')
                ->where('properties->status', 'success')
                ->exists(),
            'Approved write must have an execution audit row'
        );
        $this->assertTrue(
            ActivityLog::where('log_name', ToshiAuditService::LOG_NAME)
                ->where('properties->tool', 'mcp_tools_spike-slack-post-message')
                ->where('properties->status', 'approval_resolved')
                ->exists(),
            'Resolution must be audited with approver identity'
        );
    }

    #[Test]
    public function write_paused_then_rejected_executes_nothing(): void
    {
        $schoolId = $this->createSchool();
        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolId]);
        $this->actingAs($admin);

        ToshiActionService::$pendingConfirmPayload = null;

        SlackSkill::fake([
            new ToolCall('call_slack_post_2', 'mcp_tools_spike-slack-post-message', [
                'channel' => '#staff-alerts',
                'text' => 'Staff meeting moved to 4pm.',
            ]),
            'Cancelled.',
        ]);

        $result = (new RouteToSlackSkillTool)->handle(new Request([
            'query' => 'Post "Staff meeting moved to 4pm." to #staff-alerts.',
        ]));

        $decoded = json_decode($result, true);
        $this->assertTrue(($decoded['__tier2_confirm'] ?? false) === true);

        $payload = ToshiActionService::$pendingConfirmPayload;
        $this->assertNotNull($payload);
        ToshiActionService::$pendingConfirmPayload = null;

        $this->assertSame(
            0,
            ActivityLog::where('log_name', ToshiAuditService::LOG_NAME)
                ->where('properties->tool', 'mcp_tools_spike-slack-post-message')
                ->where('properties->status', 'success')
                ->count()
        );

        $this->clearAgentFake(SlackSkill::class);
        $this->fakeOpenAiCompatibleCompletion('Understood — not posting.');

        $resumed = (new SlackSkill)
            ->continue($payload['mcp_resume']['conversation_id'], as: $admin)
            ->prompt(Decisions::from([
                $payload['mcp_resume']['approval_id'] => Decision::reject('Rejected in test.'),
            ]));

        $this->assertFalse($resumed->hasPendingApprovals());

        $this->assertSame(
            0,
            ActivityLog::where('log_name', ToshiAuditService::LOG_NAME)
                ->where('properties->tool', 'mcp_tools_spike-slack-post-message')
                ->where('properties->status', 'success')
                ->count(),
            'Rejected write must never execute'
        );
        $this->assertTrue(
            ActivityLog::where('log_name', ToshiAuditService::LOG_NAME)
                ->where('properties->tool', 'mcp_tools_spike-slack-post-message')
                ->where('properties->status', 'approval_rejected')
                ->exists(),
            'Rejection must be audited'
        );
    }

    #[Test]
    public function read_query_does_not_pause_and_audits_execution(): void
    {
        $schoolId = $this->createSchool();
        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolId]);
        $this->actingAs($admin);

        ToshiActionService::$pendingConfirmPayload = null;

        SlackSkill::fake([
            new ToolCall('call_slack_list_1', 'mcp_tools_spike-slack-auth-test', []),
            'Workspace connected as KlassApp Spike.',
        ]);

        $result = (new RouteToSlackSkillTool)->handle(new Request([
            'query' => 'Check the Slack workspace status.',
        ]));

        $decoded = json_decode($result, true);
        $this->assertFalse(($decoded['__tier2_confirm'] ?? false) === true, 'Reads must not pause');
        $this->assertStringContainsString('KlassApp Spike', (string) $result);

        $this->assertNull(ToshiActionService::$pendingConfirmPayload);

        $this->assertTrue(
            ActivityLog::where('log_name', ToshiAuditService::LOG_NAME)
                ->where('properties->tool', 'mcp_tools_spike-slack-auth-test')
                ->where('properties->status', 'success')
                ->exists(),
            'Read must execute and audit'
        );
    }

    private function createSchool(): int
    {
        return \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
            'name' => 'Slack Flow School',
            'slug' => 'slack-flow-' . uniqid(),
            'email' => 'slack-flow-' . uniqid() . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function clearAgentFake(string $agentClass): void
    {
        $ai = \Laravel\Ai\Ai::getFacadeRoot();
        $prop = new ReflectionProperty($ai, 'fakeAgentGateways');
        $gateways = $prop->getValue($ai);
        unset($gateways[$agentClass]);
        $prop->setValue($ai, $gateways);
    }

    private function fakeOpenAiCompatibleCompletion(string $content): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'id' => 'chatcmpl-test',
                'object' => 'chat.completion',
                'choices' => [[
                    'index' => 0,
                    'message' => ['role' => 'assistant', 'content' => $content],
                    'finish_reason' => 'stop',
                ]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);
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
