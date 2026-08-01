<?php

namespace Tests\Feature\Toshi\Platform;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Ai\Approvals\Decision;
use App\Ai\Tools\Superadmin\CreatePlanTool;
use App\Ai\Tools\Superadmin\UpdatePlanTool;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\User;
use App\Services\Toshi\PlatformToolInvoker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
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

        $tool = app(CreatePlanTool::class);
        $args = $this->planArgs();
        $agent = new PlatformOperationsAgent;

        $pending = app(PlatformToolInvoker::class)->invoke($tool, $args, null, $agent);

        $this->assertSame('pending_approval', $pending['status']);
        $this->assertNotNull($pending['pending']);
        $this->assertStringContainsString('billing', strtolower((string) $pending['pending']->reason));
        $this->assertSame(0, Plan::where('name', 'BatchA Plan')->count());

        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('causer_id', $this->siteadmin->id)
                ->where('properties->status', 'pending_approval')
                ->where('properties->tool', 'CreatePlanTool')
                ->exists()
        );
    }

    public function test_create_plan_mutates_only_after_decision_approve(): void
    {
        $this->actingAs($this->siteadmin);

        $tool = app(CreatePlanTool::class);
        $args = $this->planArgs(['name' => 'Approved Plan', 'amount' => 99]);
        $agent = new PlatformOperationsAgent;
        $invoker = app(PlatformToolInvoker::class);

        $before = $invoker->invoke($tool, $args, null, $agent);
        $this->assertSame('pending_approval', $before['status']);
        $this->assertDatabaseMissing('plans', ['name' => 'Approved Plan']);

        $after = $invoker->invoke($tool, $args, Decision::approve(), $agent);

        $this->assertSame('executed', $after['status']);
        $this->assertStringContainsString('Plan created', $after['result']);
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
    }

    public function test_create_plan_blocked_on_reject(): void
    {
        $this->actingAs($this->siteadmin);

        $tool = app(CreatePlanTool::class);
        $args = $this->planArgs(['name' => 'Rejected Plan']);
        $agent = new PlatformOperationsAgent;

        $outcome = app(PlatformToolInvoker::class)->invoke(
            $tool,
            $args,
            Decision::reject('Keep existing pricing.'),
            $agent,
        );

        $this->assertSame('rejected', $outcome['status']);
        $this->assertStringContainsString('Rejected', $outcome['result']);
        $this->assertDatabaseMissing('plans', ['name' => 'Rejected Plan']);
    }

    public function test_direct_handle_without_approval_does_not_mutate(): void
    {
        $this->actingAs($this->siteadmin);

        $result = (string) app(CreatePlanTool::class)->handle(new Request($this->planArgs([
            'name' => 'Sneaky Plan',
        ])));

        $this->assertStringContainsString('requires human approval', $result);
        $this->assertDatabaseMissing('plans', ['name' => 'Sneaky Plan']);
    }

    public function test_create_plan_validation_rejection_after_approve(): void
    {
        $this->actingAs($this->siteadmin);

        $outcome = app(PlatformToolInvoker::class)->invoke(
            app(CreatePlanTool::class),
            [
                'cycle' => 'monthly',
                'name' => 'Bad Plan',
                // amount missing
                'no_of_users' => 1,
                'no_of_students' => 1,
            ],
            Decision::approve(),
            new PlatformOperationsAgent,
        );

        $this->assertSame('executed', $outcome['status']);
        $this->assertStringStartsWith('❌', $outcome['result']);
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

        $invoker = app(PlatformToolInvoker::class);
        $tool = app(UpdatePlanTool::class);
        $agent = new PlatformOperationsAgent;

        $pending = $invoker->invoke($tool, $args, null, $agent);
        $this->assertSame('pending_approval', $pending['status']);
        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'amount' => 10]);

        $done = $invoker->invoke($tool, $args, Decision::approve(), $agent);
        $this->assertSame('executed', $done['status']);
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Editable Updated',
            'amount' => 54321,
        ]);
    }
}
