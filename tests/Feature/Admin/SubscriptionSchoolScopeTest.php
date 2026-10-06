<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Subscriptions are scoped to the caller's school, updates never flip
 * ownership, and the policy checks school ownership.
 */
class SubscriptionSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private User $memberA;
    private User $adminB;
    private User $siteAdmin;
    private Plan $plan;
    private Subscription $subA;
    private Subscription $subB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = $this->createSchool('Sub School A');
        $this->schoolB = $this->createSchool('Sub School B');

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 3, 'email' => 'sub-admin-a@test.sch.ug',
        ]);
        $this->memberA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 3, 'email' => 'sub-member-a@test.sch.ug',
        ]);
        $this->adminB = User::factory()->create([
            'school_id' => $this->schoolB->id, 'usergroup_id' => 3, 'email' => 'sub-admin-b@test.sch.ug',
        ]);
        $this->siteAdmin = User::factory()->create([
            'school_id' => null, 'usergroup_id' => 1, 'email' => 'sub-site-admin@test.sch.ug',
        ]);

        $this->plan = $this->createPlan();

        $this->subA = Subscription::create([
            'school_id' => $this->schoolA->id, 'user_id' => $this->memberA->id,
            'plan_id' => $this->plan->id, 'status' => 'pending', 'amount_paid' => 0,
        ]);
        $this->subB = Subscription::create([
            'school_id' => $this->schoolB->id, 'user_id' => $this->adminB->id,
            'plan_id' => $this->plan->id, 'status' => 'pending', 'amount_paid' => 0,
        ]);
    }

    private function createSchool(string $name): School
    {
        return School::create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . random_int(1000, 9999),
            'email' => strtolower(str_replace(' ', '-', $name)) . '@test.sch.ug',
            'phone' => '+256700' . random_int(100000, 999999),
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
        ]);
    }

    private function createPlan(): Plan
    {
        return Plan::create([
            'name' => 'sub-plan-' . random_int(1000, 9999),
            'display_name' => 'Sub Plan',
            'cycle' => 30,
            'amount' => 15000,
            'is_active' => 1,
            'order' => 1,
            'no_of_users' => 5,
            'no_of_students' => 100,
            'no_of_events' => 10,
            'no_of_folders' => 2,
            'no_of_files' => 50,
            'no_of_videos' => 5,
            'no_of_audios' => 5,
            'no_of_bulletins' => 3,
            'no_of_groups' => 5,
        ]);
    }

    public function test_detail_route_of_another_schools_subscription_gets_a_404(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('admin.subscriptions.edit', $this->subB->id))
            ->assertNotFound();

        $this->actingAs($this->adminA)
            ->get(route('admin.subscriptions.edit', $this->subA->id))
            ->assertOk();
    }

    public function test_update_of_another_schools_subscription_gets_a_404_and_changes_nothing(): void
    {
        $this->actingAs($this->adminA)
            ->put(route('admin.subscriptions.update', $this->subB->id), [
                'plan_id' => $this->plan->id,
                'status' => 'approved',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $this->subB->id,
            'status' => 'pending',
            'school_id' => $this->schoolB->id,
        ]);
    }

    public function test_update_does_not_flip_ownership(): void
    {
        $response = $this->actingAs($this->adminA)
            ->put(route('admin.subscriptions.update', $this->subA->id), [
                'plan_id' => $this->plan->id,
                'status' => 'approved',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $this->subA->id,
            'school_id' => $this->schoolA->id,
            'user_id' => $this->memberA->id,
            'status' => 'approved',
        ]);
    }

    public function test_update_with_a_missing_plan_stays_a_validation_error(): void
    {
        $response = $this->actingAs($this->adminA)
            ->put(route('admin.subscriptions.update', $this->subA->id), [
                'plan_id' => 999999,
                'status' => 'pending',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('plan_id');

        $this->assertDatabaseHas('subscriptions', ['id' => $this->subA->id, 'status' => 'pending']);
    }

    public function test_policy_checks_school_ownership(): void
    {
        $this->assertFalse($this->adminA->can('view', $this->subB));
        $this->assertTrue($this->adminA->can('view', $this->subA));
        $this->assertTrue($this->siteAdmin->can('view', $this->subB));
    }

    public function test_plan_submission_flow_still_works_for_the_own_school(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('admin.subscriptions.store'), [
                'plan_id' => $this->plan->id,
                'payment_reference' => 'TEST-REF-1',
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'school_id' => $this->schoolA->id,
            'user_id' => $this->adminA->id,
        ]);
    }
}
