<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StudentPromotionRules;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * School-scoped implicit route bindings (SchoolScopedRouteBinding trait).
 *
 * A record of another school must 404 for a school user; a same-school record
 * still works; site admins and non-web (unauthenticated) resolution keep
 * working. Test names say only what is checked.
 */
class SchoolScopedRouteBindingTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private User $siteAdmin;
    private SchoolGradingSystem $gradeA;
    private SchoolGradingSystem $gradeB;
    private StudentPromotionRules $ruleA;
    private StudentPromotionRules $ruleB;
    private Subscription $subA;
    private Subscription $subB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = $this->createSchool('School A');
        $this->schoolB = $this->createSchool('School B');

        [$stdA, $secA] = $this->createStructure($this->schoolA);
        [$stdB, $secB] = $this->createStructure($this->schoolB);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
            'email' => 'admin-a@test.sch.ug',
        ]);

        $adminB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'usergroup_id' => 3,
            'email' => 'admin-b@test.sch.ug',
        ]);

        $this->siteAdmin = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 1,
            'email' => 'site-admin@test.sch.ug',
        ]);

        $this->gradeA = SchoolGradingSystem::create([
            'school_id' => $this->schoolA->id, 'standard_id' => $stdA->id,
            'grade' => 'A', 'points' => 4, 'min_score' => 80, 'max_score' => 100, 'remark' => 'Excellent',
        ]);
        $this->gradeB = SchoolGradingSystem::create([
            'school_id' => $this->schoolB->id, 'standard_id' => $stdB->id,
            'grade' => 'A', 'points' => 4, 'min_score' => 80, 'max_score' => 100, 'remark' => 'Excellent',
        ]);

        $this->ruleA = StudentPromotionRules::create([
            'school_id' => $this->schoolA->id, 'standard_id' => $stdA->id, 'section_id' => $secA->id,
            'rule_type' => 'average', 'min_average' => 50,
        ]);
        $this->ruleB = StudentPromotionRules::create([
            'school_id' => $this->schoolB->id, 'standard_id' => $stdB->id, 'section_id' => $secB->id,
            'rule_type' => 'average', 'min_average' => 50,
        ]);

        $plan = $this->createPlan();

        $this->subA = Subscription::create([
            'school_id' => $this->schoolA->id, 'user_id' => $this->adminA->id,
            'plan_id' => $plan->id, 'status' => 'pending', 'amount_paid' => 0,
        ]);
        $this->subB = Subscription::create([
            'school_id' => $this->schoolB->id, 'user_id' => $adminB->id,
            'plan_id' => $plan->id, 'status' => 'pending', 'amount_paid' => 0,
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

    /**
     * @return array{0: Standard, 1: Section}
     */
    private function createStructure(School $school): array
    {
        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026 Test',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.1 ' . $school->id,
            'status' => 1,
        ]);

        return [$standard, $section];
    }

    private function createPlan(): Plan
    {
        return Plan::create([
            'name' => 'test-plan-' . random_int(1000, 9999),
            'display_name' => 'Test Plan',
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

    public function test_grading_system_edit_is_scoped_to_the_school(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('admin.grades.edit', $this->gradeB->id))
            ->assertNotFound();

        $this->actingAs($this->adminA)
            ->get(route('admin.grades.edit', $this->gradeA->id))
            ->assertOk();
    }

    public function test_grading_system_update_from_another_school_gets_a_404_and_changes_nothing(): void
    {
        $this->actingAs($this->adminA)
            ->put(route('admin.grades.update', $this->gradeB->id), [
                'grade' => 'B', 'min_score' => 70, 'max_score' => 79,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('school_grading_systems', [
            'id' => $this->gradeB->id, 'grade' => 'A', 'min_score' => 80,
        ]);
    }

    public function test_grading_system_update_in_the_own_school_still_succeeds(): void
    {
        $this->actingAs($this->adminA)
            ->put(route('admin.grades.update', $this->gradeA->id), [
                'standard_id' => $this->gradeA->standard_id,
                'grade' => 'B', 'points' => 3, 'min_score' => 70, 'max_score' => 79, 'remark' => 'Good',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('school_grading_systems', [
            'id' => $this->gradeA->id, 'grade' => 'B', 'min_score' => 70,
        ]);
    }

    public function test_promotion_rule_edit_is_scoped_to_the_school(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('students.promotion.edit', $this->ruleB->id))
            ->assertNotFound();

        $this->actingAs($this->adminA)
            ->get(route('students.promotion.edit', $this->ruleA->id))
            ->assertOk();
    }

    public function test_subscription_detail_page_is_scoped_to_the_school(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('admin.subscriptions.edit', $this->subB->id))
            ->assertNotFound();

        $this->actingAs($this->adminA)
            ->get(route('admin.subscriptions.edit', $this->subA->id))
            ->assertOk();
    }

    public function test_site_admin_still_resolves_bindings_from_any_school(): void
    {
        // Subscriptions pass the privilege gate naturally for site admins.
        // (The detail URI resolves through the edit route — same method+URI in routes/admin.php.)
        $this->actingAs($this->siteAdmin)
            ->get(route('admin.subscriptions.edit', $this->subB->id))
            ->assertOk();

        // The privilege gate is school-scoped by design; neutralise it here —
        // this test is about the binding scope, not that middleware.
        $this->actingAs($this->siteAdmin)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->get(route('admin.grades.edit', $this->gradeB->id))
            ->assertOk();

        $this->actingAs($this->siteAdmin)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->get(route('students.promotion.edit', $this->ruleB->id))
            ->assertOk();
    }

    public function test_unauthenticated_resolution_is_not_scoped(): void
    {
        // Console commands and queue workers resolve without an acting user.
        $this->assertNotNull((new SchoolGradingSystem)->resolveRouteBinding($this->gradeB->id));
        $this->assertNotNull((new StudentPromotionRules)->resolveRouteBinding($this->ruleB->id));
        $this->assertNotNull((new Subscription)->resolveRouteBinding($this->subB->id));
    }

    public function test_subscriptions_without_a_school_are_not_resolvable_by_a_school_admin(): void
    {
        $globalSub = Subscription::create([
            'school_id' => null, 'user_id' => $this->adminA->id,
            'status' => 'pending', 'amount_paid' => 0,
        ]);

        $this->actingAs($this->adminA)
            ->get(route('admin.subscriptions.edit', $globalSub->id))
            ->assertNotFound();

        $this->actingAs($this->siteAdmin)
            ->get(route('admin.subscriptions.edit', $globalSub->id))
            ->assertOk();
    }
}
