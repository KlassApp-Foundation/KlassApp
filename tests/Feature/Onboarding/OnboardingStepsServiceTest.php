<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnboardingStepsServiceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Steps Test School',
            'email' => 'steps@test.sch.ug',
            'phone' => '0700000000',
            'slug' => 'steps-test-school',
            'status' => 1,
            'curriculum' => 'uneb',
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'Current Academic Year',
        ]);
    }

    /** @test */
    public function standards_step_not_complete_when_only_phase_standard_exists(): void
    {
        // Regression test for Finding 3:
        // Previously, isStepComplete('standards') checked Standard::where('school_id', ...)->exists().
        // Standard is the phase row (e.g. 'primary'), not actual classes.
        // This test proves a phase row alone does NOT make the step complete.

        // Create only a phase Standard (e.g. 'primary') — no Section, no StandardLink
        Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => '1',
        ]);

        $this->assertFalse(
            OnboardingStepsService::isStepComplete('standards', $this->school),
            'standards step must NOT be complete when only a phase Standard row exists'
        );
    }

    /** @test */
    public function standards_step_not_complete_when_section_exists_without_standard_link(): void
    {
        // Edge case: A Section row exists but no StandardLink.
        // This should not count as complete because the class isn't linked to a curriculum phase.

        Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => '1',
        ]);

        Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'status' => '1',
        ]);

        $this->assertFalse(
            OnboardingStepsService::isStepComplete('standards', $this->school),
            'standards step must NOT be complete when Section exists without StandardLink'
        );
    }

    /** @test */
    public function standards_step_complete_when_standard_link_exists(): void
    {
        // The correct check: StandardLink rows exist for the school.

        $phase = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => '1',
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'status' => '1',
        ]);

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => AcademicYear::where('school_id', $this->school->id)->first()->id,
            'standard_id' => $phase->id,
            'section_id' => $section->id,
            'status' => '1',
        ]);

        $this->assertTrue(
            OnboardingStepsService::isStepComplete('standards', $this->school),
            'standards step must be complete when at least one StandardLink exists'
        );
    }

    /** @test */
    public function other_steps_still_work_correctly(): void
    {
        // Sanity check that we didn't break the other step completions

        // curriculum: based on school->curriculum being set (already 'uneb' in setUp)
        $this->assertTrue(
            OnboardingStepsService::isStepComplete('curriculum', $this->school),
            'curriculum step should be complete when school has a curriculum set'
        );

        // subjects: no subjects exist yet
        $this->assertFalse(
            OnboardingStepsService::isStepComplete('subjects', $this->school),
            'subjects step should not be complete when no subjects exist'
        );

        // terms: no terms exist yet
        $this->assertFalse(
            OnboardingStepsService::isStepComplete('terms', $this->school),
            'terms step should not be complete when no terms exist'
        );

        // fees: no fees exist yet
        $this->assertFalse(
            OnboardingStepsService::isStepComplete('fees', $this->school),
            'fees step should not be complete when no fees exist'
        );

        // unknown step
        $this->assertFalse(
            OnboardingStepsService::isStepComplete('nonexistent', $this->school),
            'unknown step should return false'
        );
    }
}
