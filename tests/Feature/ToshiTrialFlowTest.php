<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\School;
use App\Livewire\AgentToshi;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToshiTrialFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert(['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('usergroups')->insert(['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('usergroups')->insert(['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function makePlan(string $name, int $amount, int $students): Plan
    {
        return Plan::create([
            'name' => $name,
            'amount' => $amount,
            'no_of_students' => $students,
            'cycle' => 365,
            'display_name' => ucfirst($name),
            'is_active' => true,
        ]);
    }

    public function test_selectPlan_sets_selectedPlanId(): void
    {
        $plan = $this->makePlan('standard', 45000, 300);

        $component = Livewire::test(AgentToshi::class);
        $component->call('selectPlan', $plan->id)
            ->assertSet('selectedPlanId', $plan->id);
    }

    public function test_selectPlan_with_free_plan_works(): void
    {
        $plan = $this->makePlan('free', 0, 10);

        $component = Livewire::test(AgentToshi::class);
        $component->call('selectPlan', $plan->id)
            ->assertSet('selectedPlanId', $plan->id);
    }

    public function test_onboarding_with_paid_plan_creates_trial(): void
    {
        $plan = $this->makePlan('standard', 45000, 300);

        $component = Livewire::test(AgentToshi::class);
        $component->set('selectedPlanId', $plan->id);
        $component->set('step', 14);
        $component->set('schoolName', 'Test Toshi Trial');
        $component->set('schoolType', 'primary');
        $component->set('schoolCountry', 'Uganda');
        $component->set('academicYearLabel', '2026');
        $component->set('adminName', 'Toshi Admin');
        $component->set('adminEmail', 'toshi-trial@test.internal');
        $component->set('adminPhone', '+256700000001');
        $component->set('password', 'password123');
        $component->set('standards', [['name' => 'Primary One']]);
        $component->set('subjects', ['Primary One' => ['Mathematics']]);
        $component->call('commitAll');

        $school = School::where('name', 'Test Toshi Trial')->first();
        $this->assertNotNull($school);

        $cp = \App\Models\CurrentPlan::where('school_id', $school->id)->first();
        $this->assertNotNull($cp);
        $this->assertTrue((bool) $cp->is_trial);
        $this->assertEquals($plan->id, $cp->plan_id);
        $this->assertNotNull($cp->trial_ends_at);
        $this->assertTrue(now()->lessThan($cp->trial_ends_at));
    }

    public function test_onboarding_with_premium_plan_creates_trial(): void
    {
        $plan = $this->makePlan('premium', 150000, 10000);

        $component = Livewire::test(AgentToshi::class);
        $component->set('selectedPlanId', $plan->id);
        $component->set('step', 14);
        $component->set('schoolName', 'Test Toshi Premium');
        $component->set('schoolType', 'primary');
        $component->set('schoolCountry', 'Uganda');
        $component->set('academicYearLabel', '2026');
        $component->set('adminName', 'Toshi Admin');
        $component->set('adminEmail', 'toshi-premium@test.internal');
        $component->set('adminPhone', '+256700000003');
        $component->set('password', 'password123');
        $component->set('standards', [['name' => 'Primary One']]);
        $component->set('subjects', ['Primary One' => ['Mathematics']]);
        $component->call('commitAll');

        $school = School::where('name', 'Test Toshi Premium')->first();
        $this->assertNotNull($school);

        $cp = \App\Models\CurrentPlan::where('school_id', $school->id)->first();
        $this->assertNotNull($cp);
        $this->assertTrue((bool) $cp->is_trial);
        $this->assertEquals($plan->id, $cp->plan_id);
        $this->assertEquals(10000, $cp->plan->no_of_students);
    }

    public function test_onboarding_with_free_plan_does_not_create_trial(): void
    {
        $plan = $this->makePlan('free', 0, 10);

        $component = Livewire::test(AgentToshi::class);
        $component->set('selectedPlanId', $plan->id);
        $component->set('step', 14);
        $component->set('schoolName', 'Test Toshi Free');
        $component->set('schoolType', 'primary');
        $component->set('schoolCountry', 'Uganda');
        $component->set('academicYearLabel', '2026');
        $component->set('adminName', 'Toshi Free Admin');
        $component->set('adminEmail', 'toshi-free@test.internal');
        $component->set('adminPhone', '+256700000002');
        $component->set('password', 'password123');
        $component->set('standards', [['name' => 'Primary One']]);
        $component->set('subjects', ['Primary One' => ['Mathematics']]);
        $component->call('commitAll');

        $school = School::where('name', 'Test Toshi Free')->first();
        $this->assertNotNull($school);

        $cp = \App\Models\CurrentPlan::where('school_id', $school->id)->first();
        $this->assertNotNull($cp);
        $this->assertFalse((bool) $cp->is_trial);
        $this->assertEquals($plan->id, $cp->plan_id);
    }

    public function test_selectPlan_advances_step(): void
    {
        $plan = $this->makePlan('premium', 150000, 10000);

        $component = Livewire::test(AgentToshi::class);
        $component->call('selectPlan', $plan->id)
            ->assertSet('selectedPlanId', $plan->id);
    }
}
