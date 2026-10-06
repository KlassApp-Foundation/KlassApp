<?php

namespace Tests\Feature\Grading;

use App\Models\AcademicYear;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Grading-system records are scoped to the caller's school, and the update
 * request never moves or extends a record across schools.
 */
class GradingSystemSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private SchoolGradingSystem $gradeA;
    private SchoolGradingSystem $gradeB;
    private Standard $stdA;
    private Standard $stdB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = $this->createSchool('Grade School A');
        $this->schoolB = $this->createSchool('Grade School B');

        $this->stdA = $this->createStructure($this->schoolA);
        $this->stdB = $this->createStructure($this->schoolB);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
            'email' => 'grade-admin-a@test.sch.ug',
        ]);

        $this->gradeA = SchoolGradingSystem::create([
            'school_id' => $this->schoolA->id, 'standard_id' => $this->stdA->id,
            'grade' => 'A', 'points' => 4, 'min_score' => 80, 'max_score' => 100, 'remark' => 'Excellent',
        ]);
        $this->gradeB = SchoolGradingSystem::create([
            'school_id' => $this->schoolB->id, 'standard_id' => $this->stdB->id,
            'grade' => 'A', 'points' => 4, 'min_score' => 80, 'max_score' => 100, 'remark' => 'Excellent',
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

    private function createStructure(School $school): Standard
    {
        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026 Test',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        return Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
    }

    public function test_destroy_of_another_schools_grade_gets_a_404_and_keeps_the_row(): void
    {
        $this->actingAs($this->adminA)
            ->delete(route('admin.grades.remove', $this->gradeB->id))
            ->assertNotFound();

        $this->assertDatabaseHas('school_grading_systems', ['id' => $this->gradeB->id]);

        $this->actingAs($this->adminA)
            ->delete(route('admin.grades.remove', $this->gradeA->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('school_grading_systems', ['id' => $this->gradeA->id]);
    }

    public function test_update_ignores_a_school_id_in_the_payload(): void
    {
        $this->actingAs($this->adminA)
            ->put(route('admin.grades.update', $this->gradeA->id), [
                'standard_id' => $this->gradeA->standard_id,
                'grade' => 'B', 'points' => 3, 'min_score' => 70, 'max_score' => 79,
                'school_id' => $this->schoolB->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('school_grading_systems', [
            'id' => $this->gradeA->id,
            'school_id' => $this->schoolA->id,
            'grade' => 'B',
        ]);
    }

    public function test_update_with_another_schools_standard_is_refused_and_changes_nothing(): void
    {
        $response = $this->actingAs($this->adminA)
            ->put(route('admin.grades.update', $this->gradeA->id), [
                'standard_id' => $this->stdB->id,
                'grade' => 'B', 'points' => 3, 'min_score' => 70, 'max_score' => 79,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('standard_id');

        $this->assertDatabaseHas('school_grading_systems', [
            'id' => $this->gradeA->id,
            'standard_id' => $this->stdA->id,
            'grade' => 'A',
        ]);
    }
}
