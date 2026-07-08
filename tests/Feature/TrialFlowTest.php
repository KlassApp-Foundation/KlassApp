<?php

namespace Tests\Feature;

use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Services\TrialService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialFlowTest extends TestCase
{
    use RefreshDatabase;

    private Plan $freemium;
    private Plan $growth;
    private Plan $premium;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freemium = Plan::create([
            'cycle' => 30, 'name' => 'freemium', 'display_name' => 'Freemium',
            'amount' => 0, 'is_active' => 1, 'order' => 1,
        ]);
        $this->growth = Plan::create([
            'cycle' => 30, 'name' => 'growth', 'display_name' => 'Growth',
            'amount' => 49, 'is_active' => 1, 'order' => 2,
        ]);
        $this->premium = Plan::create([
            'cycle' => 30, 'name' => 'premium', 'display_name' => 'Premium',
            'amount' => 0, 'is_custom_pricing' => 1, 'is_active' => 1, 'order' => 3,
        ]);
    }

    public function test_growth_starts_trial(): void
    {
        $school = School::create([
            'name' => 'Trial Test School', 'email' => 'trial@test.com',
            'phone' => '771234560', 'slug' => 'trial-test',
            'status' => 1,
        ]);

        TrialService::startTrial($school->id, $this->growth->id);

        $cp = CurrentPlan::where('school_id', $school->id)->first();
        $this->assertNotNull($cp);
        $this->assertTrue((bool)$cp->is_trial);
        $this->assertNotNull($cp->trial_ends_at);
        $this->assertEquals($this->growth->id, $cp->plan_id);
        $this->assertEquals(
            Carbon::now()->addDays(30)->toDateString(),
            Carbon::parse($cp->trial_ends_at)->toDateString()
        );
    }

    public function test_premium_does_not_start_trial(): void
    {
        $school = School::create([
            'name' => 'Premium No Trial', 'email' => 'premium@test.com',
            'phone' => '771234561', 'slug' => 'premium-no-trial',
            'status' => 1,
        ]);

        CurrentPlan::create([
            'school_id' => $school->id,
            'plan_id' => $this->premium->id,
        ]);

        $cp = CurrentPlan::where('school_id', $school->id)->first();
        $this->assertNotNull($cp);
        $this->assertFalse((bool)$cp->is_trial);
        $this->assertNull($cp->trial_ends_at);
        $this->assertEquals($this->premium->id, $cp->plan_id);
    }

    public function test_freemium_does_not_start_trial(): void
    {
        $school = School::create([
            'name' => 'Freemium No Trial', 'email' => 'freemium@test.com',
            'phone' => '771234562', 'slug' => 'freemium-no-trial',
            'status' => 1,
        ]);

        CurrentPlan::create([
            'school_id' => $school->id,
            'plan_id' => $this->freemium->id,
        ]);

        $cp = CurrentPlan::where('school_id', $school->id)->first();
        $this->assertNotNull($cp);
        $this->assertFalse((bool)$cp->is_trial);
        $this->assertNull($cp->trial_ends_at);
        $this->assertEquals($this->freemium->id, $cp->plan_id);
    }

    public function test_trial_service_rejects_free_plan(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $school = School::create([
            'name' => 'Reject Test', 'email' => 'reject@test.com',
            'phone' => '771234563', 'slug' => 'reject-test',
            'status' => 1,
        ]);

        TrialService::startTrial($school->id, $this->freemium->id);
    }

    public function test_is_active_trial_returns_true_during_trial(): void
    {
        $school = School::create([
            'name' => 'Active Trial', 'email' => 'active@test.com',
            'phone' => '771234564', 'slug' => 'active-trial',
            'status' => 1,
        ]);

        TrialService::startTrial($school->id, $this->growth->id);
        $this->assertTrue(TrialService::isActiveTrial($school->id));
    }

    public function test_is_active_trial_returns_false_for_no_trial(): void
    {
        $school = School::create([
            'name' => 'No Trial', 'email' => 'notrial@test.com',
            'phone' => '771234565', 'slug' => 'no-trial',
            'status' => 1,
        ]);

        CurrentPlan::create([
            'school_id' => $school->id,
            'plan_id' => $this->freemium->id,
        ]);

        $this->assertFalse(TrialService::isActiveTrial($school->id));
    }
}
