<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SavePlanTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private Plan $freePlan;
    private Plan $paidPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Test School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
            'registration_country' => 'Uganda',
            'curriculum' => 'uneb',
            'school_category' => 'primary',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'type' => 'core',
            'status' => 1,
        ]);

        AcademicTerm::create([
            'school_id' => $this->school->id,
            'name' => 'Term 1',
            'academic_year_id' => $this->year->id,
            'status' => 'current',
        ]);

        // Seed usergroups
        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create a free plan
        $this->freePlan = Plan::create([
            'cycle' => 1,
            'name' => 'freemium',
            'display_name' => 'Freemium',
            'order' => 1,
            'is_active' => 1,
            'amount' => 0,
        ]);

        // Create a paid plan
        $this->paidPlan = Plan::create([
            'cycle' => 1,
            'name' => 'premium',
            'display_name' => 'Premium',
            'order' => 2,
            'is_active' => 1,
            'amount' => 50000,
        ]);
    }

    public function test_selects_free_plan_creates_current_plan_running(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->savePlan($this->school, $this->freePlan->id, skipCompletionCheck: true);

        $this->assertInstanceOf(CurrentPlan::class, $result);
        $this->assertEquals($this->school->id, $result->school_id);
        $this->assertEquals($this->freePlan->id, $result->plan_id);
        $this->assertEquals('running', $result->status);
        $this->assertFalse((bool) $result->is_trial);
    }

    public function test_selects_paid_plan_starts_trial(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->savePlan($this->school, $this->paidPlan->id, skipCompletionCheck: true);

        $this->assertInstanceOf(CurrentPlan::class, $result);
        $this->assertEquals($this->school->id, $result->school_id);
        $this->assertEquals($this->paidPlan->id, $result->plan_id);
        $this->assertEquals('running', $result->status);
        $this->assertTrue((bool) $result->is_trial);
        $this->assertNotNull($result->trial_ends_at);
    }

    public function test_rejects_inactive_plan(): void
    {
        $engine = app(OnboardingEngine::class);

        $inactivePlan = Plan::create([
            'cycle' => 1,
            'name' => 'inactive_plan',
            'display_name' => 'Inactive',
            'order' => 99,
            'is_active' => 0,
            'amount' => 1000,
        ]);

        $this->expectException(ValidationException::class);
        $engine->savePlan($this->school, $inactivePlan->id, skipCompletionCheck: true);
    }

    public function test_rejects_nonexistent_plan(): void
    {
        $engine = app(OnboardingEngine::class);

        $this->expectException(ValidationException::class);
        $engine->savePlan($this->school, 99999, skipCompletionCheck: true);
    }

    public function test_blocks_plan_selection_with_incomplete_steps(): void
    {
        // Create a school that is NOT complete (empty name placeholder)
        $incompleteSchool = School::create([
            'name' => "Test's School",
            'email' => Str::random(8).'@incomplete.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $engine = app(OnboardingEngine::class);

        $this->expectException(ValidationException::class);
        $engine->savePlan($incompleteSchool, $this->freePlan->id, skipCompletionCheck: false);
    }

    public function test_skips_completion_check_when_flag_is_true(): void
    {
        // Even an incomplete school can select a plan with skipCompletionCheck=true
        $incompleteSchool = School::create([
            'name' => "Test's School",
            'email' => Str::random(8).'@incomplete.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $engine = app(OnboardingEngine::class);

        // Should NOT throw
        $result = $engine->savePlan($incompleteSchool, $this->freePlan->id, skipCompletionCheck: true);
        $this->assertInstanceOf(CurrentPlan::class, $result);
    }

    public function test_updates_existing_current_plan(): void
    {
        $engine = app(OnboardingEngine::class);

        // Select free plan first
        $first = $engine->savePlan($this->school, $this->freePlan->id, skipCompletionCheck: true);

        // Now "upgrade" to paid plan — should update, not create a second row
        $updated = $engine->savePlan($this->school, $this->paidPlan->id, skipCompletionCheck: true);

        $this->assertEquals($first->id, $updated->id);
        $this->assertEquals($this->paidPlan->id, $updated->plan_id);
        $this->assertTrue((bool) $updated->is_trial);

        // Only one CurrentPlan row for this school
        $this->assertEquals(1, CurrentPlan::where('school_id', $this->school->id)->count());
    }

    public function test_paid_plan_trial_ends_at_is_30_days_from_now(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->savePlan($this->school, $this->paidPlan->id, skipCompletionCheck: true);

        $this->assertNotNull($result->trial_ends_at);
        $endsAt = $result->trial_ends_at;
        $expectedEnd = now()->addDays(30);

        // Allow 5 seconds of drift
        $this->assertEqualsWithDelta($expectedEnd->timestamp, $endsAt->timestamp, 5);
    }
}
