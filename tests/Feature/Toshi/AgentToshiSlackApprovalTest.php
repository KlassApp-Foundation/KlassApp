<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\Skills\SlackSkill;
use App\Livewire\AgentToshi;
use App\Mcp\Servers\SpikeSlackMockServer;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ToshiActionService;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Ai;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCase;

/**
 * PR3 Slack wave-1 — panel-level approval flow (AgentToshi).
 *
 * The confirm card decodes the RouteToSlackSkillTool pause payload
 * (mcp_resume = agent class + conversation id + approval id). confirmYes()
 * must resume the paused conversation natively with Decision::approve();
 * confirmNo() rejects. Proves the UI path drives the real vendor resume —
 * the write executes through the single legal path and audits with the
 * confirming user as approver.
 */
class AgentToshiSlackApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.slack_mcp.mode' => 'mock',
            // Neutralize dual-config guard + host/model mismatch from local .env.
            'toshi.llm_env' => [
                'openai_compatible_model' => 'deepseek-chat',
                'toshi_llm_model' => null,
                'openai_compatible_url' => 'https://api.deepseek.com/v1',
                'toshi_llm_base_url' => null,
            ],
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com/v1',
            'ai.providers.openai-compatible.key' => 'test-key',
            'toshi.model' => 'deepseek-chat',
            'toshi.mcp_write_gates.master_switch' => true,
            'toshi.mcp_write_gates.connectors.slack.mode' => 'classify',
            'toshi.mcp_connectors.slack.enabled' => true,
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
    public function confirm_card_approve_resumes_the_paused_mcp_write(): void
    {
        [$admin, $payload] = $this->createPausedApproval();

        // Real provider for the resume turn (fake cleared; HTTP stubbed).
        $this->clearAgentFake(SlackSkill::class);
        $this->fakeOpenAiCompatibleCompletion('Posted to #general.');

        Livewire::test(AgentToshi::class)
            ->set('pendingToolConfirm', [
                'tool' => 'mcp_tools_spike-slack-post-message',
                'args' => ['channel' => '#general', 'text' => 'Fee reminders go out Friday.'],
                'mcp_resume' => $payload,
            ])
            ->call('confirmYes');

        $this->assertTrue(
            ActivityLog::where('log_name', ToshiAuditService::LOG_NAME)
                ->where('properties->tool', 'mcp_tools_spike-slack-post-message')
                ->where('properties->status', 'success')
                ->exists(),
            'Approved write must execute and audit after panel confirmYes'
        );
        $this->assertTrue(
            ActivityLog::where('log_name', ToshiAuditService::LOG_NAME)
                ->where('properties->tool', 'mcp_tools_spike-slack-post-message')
                ->where('properties->status', 'approval_resolved')
                ->exists(),
            'Panel resolution must be audited with approver identity'
        );
    }

    #[Test]
    public function confirm_card_reject_leaves_the_write_unexecuted(): void
    {
        [$admin, $payload] = $this->createPausedApproval();

        $this->clearAgentFake(SlackSkill::class);
        $this->fakeOpenAiCompatibleCompletion('Understood.');

        Livewire::test(AgentToshi::class)
            ->set('pendingToolConfirm', [
                'tool' => 'mcp_tools_spike-slack-post-message',
                'args' => ['channel' => '#general', 'text' => 'Fee reminders go out Friday.'],
                'mcp_resume' => $payload,
            ])
            ->call('confirmNo');

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

    /**
     * Drive a real pause through RouteToSlackSkillTool and return the resume
     * payload the panel would decode.
     */
    private function createPausedApproval(): array
    {
        $schoolId = \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
            'name' => 'Slack Panel School',
            'slug' => 'slack-panel-' . uniqid(),
            'email' => 'slack-panel-' . uniqid() . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolId]);
        $this->actingAs($admin);

        ToshiActionService::$pendingConfirmPayload = null;

        SlackSkill::fake([
            new ToolCall('call_slack_pause', 'mcp_tools_spike-slack-post-message', [
                'channel' => '#general',
                'text' => 'Fee reminders go out Friday.',
            ]),
            'Posted.',
        ]);

        (new \App\AiAgents\Tools\RouteToSlackSkillTool)->handle(
            new \Laravel\Ai\Tools\Request(['query' => 'Post "Fee reminders go out Friday." to #general'])
        );

        $payload = ToshiActionService::$pendingConfirmPayload;
        $this->assertNotNull($payload, 'Pause must populate the side-channel');
        ToshiActionService::$pendingConfirmPayload = null;

        return [$admin, $payload['mcp_resume']];
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
