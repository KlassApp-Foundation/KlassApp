<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\School;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentPromotionRules;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Student promotion rules are scoped to the caller's school, and the update
 * request keeps the section -> standard mapping inside that school.
 */
class PromotionRulesSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private StudentPromotionRules $ruleA;
    private StudentPromotionRules $ruleB;
    private Standard $stdA;
    private Section $secA;
    private Section $secB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = $this->createSchool('Promo School A');
        $this->schoolB = $this->createSchool('Promo School B');

        [$this->stdA, $this->secA] = $this->createStructure($this->schoolA);
        [$stdB, $this->secB] = $this->createStructure($this->schoolB);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
            'email' => 'promo-admin-a@test.sch.ug',
        ]);

        $this->ruleA = StudentPromotionRules::create([
            'school_id' => $this->schoolA->id, 'standard_id' => $this->stdA->id, 'section_id' => $this->secA->id,
            'rule_type' => 'average', 'min_average' => 50,
        ]);
        $this->ruleB = StudentPromotionRules::create([
            'school_id' => $this->schoolB->id, 'standard_id' => $stdB->id, 'section_id' => $this->secB->id,
            'rule_type' => 'average', 'min_average' => 50,
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
        $year = AcademicYear::create([
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

        StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        return [$standard, $section];
    }

    public function test_update_of_another_schools_promotion_rule_gets_a_404(): void
    {
        $this->actingAs($this->adminA)
            ->put(route('students.promotion.update', $this->ruleB->id), [
                'section_id' => $this->secB->id,
                'rule_type' => 'average',
                'min_average' => 60,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('student_promotion_rules', [
            'id' => $this->ruleB->id,
            'min_average' => 50,
        ]);
    }

    public function test_update_with_another_schools_section_is_refused_and_changes_nothing(): void
    {
        $response = $this->actingAs($this->adminA)
            ->put(route('students.promotion.update', $this->ruleA->id), [
                'section_id' => $this->secB->id,
                'rule_type' => 'average',
                'min_average' => 60,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('section_id');

        $this->assertDatabaseHas('student_promotion_rules', [
            'id' => $this->ruleA->id,
            'standard_id' => $this->stdA->id,
            'min_average' => 50,
        ]);
    }

    public function test_update_in_own_school_still_succeeds(): void
    {
        $this->actingAs($this->adminA)
            ->put(route('students.promotion.update', $this->ruleA->id), [
                'section_id' => $this->secA->id,
                'rule_type' => 'average',
                'min_average' => 55,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_promotion_rules', [
            'id' => $this->ruleA->id,
            'standard_id' => $this->stdA->id,
            'min_average' => 55,
        ]);
    }

    public function test_delete_of_own_schools_promotion_rule_succeeds(): void
    {
        $this->actingAs($this->adminA)
            ->delete(route('students.promotion.remove', $this->ruleA->id))
            ->assertOk();

        $this->assertDatabaseMissing('student_promotion_rules', ['id' => $this->ruleA->id]);
    }

    public function test_delete_of_another_schools_promotion_rule_gets_a_404_and_leaves_it_in_place(): void
    {
        $this->actingAs($this->adminA)
            ->delete(route('students.promotion.remove', $this->ruleB->id))
            ->assertNotFound();

        $this->assertDatabaseHas('student_promotion_rules', ['id' => $this->ruleB->id]);
    }
}
