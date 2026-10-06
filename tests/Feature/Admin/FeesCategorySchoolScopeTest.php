<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fee categories are scoped to the caller's school.
 *
 * Another school's category 404s on edit/update (and is never changed);
 * the same school still succeeds; a category can never be moved to another
 * school. Test names say only what is checked.
 */
class FeesCategorySchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private FeesCategories $feeA;
    private FeesCategories $feeB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = $this->createSchool('Fee School A');
        $this->schoolB = $this->createSchool('Fee School B');

        $stdA = $this->createStructure($this->schoolA);
        $stdB = $this->createStructure($this->schoolB);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
            'email' => 'fee-admin-a@test.sch.ug',
        ]);

        $this->feeA = FeesCategories::create([
            'school_id' => $this->schoolA->id, 'standard_id' => $stdA->id,
            'name' => 'Tuition A', 'amount' => 100,
        ]);
        $this->feeB = FeesCategories::create([
            'school_id' => $this->schoolB->id, 'standard_id' => $stdB->id,
            'name' => 'Tuition B', 'amount' => 200,
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

    public function test_edit_of_another_schools_fee_category_gets_a_404(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('admin.fees-categories.edit', $this->feeB->id))
            ->assertNotFound();

        $this->actingAs($this->adminA)
            ->get(route('admin.fees-categories.edit', $this->feeA->id))
            ->assertOk();
    }

    public function test_update_of_another_schools_fee_category_gets_a_404_and_changes_nothing(): void
    {
        $this->actingAs($this->adminA)
            ->patch(route('admin.fees-categories.update', $this->feeB->id), [
                'name' => 'Renamed By Another School',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('fees_categories', [
            'id' => $this->feeB->id,
            'name' => 'Tuition B',
        ]);
    }

    public function test_update_in_own_school_still_succeeds(): void
    {
        $this->actingAs($this->adminA)
            ->patch(route('admin.fees-categories.update', $this->feeA->id), [
                'name' => 'Tuition A Updated',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fees_categories', [
            'id' => $this->feeA->id,
            'name' => 'Tuition A Updated',
        ]);
    }

    public function test_update_cannot_move_a_fee_category_to_another_school(): void
    {
        $this->actingAs($this->adminA)
            ->patch(route('admin.fees-categories.update', $this->feeA->id), [
                'name' => 'Tuition A Renamed',
                'school_id' => $this->schoolB->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fees_categories', [
            'id' => $this->feeA->id,
            'school_id' => $this->schoolA->id,
        ]);
    }

    public function test_destroy_of_another_schools_fee_category_leaves_it_in_place(): void
    {
        $this->actingAs($this->adminA)
            ->delete(route('admin.fees-categories.destroy', $this->feeB->id))
            ->assertRedirect();

        $this->assertDatabaseHas('fees_categories', ['id' => $this->feeB->id]);

        $this->actingAs($this->adminA)
            ->delete(route('admin.fees-categories.destroy', $this->feeA->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('fees_categories', ['id' => $this->feeA->id]);
    }
}
