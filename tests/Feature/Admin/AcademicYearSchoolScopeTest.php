<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Academic years are scoped to the caller's school on update and on making a
 * year current.
 */
class AcademicYearSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private AcademicYear $yearA;
    private AcademicYear $yearA2;
    private AcademicYear $yearB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = $this->createSchool('AY School A');
        $this->schoolB = $this->createSchool('AY School B');

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
            'email' => 'ay-admin-a@test.sch.ug',
        ]);

        $this->yearA = $this->createYear($this->schoolA, '2026 A', 1, 'Base A');
        $this->yearA2 = $this->createYear($this->schoolA, '2027 A', 0, 'Next A');
        $this->yearB = $this->createYear($this->schoolB, '2026 B', 1, 'Base B');
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

    private function createYear(School $school, string $name, int $status, string $description): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => $name,
            'description' => $description,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => $status,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function payload(string $description): array
    {
        return [
            'description' => $description,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'old',
        ];
    }

    public function test_update_of_another_schools_year_gets_a_404_and_changes_nothing(): void
    {
        $this->actingAs($this->adminA)
            ->post('/admin/academic/edit/' . $this->yearB->id, $this->payload('Changed by another school'))
            ->assertNotFound();

        // The settings variant shares the same handler.
        $this->actingAs($this->adminA)
            ->post('/admin/academic/update/' . $this->yearB->id, $this->payload('Changed by another school'))
            ->assertNotFound();

        $this->assertDatabaseHas('academic_years', [
            'id' => $this->yearB->id,
            'description' => 'Base B',
        ]);
    }

    public function test_update_in_own_school_still_succeeds(): void
    {
        $this->actingAs($this->adminA)
            ->post('/admin/academic/edit/' . $this->yearA2->id, $this->payload('Updated AY'))
            ->assertOk();

        $this->assertDatabaseHas('academic_years', [
            'id' => $this->yearA2->id,
            'description' => 'Updated AY',
        ]);
    }

    public function test_update_status_of_another_schools_year_gets_a_404_and_changes_nothing(): void
    {
        $this->actingAs($this->adminA)
            ->post('/admin/academic/updateStatus', ['academic_year_id' => $this->yearB->id])
            ->assertNotFound();

        $this->assertDatabaseHas('academic_years', ['id' => $this->yearB->id, 'status' => 1]);
        $this->assertDatabaseHas('academic_years', ['id' => $this->yearA->id, 'status' => 1]);
    }

    public function test_update_status_in_own_school_still_succeeds(): void
    {
        $this->actingAs($this->adminA)
            ->post('/admin/academic/updateStatus', ['academic_year_id' => $this->yearA2->id])
            ->assertOk();

        $this->assertDatabaseHas('academic_years', ['id' => $this->yearA2->id, 'status' => 1]);
        $this->assertDatabaseHas('academic_years', ['id' => $this->yearA->id, 'status' => 0]);
    }
}
