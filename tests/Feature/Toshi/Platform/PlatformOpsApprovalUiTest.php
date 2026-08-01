<?php

namespace Tests\Feature\Toshi\Platform;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Responses\Data\ToolCall;
use ReflectionProperty;
use Tests\TestCase;

/**
 * HTTP Complete Approval Flow for PlatformOperationsAgent (GET review + POST decisions).
 */
class PlatformOpsApprovalUiTest extends TestCase
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
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->siteadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin-ops-ui@test.sch.ug',
        ]);
    }

    public function test_get_shows_pending_create_plan_approval(): void
    {
        $this->actingAs($this->siteadmin);

        $paused = $this->pauseCreatePlan('Ui Visible Plan');

        $response = $this->get(route('superadmin.toshi.ops.show', $paused->conversationId));

        $response->assertOk();
        $response->assertSee('Waiting on approval', false);
        $response->assertSee('CreatePlanTool', false);
        $response->assertSee('Ui Visible Plan', false);
        $response->assertSee($paused->pendingApprovals->first()->id, false);
        $this->assertDatabaseMissing('plans', ['name' => 'Ui Visible Plan']);
    }

    public function test_post_approve_creates_plan(): void
    {
        $this->actingAs($this->siteadmin);

        $paused = $this->pauseCreatePlan('Http Approved Plan', amount: 777);
        $approvalId = $paused->pendingApprovals->first()->id;

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Plan created after HTTP approve.');

        $response = $this->postJson(route('superadmin.toshi.ops.store', $paused->conversationId), [
            'decisions' => [
                $approvalId => ['action' => 'approve'],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'complete');
        $response->assertJsonPath('conversation_id', $paused->conversationId);

        $this->assertDatabaseHas('plans', [
            'name' => 'Http Approved Plan',
            'amount' => 777,
        ]);
    }

    public function test_post_reject_does_not_create_plan(): void
    {
        $this->actingAs($this->siteadmin);

        $paused = $this->pauseCreatePlan('Http Rejected Plan');
        $approvalId = $paused->pendingApprovals->first()->id;

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Understood, plan not created.');

        $response = $this->postJson(route('superadmin.toshi.ops.store', $paused->conversationId), [
            'decisions' => [
                $approvalId => [
                    'action' => 'reject',
                    'result' => 'Keep existing pricing.',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'complete');
        $this->assertDatabaseMissing('plans', ['name' => 'Http Rejected Plan']);
    }

    public function test_post_edit_approves_with_modified_arguments(): void
    {
        $this->actingAs($this->siteadmin);

        $paused = $this->pauseCreatePlan('Http Edited Plan', amount: 100);
        $approvalId = $paused->pendingApprovals->first()->id;
        $args = $paused->pendingApprovals->first()->arguments;
        $args['amount'] = 999;
        $args['name'] = 'Http Edited Plan';

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Plan created with edited amount.');

        $response = $this->postJson(route('superadmin.toshi.ops.store', $paused->conversationId), [
            'decisions' => [
                $approvalId => [
                    'action' => 'edit',
                    'arguments' => $args,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'complete');
        $this->assertDatabaseHas('plans', [
            'name' => 'Http Edited Plan',
            'amount' => 999,
        ]);
    }

    public function test_non_platform_user_forbidden(): void
    {
        $this->actingAs($this->siteadmin);
        $paused = $this->pauseCreatePlan('Forbidden Plan');
        $approvalId = $paused->pendingApprovals->first()->id;

        // Same usergroup, but not on platform allowlist.
        $otherSiteadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin-ops-ui-blocked@test.sch.ug',
        ]);

        config([
            'toshi.platform_gate.enabled' => true,
            'toshi.platform_gate.user_ids' => [$this->siteadmin->id],
        ]);

        $this->actingAs($otherSiteadmin);

        $this->get(route('superadmin.toshi.ops.show', $paused->conversationId))
            ->assertForbidden();

        $this->postJson(route('superadmin.toshi.ops.store', $paused->conversationId), [
            'decisions' => [
                $approvalId => ['action' => 'approve'],
            ],
        ])->assertForbidden();

        $this->assertDatabaseMissing('plans', ['name' => 'Forbidden Plan']);
    }

    public function test_platform_gate_disabled_forbidden_even_for_siteadmin(): void
    {
        config(['toshi.platform_gate.enabled' => true]);

        $this->actingAs($this->siteadmin);
        $paused = $this->pauseCreatePlan('Gate Off Plan');

        config(['toshi.platform_gate.enabled' => false]);

        $this->get(route('superadmin.toshi.ops.show', $paused->conversationId))
            ->assertForbidden();
    }

    public function test_other_siteadmin_cannot_view_foreign_conversation(): void
    {
        config([
            'toshi.platform_gate.enabled' => true,
            'toshi.platform_gate.user_ids' => [],
        ]);

        $this->actingAs($this->siteadmin);
        $paused = $this->pauseCreatePlan('Foreign Conversation Plan');

        $peer = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin-ops-ui-peer@test.sch.ug',
        ]);

        $this->actingAs($peer);

        $this->get(route('superadmin.toshi.ops.show', $paused->conversationId))
            ->assertForbidden();
    }

    /**
     * @return \Laravel\Ai\Responses\AgentResponse
     */
    private function pauseCreatePlan(string $name, int|float $amount = 12345)
    {
        $args = [
            'cycle' => 'monthly',
            'name' => $name,
            'amount' => $amount,
            'no_of_users' => 10,
            'no_of_students' => 100,
            'is_active' => 1,
        ];

        PlatformOperationsAgent::fake([
            new ToolCall('call_ops_ui_'.md5($name), 'CreatePlanTool', $args),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt("Create billing plan {$name}.");

        $this->assertTrue($paused->hasPendingApprovals());
        $this->assertNotNull($paused->conversationId);

        return $paused;
    }

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
