<?php

namespace Tests\Feature\Toshi\Platform;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Ai\Tools\Superadmin\ApproveSubscriptionTool;
use App\Ai\Tools\Superadmin\CreateSubscriptionTool;
use App\Models\ActivityLog;
use App\Models\City;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use ReflectionProperty;
use Tests\TestCase;

class PlatformSubscriptionToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $siteadmin;

    private User $subscriber;

    private Plan $plan;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.platform_gate.enabled' => true,
            'toshi.platform_gate.user_ids' => [],
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->siteadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin-sub@test.sch.ug',
        ]);

        $country = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'iso_code' => 'UG',
            'tel_prefix' => '+256',
            'status' => 1,
        ]);

        $city = City::create([
            'country_id' => $country->id,
            'name' => 'Kampala',
            'status' => 1,
            'state_id' => 0,
        ]);

        $this->school = School::create([
            'name' => 'Sub Test School',
            'email' => 'sub.school.'.uniqid().'@example.com',
            'phone' => '0700111222',
            'address' => 'Plot 1',
            'city_id' => $city->id,
            'country_id' => $country->id,
            'pincode' => 25601,
            'status' => 1,
        ]);

        $this->plan = Plan::create([
            'cycle' => 'monthly',
            'name' => 'Growth',
            'display_name' => 'Growth',
            'order' => 1,
            'is_active' => 1,
            'amount' => 35,
            'no_of_users' => 10,
            'no_of_students' => 100,
        ]);

        $this->subscriber = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'email' => 'subscriber@test.sch.ug',
            'status' => 'active',
        ]);
    }

    private function createArgs(array $overrides = []): array
    {
        return array_merge([
            'user_id' => $this->subscriber->id,
            'plan_id' => $this->plan->id,
            'school_id' => $this->school->id,
            'status' => 'pending',
        ], $overrides);
    }

    public function test_create_subscription_happy_path_db_and_audit(): void
    {
        $this->actingAs($this->siteadmin);

        $args = $this->createArgs();

        PlatformOperationsAgent::fake([
            new ToolCall('call_sub_create', 'CreateSubscriptionTool', $args),
            'Subscription created.',
        ]);

        $response = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Create a pending subscription.');

        $this->assertFalse($response->hasPendingApprovals());
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->subscriber->id,
            'plan_id' => $this->plan->id,
            'school_id' => $this->school->id,
            'status' => 'pending',
        ]);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('causer_id', $this->siteadmin->id)
                ->where('properties->tool', 'CreateSubscriptionTool')
                ->where('properties->status', 'success')
                ->exists()
        );
    }

    public function test_create_subscription_validation_rejection(): void
    {
        $this->actingAs($this->siteadmin);

        $result = (string) app(CreateSubscriptionTool::class)->handle(new Request([
            'user_id' => 999999,
            'plan_id' => $this->plan->id,
            'school_id' => $this->school->id,
            'status' => 'pending',
        ]));

        $this->assertStringStartsWith('❌', $result);
        $this->assertSame(0, Subscription::count());
    }

    public function test_approve_subscription_pending_before_approve_no_mutation(): void
    {
        $this->actingAs($this->siteadmin);

        $subscription = Subscription::create($this->createArgs());

        PlatformOperationsAgent::fake([
            new ToolCall('call_sub_approve_pending', 'ApproveSubscriptionTool', [
                'id' => $subscription->id,
            ]),
        ]);

        $response = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Approve the subscription.');

        $this->assertTrue($response->hasPendingApprovals());
        $pending = $response->pendingApprovals->first();
        $this->assertSame('ApproveSubscriptionTool', $pending->tool);
        $this->assertStringContainsString('billing', strtolower((string) $pending->reason));
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'pending',
        ]);
        $this->assertNull($subscription->fresh()->start_date);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->status', 'pending_approval')
                ->where('properties->tool', 'ApproveSubscriptionTool')
                ->exists()
        );
    }

    public function test_approve_subscription_mutates_only_after_decision_approve_resume(): void
    {
        $this->actingAs($this->siteadmin);

        $subscription = Subscription::create($this->createArgs());

        PlatformOperationsAgent::fake([
            new ToolCall('call_sub_approve', 'ApproveSubscriptionTool', [
                'id' => $subscription->id,
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Approve subscription.');

        $this->assertTrue($paused->hasPendingApprovals());
        $approvalId = $paused->pendingApprovals->first()->id;

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Subscription approved.');

        $resumed = (new PlatformOperationsAgent)
            ->continue($paused->conversationId, as: $this->siteadmin)
            ->prompt(Decisions::from([
                $approvalId => Decision::approve(),
            ]));

        $this->assertFalse($resumed->hasPendingApprovals());
        $fresh = $subscription->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertNotNull($fresh->start_date);
        $this->assertNotNull($fresh->end_date);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ApproveSubscriptionTool')
                ->where('properties->status', 'success')
                ->exists()
        );
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ApproveSubscriptionTool')
                ->where('properties->status', 'approval_resolved')
                ->exists()
        );
    }

    public function test_approve_subscription_blocked_on_reject(): void
    {
        $this->actingAs($this->siteadmin);

        $subscription = Subscription::create($this->createArgs());

        PlatformOperationsAgent::fake([
            new ToolCall('call_sub_reject', 'ApproveSubscriptionTool', [
                'id' => $subscription->id,
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Approve subscription.');

        $approvalId = $paused->pendingApprovals->first()->id;

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Understood, not approved.');

        (new PlatformOperationsAgent)
            ->continue($paused->conversationId, as: $this->siteadmin)
            ->prompt(Decisions::from([
                $approvalId => Decision::reject('Keep pending.'),
            ]));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'pending',
        ]);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ApproveSubscriptionTool')
                ->where('properties->status', 'approval_rejected')
                ->exists()
        );
    }

    public function test_approve_non_pending_validation_via_handle(): void
    {
        $this->actingAs($this->siteadmin);

        $subscription = Subscription::create($this->createArgs(['status' => 'approved']));

        $result = (string) app(ApproveSubscriptionTool::class)->handle(new Request([
            'id' => $subscription->id,
        ]));

        $this->assertStringStartsWith('❌', $result);
        $this->assertStringContainsString('not pending', $result);
    }

    public function test_cancel_subscription_approvable_flow(): void
    {
        $this->actingAs($this->siteadmin);

        $subscription = Subscription::create($this->createArgs(['status' => 'approved']));

        PlatformOperationsAgent::fake([
            new ToolCall('call_sub_cancel', 'CancelSubscriptionTool', [
                'id' => $subscription->id,
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Cancel subscription.');

        $this->assertTrue($paused->hasPendingApprovals());
        $this->assertSame('CancelSubscriptionTool', $paused->pendingApprovals->first()->tool);

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Subscription canceled.');

        (new PlatformOperationsAgent)
            ->continue($paused->conversationId, as: $this->siteadmin)
            ->prompt(Decisions::from([
                $paused->pendingApprovals->first()->id => Decision::approve(),
            ]));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'canceled',
        ]);
    }

    public function test_needs_approval_returns_approval_instance(): void
    {
        $tool = app(ApproveSubscriptionTool::class);
        $approval = $tool->shouldRequestApproval(new Request(['id' => 42]));

        $this->assertNotNull($approval);
        $this->assertStringContainsString('42', (string) $approval->reason);
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
