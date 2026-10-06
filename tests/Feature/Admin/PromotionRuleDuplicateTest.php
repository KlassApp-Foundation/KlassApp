<?php

namespace Tests\Feature\Admin;

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
 * A promotion rule for a class + type that already exists is a form error,
 * not a server error (the table has a unique section_id+rule_type).
 */
class PromotionRuleDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;
    private Standard $standard;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Promo Dup School',
            'slug' => 'promo-dup-' . random_int(1000, 9999),
            'email' => 'promo-dup@test.sch.ug',
            'phone' => '+256700' . random_int(100000, 999999),
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
        ]);

        [$this->standard, $this->section] = $this->createStructure($this->school);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'email' => 'promo-dup-admin@test.sch.ug',
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

    public function test_creating_a_duplicate_rule_shows_a_form_error_instead_of_a_server_error(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/students/promotions/store', $this->payload('average'))
            ->assertRedirect();

        $this->assertDatabaseCount('student_promotion_rules', 1);

        $response = $this->actingAs($this->admin)
            ->post('/admin/students/promotions/store', $this->payload('average'));

        $response->assertRedirect();
        $response->assertSessionHasErrors('section_id');
        $this->assertDatabaseCount('student_promotion_rules', 1);
    }

    public function test_creating_a_rule_for_another_rule_type_still_works(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/students/promotions/store', $this->payload('average'))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post('/admin/students/promotions/store', $this->payload('aggregate'))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('student_promotion_rules', 2);
    }

    public function test_updating_a_rule_to_a_duplicate_combination_shows_a_form_error(): void
    {
        $this->actingAs($this->admin)->post('/admin/students/promotions/store', $this->payload('average'))->assertRedirect();
        $this->actingAs($this->admin)->post('/admin/students/promotions/store', $this->payload('points'))->assertRedirect();

        $points = StudentPromotionRules::where('rule_type', 'points')->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->put('/admin/students/promotions/update/' . $points->id, $this->updatePayload('average'));

        $response->assertRedirect();
        $response->assertSessionHasErrors('section_id');
        $this->assertSame('points', $points->fresh()->rule_type);
    }

    public function test_updating_a_rule_with_its_own_combination_still_works(): void
    {
        $this->actingAs($this->admin)->post('/admin/students/promotions/store', $this->payload('average'))->assertRedirect();

        $rule = StudentPromotionRules::firstOrFail();

        $this->actingAs($this->admin)
            ->put('/admin/students/promotions/update/' . $rule->id, $this->updatePayload('average'))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('student_promotion_rules', 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $type): array
    {
        return [
            'section_id' => $this->section->id,
            'rule_type' => $type,
            'min_average' => 50,
            'min_aggregate' => 4,
            'min_points' => 4,
        ];
    }

    /**
     * The update request validates min_aggregate as a string.
     *
     * @return array<string, mixed>
     */
    private function updatePayload(string $type): array
    {
        return [
            'section_id' => $this->section->id,
            'rule_type' => $type,
            'min_average' => 50,
            'min_aggregate' => '4',
            'min_points' => 4,
        ];
    }
}
