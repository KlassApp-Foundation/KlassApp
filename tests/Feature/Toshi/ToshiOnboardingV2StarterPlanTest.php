<?php

namespace Tests\Feature\Toshi;

use App\Models\Plan;
use App\Onboarding\Steps\Steps\PlanSelectionStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToshiOnboardingV2StarterPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_selects_the_freemium_plan(): void
    {
        $freemium = Plan::create([
            'name' => 'freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);
        Plan::create([
            'name' => 'growth',
            'display_name' => 'Growth',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 50,
            'order' => 2,
            'is_active' => 1,
        ]);

        $step = app(PlanSelectionStep::class);

        $this->assertSame((int) $freemium->id, $step->normalize('Starter'));
        $this->assertSame((int) $freemium->id, $step->normalize('freemium'));
        $this->assertSame(0, $step->normalize('not-a-plan'));
    }
}
