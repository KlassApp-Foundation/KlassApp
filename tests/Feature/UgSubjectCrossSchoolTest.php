<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UgSubjectCrossSchoolTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private Subject $subjectB;
    private Subject $subjectA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create([
            'name' => 'School A', 'slug' => 'school-a',
            'email' => 'a@us.test', 'phone' => '+256700000001',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $this->schoolB = School::create([
            'name' => 'School B', 'slug' => 'school-b',
            'email' => 'b@us.test', 'phone' => '+256700000002',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        foreach ([$this->schoolA, $this->schoolB] as $school) {
            AcademicYear::create([
                'school_id' => $school->id, 'name' => '2026 Test',
                'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
            ]);
            Standard::create([
                'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
            ]);
            Section::create([
                'school_id' => $school->id, 'name' => 'P.1', 'status' => 1,
            ]);
        }

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 3,
            'email' => 'admin.a@us.test',
        ]);

        $yearB = AcademicYear::where('school_id', $this->schoolB->id)->first();
        $standardB = Standard::where('school_id', $this->schoolB->id)->first();
        $sectionB = Section::where('school_id', $this->schoolB->id)->first();

        $yearA = AcademicYear::where('school_id', $this->schoolA->id)->first();
        $standardA = Standard::where('school_id', $this->schoolA->id)->first();
        $sectionA = Section::where('school_id', $this->schoolA->id)->first();

        $this->subjectA = Subject::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $yearA->id,
            'standard_id' => $standardA->id,
            'section_id' => $sectionA->id,
            'name' => 'Own Math',
            'type' => 'core',
        ]);

        $this->subjectB = Subject::create([
            'school_id' => $this->schoolB->id,
            'academic_year_id' => $yearB->id,
            'standard_id' => $standardB->id,
            'section_id' => $sectionB->id,
            'name' => 'Cross-Tenant Math',
            'type' => 'core',
        ]);
    }

    /** @test */
    public function admin_can_update_own_subject()
    {
        $response = $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->patch("/admin/subjects/{$this->subjectA->id}/update", [
                'name' => 'Updated Own Math',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'id' => $this->subjectA->id,
            'name' => 'Updated Own Math',
        ]);
    }

    /** @test */
    public function admin_cannot_update_another_schools_subject()
    {
        $response = $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->patch("/admin/subjects/{$this->subjectB->id}/update", [
                'name' => 'Hacked Subject',
            ]);

        // The scoped query matches 0 rows, returns success-like redirect.
        // Validation prepareForValidation may set school_id from auth user,
        // but the controller's where('school_id', $school_id) guards it regardless.
        $response->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'id' => $this->subjectB->id,
            'name' => 'Cross-Tenant Math',
        ]);
    }

    /** @test */
    public function admin_cannot_force_delete_another_schools_subject()
    {
        // First soft-delete within own school so it's trashed
        $this->subjectB->delete();

        $response = $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->delete("/admin/subjects/{$this->subjectB->id}/force-delete");

        $response->assertNotFound();

        $this->assertSoftDeleted('subjects', ['id' => $this->subjectB->id]);
    }

    /** @test */
    public function admin_cannot_restore_another_schools_subject()
    {
        // Soft-delete within own school so it's trashed
        $this->subjectB->delete();

        $response = $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->post("/admin/subjects/{$this->subjectB->id}/restore");

        $response->assertNotFound();

        // Still soft-deleted — restore was blocked
        $this->assertSoftDeleted('subjects', ['id' => $this->subjectB->id]);
    }
}
