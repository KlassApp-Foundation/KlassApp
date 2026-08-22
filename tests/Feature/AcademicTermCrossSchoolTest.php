<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademicTermCrossSchoolTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private AcademicTerm $termB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create([
            'name' => 'School A', 'slug' => 'term-a',
            'email' => 'a@term.test', 'phone' => '+256700000001',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $this->schoolB = School::create([
            'name' => 'School B', 'slug' => 'term-b',
            'email' => 'b@term.test', 'phone' => '+256700000002',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        foreach ([$this->schoolA, $this->schoolB] as $school) {
            $year = AcademicYear::create([
                'school_id' => $school->id, 'name' => '2026 Test',
                'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
            ]);
            Standard::create([
                'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
            ]);
        }

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 3,
            'email' => 'admin.a@term.test',
        ]);

        $yearB = AcademicYear::where('school_id', $this->schoolB->id)->first();

        $this->termB = AcademicTerm::create([
            'school_id' => $this->schoolB->id,
            'academic_year_id' => $yearB->id,
            'name' => 'Term 1 B',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);
    }

    /** @test */
    public function admin_cannot_update_another_schools_academic_term()
    {
        $response = $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->patch("/admin/academic-term/{$this->termB->id}/update", [
                'name' => 'Hacked Term',
            ]);

        // The controller scoping makes the update a no-op (0 rows),
        // but still redirects — the DB row is unchanged.
        $response->assertRedirect();

        $this->assertDatabaseHas('academic_terms', [
            'id' => $this->termB->id,
            'name' => 'Term 1 B',
        ]);
    }
}