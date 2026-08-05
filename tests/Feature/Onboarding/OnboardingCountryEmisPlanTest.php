<?php

namespace Tests\Feature\Onboarding;

use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers Part A (country / EMIS / UNEB centre steps) and Part B (end-of-flow
 * plan selection + suggestion) at the service layer.
 */
class OnboardingCountryEmisPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function makeSchool(array $overrides = []): School
    {
        $uid = uniqid();

        return School::create(array_merge([
            'name' => 'Country Test School ' . $uid,
            'email' => "country-{$uid}@test.sch.ug",
            'phone' => '070' . substr((string) mt_rand(1000000, 9999999), 0, 7),
            'slug' => 'country-test-school-' . $uid,
            'status' => 1,
            'curriculum' => 'uneb',
        ], $overrides));
    }

    // ── isUganda / isUnebCurriculum ────────────────────────────────

    /** @test */
    public function is_uganda_matches_name_and_code_case_insensitively(): void
    {
        $this->assertTrue(OnboardingStepsService::isUganda('Uganda'));
        $this->assertTrue(OnboardingStepsService::isUganda('uganda'));
        $this->assertTrue(OnboardingStepsService::isUganda('UG'));
        $this->assertFalse(OnboardingStepsService::isUganda('Kenya'));
        $this->assertFalse(OnboardingStepsService::isUganda(''));
        $this->assertFalse(OnboardingStepsService::isUganda(null));
    }

    /** @test */
    public function is_uneb_curriculum_is_case_insensitive(): void
    {
        $this->assertTrue(OnboardingStepsService::isUnebCurriculum('uneb'));
        $this->assertTrue(OnboardingStepsService::isUnebCurriculum('UNEB'));
        $this->assertFalse(OnboardingStepsService::isUnebCurriculum('cambridge'));
        $this->assertFalse(OnboardingStepsService::isUnebCurriculum(null));
    }

    // ── EMIS (Uganda-only, required) ───────────────────────────────

    /** @test */
    public function emis_required_for_uganda_and_incomplete_until_ministry_code_set(): void
    {
        $school = $this->makeSchool(['registration_country' => 'Uganda', 'ministry_code' => null]);

        $this->assertFalse(OnboardingStepsService::isEmisComplete($school));
        $this->assertFalse(OnboardingStepsService::isStepComplete('emis', $school));

        $school->ministry_code = 'EMIS-12345';
        $school->save();

        $this->assertTrue(OnboardingStepsService::isEmisComplete($school->fresh()));
        $this->assertTrue(OnboardingStepsService::isStepComplete('emis', $school->fresh()));
    }

    /** @test */
    public function emis_auto_complete_and_not_applicable_for_non_uganda(): void
    {
        $school = $this->makeSchool(['registration_country' => 'Kenya', 'ministry_code' => null]);

        // Even without a ministry code, EMIS is considered complete for non-Uganda.
        $this->assertTrue(OnboardingStepsService::isEmisComplete($school));
        $this->assertTrue(OnboardingStepsService::isStepComplete('emis', $school));

        // And the step is filtered out of the applicable list entirely.
        $this->assertArrayNotHasKey('emis', OnboardingStepsService::applicableSteps($school));
    }

    /** @test */
    public function emis_step_is_applicable_for_uganda(): void
    {
        $school = $this->makeSchool(['registration_country' => 'Uganda']);

        $this->assertArrayHasKey('emis', OnboardingStepsService::applicableSteps($school));
    }

    // ── UNEB centre (UNEB-only, optional, never blocks) ────────────

    /** @test */
    public function uneb_center_optional_null_incomplete_but_empty_or_value_complete(): void
    {
        $school = $this->makeSchool(['curriculum' => 'uneb', 'uneb_center_number' => null]);

        // null = never asked -> not complete yet (so it surfaces once)
        $this->assertFalse(OnboardingStepsService::isUnebCenterComplete($school));

        // '' = explicitly skipped -> complete (never blocks)
        $school->uneb_center_number = '';
        $school->save();
        $this->assertTrue(OnboardingStepsService::isUnebCenterComplete($school->fresh()));

        // an actual value -> complete
        $school->uneb_center_number = 'U0001';
        $school->save();
        $this->assertTrue(OnboardingStepsService::isUnebCenterComplete($school->fresh()));
    }

    /** @test */
    public function uneb_center_not_applicable_and_auto_complete_for_non_uneb(): void
    {
        $school = $this->makeSchool(['curriculum' => 'cambridge', 'uneb_center_number' => null]);

        $this->assertTrue(OnboardingStepsService::isUnebCenterComplete($school));
        $this->assertTrue(OnboardingStepsService::isStepComplete('uneb_center', $school));
        $this->assertArrayNotHasKey('uneb_center', OnboardingStepsService::applicableSteps($school));
    }

    /** @test */
    public function uneb_center_step_is_applicable_for_uneb_curriculum(): void
    {
        $school = $this->makeSchool(['curriculum' => 'uneb']);

        $this->assertArrayHasKey('uneb_center', OnboardingStepsService::applicableSteps($school));
    }

    // ── persistCountry ─────────────────────────────────────────────

    /** @test */
    public function persist_country_sets_registration_country_and_country_id_when_matched(): void
    {
        $country = \App\Models\Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $school = $this->makeSchool(['registration_country' => null, 'country_id' => null]);

        OnboardingStepsService::persistCountry($school, 'Uganda');

        $fresh = $school->fresh();
        $this->assertSame('Uganda', $fresh->registration_country);
        $this->assertSame($country->id, (int) $fresh->country_id);
    }

    /** @test */
    public function persist_country_stores_name_even_without_matching_country_row(): void
    {
        $school = $this->makeSchool(['registration_country' => null]);

        OnboardingStepsService::persistCountry($school, 'Narnia');

        $this->assertSame('Narnia', $school->fresh()->registration_country);
    }

    // ── countActiveStudents ────────────────────────────────────────

    /** @test */
    public function count_active_students_excludes_exited_and_non_students(): void
    {
        $school = $this->makeSchool();

        // 3 active students, 1 exited student, 1 teacher.
        $this->makeUser($school->id, 6, 'active');
        $this->makeUser($school->id, 6, 'active');
        $this->makeUser($school->id, 6, 'active');
        $this->makeUser($school->id, 6, 'exit');
        $this->makeUser($school->id, 5, 'active'); // teacher, ignored

        // A student from a different school, must not be counted.
        $other = $this->makeSchool(['slug' => 'other-' . uniqid()]);
        $this->makeUser($other->id, 6, 'active');

        $this->assertSame(3, OnboardingStepsService::countActiveStudents($school->id));
    }

    private function makeUser(int $schoolId, int $group, ?string $status): User
    {
        $user = User::create([
            'school_id' => $schoolId,
            'usergroup_id' => $group,
            'name' => 'U' . uniqid(),
            'email' => uniqid() . '@test.sch.ug',
            'password' => bcrypt('password'),
            'email_verified' => 1,
        ]);

        // `status` is not mass-assignable — set it directly so `exit` persists.
        $user->status = $status;
        $user->save();

        return $user;
    }

    // ── suggestPlanForStudentCount + override ──────────────────────

    private function seedTierPlans(): void
    {
        Plan::create(['name' => 'freemium', 'display_name' => 'Freemium', 'amount' => 0, 'no_of_students' => 50, 'no_of_users' => 5, 'cycle' => 365, 'is_active' => 1, 'order' => 1]);
        Plan::create(['name' => 'growth', 'display_name' => 'Growth', 'amount' => 50000, 'no_of_students' => 500, 'no_of_users' => 50, 'cycle' => 365, 'is_active' => 1, 'order' => 2]);
        Plan::create(['name' => 'premium', 'display_name' => 'Premium', 'amount' => 150000, 'no_of_students' => 0, 'no_of_users' => 0, 'cycle' => 365, 'is_active' => 1, 'order' => 3]);
    }

    /** @test */
    public function suggest_plan_picks_smallest_tier_that_fits(): void
    {
        $this->seedTierPlans();

        $this->assertSame('freemium', OnboardingStepsService::suggestPlanForStudentCount(10)?->name);
        $this->assertSame('freemium', OnboardingStepsService::suggestPlanForStudentCount(50)?->name);
        $this->assertSame('growth', OnboardingStepsService::suggestPlanForStudentCount(51)?->name);
        $this->assertSame('growth', OnboardingStepsService::suggestPlanForStudentCount(500)?->name);
        // Beyond growth -> premium (0 == unlimited).
        $this->assertSame('premium', OnboardingStepsService::suggestPlanForStudentCount(5000)?->name);
    }

    /** @test */
    public function suggest_plan_returns_null_when_no_plans_exist(): void
    {
        $this->assertNull(OnboardingStepsService::suggestPlanForStudentCount(100));
    }

    // ── plan_selection completes only when CurrentPlan exists (end) ─

    /** @test */
    public function plan_selection_step_incomplete_until_current_plan_created_at_end(): void
    {
        $this->seedTierPlans();
        $school = $this->makeSchool();

        // No CurrentPlan yet -> plan_selection is not complete (deferred to end).
        $this->assertFalse(OnboardingStepsService::isStepComplete('plan_selection', $school));

        // Admin later picks ANY tier (override allowed) -> CurrentPlan created.
        $freemium = Plan::where('name', 'freemium')->first();
        CurrentPlan::create(['school_id' => $school->id, 'plan_id' => $freemium->id, 'status' => 'running']);

        $this->assertTrue(OnboardingStepsService::isStepComplete('plan_selection', $school->fresh()));
    }
}
