<?php

namespace Tests\Feature\Toshi\Platform;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Ai\Tools\Superadmin\CreatePlanTool;
use App\Ai\Tools\Superadmin\UpdatePlanTool;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use ReflectionProperty;
use Tests\TestCase;

class PlatformPlanToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $siteadmin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.platform_gate.enabled' => true,
            'toshi.platform_gate.user_ids' => [],
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->siteadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin-plan@test.sch.ug',
        ]);
    }

    private function planArgs(array $overrides = []): array
    {
        return array_merge([
            'cycle' => 'monthly',
            'name' => 'BatchA Plan',
            'amount' => 12345,
            'no_of_users' => 10,
            'no_of_students' => 100,
            'is_active' => 1,
        ], $overrides);
    }

    public function test_create_plan_pending_before_approve_no_mutation(): void
    {
        $this->actingAs($this->siteadmin);

        $args = $this->planArgs();

        PlatformOperationsAgent::fake([
            new ToolCall('call_plan_pending', 'CreatePlanTool', $args),
        ]);

        $response = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Create billing plan BatchA Plan.');

        $this->assertTrue($response->hasPendingApprovals());
        $pending = $response->pendingApprovals->first();
        $this->assertSame('CreatePlanTool', $pending->tool);
        $this->assertStringContainsString('billing', strtolower((string) $pending->reason));
        $this->assertSame(0, Plan::where('name', 'BatchA Plan')->count());

        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('causer_id', $this->siteadmin->id)
                ->where('properties->status', 'pending_approval')
                ->where('properties->tool', 'CreatePlanTool')
                ->exists()
        );
    }

    public function test_create_plan_mutates_only_after_decision_approve_resume(): void
    {
        $this->actingAs($this->siteadmin);

        $args = $this->planArgs(['name' => 'Approved Plan', 'amount' => 99]);

        PlatformOperationsAgent::fake([
            new ToolCall('call_plan_approve', 'CreatePlanTool', $args),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Create Approved Plan.');

        $this->assertTrue($paused->hasPendingApprovals());
        $this->assertDatabaseMissing('plans', ['name' => 'Approved Plan']);
        $this->assertNotNull($paused->conversationId);

        $approvalId = $paused->pendingApprovals->first()->id;

        // Agent::fake skips real tool resume; clear fake + stub provider for post-approve continuation.
        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Plan created after approval.');

        $resumed = (new PlatformOperationsAgent)
            ->continue($paused->conversationId, as: $this->siteadmin)
            ->prompt(Decisions::from([
                $approvalId => Decision::approve(),
            ]));

        $this->assertFalse($resumed->hasPendingApprovals());
        $this->assertDatabaseHas('plans', [
            'name' => 'Approved Plan',
            'amount' => 99,
            'no_of_users' => 10,
            'no_of_students' => 100,
        ]);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'CreatePlanTool')
                ->where('properties->status', 'success')
                ->exists()
        );
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'CreatePlanTool')
                ->where('properties->status', 'approval_resolved')
                ->exists()
        );
    }

    public function test_create_plan_blocked_on_reject(): void
    {
        $this->actingAs($this->siteadmin);

        $args = $this->planArgs(['name' => 'Rejected Plan']);

        PlatformOperationsAgent::fake([
            new ToolCall('call_plan_reject', 'CreatePlanTool', $args),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Create Rejected Plan.');

        $this->assertTrue($paused->hasPendingApprovals());
        $approvalId = $paused->pendingApprovals->first()->id;

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Understood, plan not created.');

        $resumed = (new PlatformOperationsAgent)
            ->continue($paused->conversationId, as: $this->siteadmin)
            ->prompt(Decisions::from([
                $approvalId => Decision::reject('Keep existing pricing.'),
            ]));

        $this->assertFalse($resumed->hasPendingApprovals());
        $this->assertDatabaseMissing('plans', ['name' => 'Rejected Plan']);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'CreatePlanTool')
                ->where('properties->status', 'approval_rejected')
                ->exists()
        );
    }

    public function test_needs_approval_returns_approval_instance(): void
    {
        $tool = app(CreatePlanTool::class);
        $approval = $tool->shouldRequestApproval(new Request($this->planArgs([
            'name' => 'Schema Check',
        ])));

        $this->assertNotNull($approval);
        $this->assertStringContainsString('Schema Check', (string) $approval->reason);
    }

    public function test_create_plan_validation_rejection_after_approve_via_handle(): void
    {
        $this->actingAs($this->siteadmin);

        // handle() runs only after native HITL approve; direct call simulates post-approve execution.
        $result = (string) app(CreatePlanTool::class)->handle(new Request([
            'cycle' => 'monthly',
            'name' => 'Bad Plan',
            'no_of_users' => 1,
            'no_of_students' => 1,
        ]));

        $this->assertStringStartsWith('❌', $result);
        $this->assertDatabaseMissing('plans', ['name' => 'Bad Plan']);
    }

    public function test_update_plan_approvable_flow(): void
    {
        $this->actingAs($this->siteadmin);

        $plan = Plan::create([
            'cycle' => 'monthly',
            'name' => 'Editable',
            'display_name' => 'Editable',
            'order' => 1,
            'is_active' => 1,
            'amount' => 10,
            'no_of_users' => 5,
            'no_of_students' => 50,
        ]);

        $args = $this->planArgs([
            'id' => $plan->id,
            'name' => 'Editable Updated',
            'amount' => 54321,
        ]);

        PlatformOperationsAgent::fake([
            new ToolCall('call_plan_update', 'UpdatePlanTool', $args),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Update Editable plan.');

        $this->assertTrue($paused->hasPendingApprovals());
        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'amount' => 10]);

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Plan updated.');

        (new PlatformOperationsAgent)
            ->continue($paused->conversationId, as: $this->siteadmin)
            ->prompt(Decisions::from([
                $paused->pendingApprovals->first()->id => Decision::approve(),
            ]));

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Editable Updated',
            'amount' => 54321,
        ]);
    }

    public function test_agent_fake_assert_prompted_with_decisions(): void
    {
        PlatformOperationsAgent::fake();

        (new PlatformOperationsAgent)->prompt(Decisions::from([
            'call_abc' => Decision::approve(),
        ]));

        PlatformOperationsAgent::assertPrompted(function (AgentPrompt $prompt) {
            return $prompt->hasApprovalDecisions()
                && $prompt->approvalDecisions->get('call_abc')->isApproved();
        });
    }

    /**
     * laravel/ai skips real tool resume while Agent::fake() is registered.
     */
    private function clearAgentFake(string $agentClass): void
    {
        $ai = Ai::getFacadeRoot();
        $prop = new ReflectionProperty($ai, 'fakeAgentGateways');
        $gateways = $prop->getValue($ai);
        unset($gateways[$agentClass]);
        $prop->setValue($ai, $gateways);
    }

    private function fakeOpenAiCompatibleCompletion(string $content): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'id' => 'chatcmpl-test',
                'object' => 'chat.completion',
                'choices' => [[
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => $content,
                    ],
                    'finish_reason' => 'stop',
                ]],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'total_tokens' => 15,
                ],
            ], 200),
        ]);
    }
}
