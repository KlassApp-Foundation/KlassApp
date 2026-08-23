<?php

namespace Tests\Feature\Admin;

use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionControllerCrossTenantCurrentPlanTest extends TestCase
{
    use RefreshDatabase;

    private function createSchool(string $name): School
    {
        return School::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)) . '@test.sch.ug',
            'phone' => '+256700' . random_int(100000, 999999),
            'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . random_int(1000, 9999),
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
        ]);
    }

    private function createPlan(string $name, string $displayName): Plan
    {
        return Plan::create([
            'name' => $name,
            'display_name' => $displayName,
            'cycle' => 30,
            'amount' => 0,
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

    public function test_admin_sees_only_their_schools_non_running_current_plan(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');

        $adminA = User::factory()->create([
            'school_id' => $schoolA->id,
            'usergroup_id' => 3,
            'email' => 'admin-a@test.sch.ug',
            'name' => 'Admin A',
        ]);

        User::factory()->create([
            'school_id' => $schoolB->id,
            'usergroup_id' => 3,
            'email' => 'admin-b@test.sch.ug',
            'name' => 'Admin B',
        ]);

        $planA = $this->createPlan('alpha-plan', 'Alpha');
        $planB = $this->createPlan('beta-plan', 'Beta');

        CurrentPlan::create(['school_id' => $schoolA->id, 'plan_id' => $planA->id, 'status' => 'expired']);
        CurrentPlan::create(['school_id' => $schoolB->id, 'plan_id' => $planB->id, 'status' => 'expired']);

        $response = $this->actingAs($adminA)->get('/admin/subscriptions');

        $response->assertOk();
        $response->assertSee('Your current plan is');
        $response->assertSee('alpha-plan');
        $response->assertDontSee('beta-plan');
    }

    public function test_admin_without_non_running_current_plan_renders_without_crashing(): void
    {
        $school = $this->createSchool('Empty Plan School');

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => 'admin-empty@test.sch.ug',
            'name' => 'Empty Admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/subscriptions');

        $response->assertOk();
        $response->assertSee('No current plan selected');
    }
}
