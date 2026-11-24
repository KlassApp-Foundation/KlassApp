<?php

namespace Tests\Feature\Onboarding;

use App\Models\School;
use App\Onboarding\OnboardingDpaGate;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OnboardingDpaGateTest extends TestCase
{
    use RefreshDatabase;

    private function school(array $overrides = []): School
    {
        // These columns are gate-owned, not fillable: set them after create.
        $requiredAt = $overrides['onboarding_dpa_required_at'] ?? null;
        unset($overrides['onboarding_dpa_required_at']);

        $school = School::create(array_merge([
            'name' => 'DPA Gate School',
            'email' => 'dpa.gate@example.test',
            'phone' => '+256700777001',
            'slug' => 'dpa-gate-school',
            'status' => 1,
            'registration_country' => 'Uganda',
            'curriculum' => 'uneb',
        ], $overrides));

        if ($requiredAt !== null) {
            $school->forceFill(['onboarding_dpa_required_at' => $requiredAt])->save();
            $school = $school->fresh();
        }

        return $school;
    }

    public function test_a_real_school_mid_onboarding_cannot_write_before_confirming(): void
    {
        $school = $this->school(['onboarding_dpa_required_at' => now()]);

        $this->expectException(ValidationException::class);

        app(OnboardingEngine::class)->saveSchoolName($school, 'Renamed School');
    }

    public function test_the_same_write_is_allowed_once_the_dpa_is_recorded(): void
    {
        $school = $this->school(['onboarding_dpa_required_at' => now()]);
        app(OnboardingDpaGate::class)->record($school);

        app(OnboardingEngine::class)->saveSchoolName($school->fresh(), 'Renamed School');

        $this->assertSame('Renamed School', $school->fresh()->name);
        $this->assertSame(OnboardingDpaGate::VERSION, $school->fresh()->onboarding_dpa_version);
    }

    public function test_a_school_that_has_not_started_onboarding_is_not_blocked(): void
    {
        $school = $this->school();

        app(OnboardingEngine::class)->saveSchoolName($school, 'Started Later');

        $this->assertSame('Started Later', $school->fresh()->name);
    }

    public function test_demo_and_test_schools_are_exempt(): void
    {
        foreach (['is_demo', 'is_test'] as $index => $flag) {
            $school = $this->school([
                'name' => 'Exempt '.$flag,
                'email' => 'exempt.'.$flag.'@example.test',
                'phone' => '+25670077701'.$index,
                'slug' => 'exempt-'.$flag,
                'onboarding_dpa_required_at' => now(),
            ]);

            // is_demo / is_test are gate inputs, not fillable.
            $school->forceFill([$flag => 1])->save();

            app(OnboardingEngine::class)->saveSchoolName($school->fresh(), 'Renamed '.$flag);

            $this->assertSame('Renamed '.$flag, $school->fresh()->name);
        }
    }
}
