<?php

namespace Tests\Feature\Superadmin;

use App\Models\Country;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Superadmin\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubscriptionCurrentPlanTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private Plan $freePlan;
    private Plan $paidPlan;
    private User $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'CurrentPlan Test School ' . uniqid(),
            'email' => 'cptest.' . uniqid() . '@example.com',
            'phone' => '0700111222',
            'slug' => 'cptest-' . uniqid(),
            'status' => 1,
        ]);

        $this->freePlan = Plan::create([
            'cycle' => 'monthly',
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'order' => 1,
            'is_active' => 1,
            'amount' => 0,
            'no_of_users' => 50,
            'no_of_students' => 100,
        ]);

        $this->paidPlan = Plan::create([
            'cycle' => 'monthly',
            'name' => 'Growth',
            'display_name' => 'Growth',
            'order' => 2,
            'is_active' => 1,
            'amount' => 35,
            'no_of_users' => 10,
            'no_of_students' => 100,
        ]);

        $this->subscriber = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);
    }

    private function createArgs(array $overrides = []): array
    {
        return array_merge([
            'user_id' => $this->subscriber->id,
            'plan_id' => $this->freePlan->id,
            'school_id' => $this->school->id,
            'status' => 'pending',
        ], $overrides);
    }

    // ── Service::approve() (Filament action) ──

    public function test_approve_free_subscription_creates_current_plan(): void
    {
        $subscription = Subscription::create($this->createArgs([
            'plan_id' => $this->freePlan->id,
        ]));

        $this->assertSame(0, CurrentPlan::where('school_id', $this->school->id)->count());

        app(SubscriptionService::class)->approve($subscription->id);

        $this->assertSame('approved', $subscription->fresh()->status);
        $this->assertDatabaseHas('current_plans', [
            'school_id' => $this->school->id,
            'plan_id' => $this->freePlan->id,
        ]);
    }

    public function test_approve_paid_subscription_starts_trial(): void
    {
        $subscription = Subscription::create($this->createArgs([
            'plan_id' => $this->paidPlan->id,
        ]));

        $this->assertSame(0, CurrentPlan::where('school_id', $this->school->id)->count());

        app(SubscriptionService::class)->approve($subscription->id);

        $this->assertDatabaseHas('current_plans', [
            'school_id' => $this->school->id,
            'plan_id' => $this->paidPlan->id,
            'is_trial' => true,
            'status' => 'running',
        ]);
        $this->assertNotNull(
            CurrentPlan::where('school_id', $this->school->id)->first()->trial_ends_at
        );
    }

    public function test_approve_does_not_overwrite_existing_current_plan(): void
    {
        CurrentPlan::create([
            'school_id' => $this->school->id,
            'plan_id' => $this->paidPlan->id,
            'status' => 'running',
            'is_trial' => true,
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
        ]);

        $subscription = Subscription::create($this->createArgs([
            'plan_id' => $this->freePlan->id,
        ]));

        app(SubscriptionService::class)->approve($subscription->id);

        // Existing CurrentPlan should be untouched — plan_id should still be paidPlan.
        $this->assertDatabaseHas('current_plans', [
            'school_id' => $this->school->id,
            'plan_id' => $this->paidPlan->id,
        ]);
        $this->assertDatabaseMissing('current_plans', [
            'school_id' => $this->school->id,
            'plan_id' => $this->freePlan->id,
        ]);
    }

    // ── Service::create() (SubscriptionForm) ──

    public function test_create_with_approved_status_creates_current_plan(): void
    {
        $this->assertSame(0, CurrentPlan::where('school_id', $this->school->id)->count());

        app(SubscriptionService::class)->create($this->createArgs([
            'plan_id' => $this->freePlan->id,
            'status' => 'approved',
        ]));

        $this->assertDatabaseHas('current_plans', [
            'school_id' => $this->school->id,
            'plan_id' => $this->freePlan->id,
        ]);
    }

    public function test_create_with_pending_status_does_not_create_current_plan(): void
    {
        $this->assertSame(0, CurrentPlan::where('school_id', $this->school->id)->count());

        app(SubscriptionService::class)->create($this->createArgs([
            'plan_id' => $this->freePlan->id,
            'status' => 'pending',
        ]));

        $this->assertSame(0, CurrentPlan::where('school_id', $this->school->id)->count());
    }

    // ── Service::update() (SubscriptionForm edit) ──

    public function test_update_to_approved_creates_current_plan(): void
    {
        $subscription = Subscription::create($this->createArgs([
            'plan_id' => $this->freePlan->id,
            'status' => 'pending',
        ]));

        $this->assertSame(0, CurrentPlan::where('school_id', $this->school->id)->count());

        app(SubscriptionService::class)->update($subscription->id, $this->createArgs([
            'plan_id' => $this->paidPlan->id,
            'status' => 'approved',
        ]));

        $this->assertSame('approved', $subscription->fresh()->status);
        $this->assertDatabaseHas('current_plans', [
            'school_id' => $this->school->id,
            'plan_id' => $this->paidPlan->id,
        ]);
    }

    public function test_update_staying_pending_does_not_create_current_plan(): void
    {
        $subscription = Subscription::create($this->createArgs([
            'plan_id' => $this->freePlan->id,
            'status' => 'pending',
        ]));

        app(SubscriptionService::class)->update($subscription->id, $this->createArgs([
            'plan_id' => $this->paidPlan->id,
            'status' => 'pending',
        ]));

        $this->assertSame(0, CurrentPlan::where('school_id', $this->school->id)->count());
    }
}
