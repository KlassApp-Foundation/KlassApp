<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admin student-profile surfaces must resolve users.name within the actor's
 * school — same collision class as PR #517 (Grace Auma).
 */
class StudentDetailsNameScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $localStudent;

    private User $foreignTwin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $otherSchool = School::create([
            'name' => 'Collision Admin School',
            'slug' => 'collision-admin-'.uniqid(),
            'email' => 'collision-admin-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $otherYear = AcademicYear::create([
            'school_id' => $otherSchool->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        // Create foreign twin first so it has the lower id — unscoped first()
        // would resolve this row and leak the wrong medical history.
        $this->foreignTwin = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $otherSchool->id,
            'name' => 'Grace Auma',
            'email' => 'grace.collision@t.sch.ug',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'user_id' => $this->foreignTwin->id,
            'medication_problems' => 'ForeignOnlyAllergy',
            'academic_status' => 'pass',
        ]);

        $this->school = School::create([
            'name' => 'Admin Name Scope School',
            'slug' => 'admin-name-scope-'.uniqid(),
            'email' => 'admin-name-scope-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'name' => 'admin-name-scope',
            'email' => 'admin.name.scope@t.sch.ug',
            'status' => 'active',
        ]);

        $this->localStudent = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Grace Auma',
            'email' => 'grace.local@t.sch.ug',
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $this->localStudent->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => 'Grace',
            'lastname' => 'Auma',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->localStudent->id,
            'medication_problems' => 'LocalAsthma',
            'academic_status' => 'pass',
        ]);
    }

    public function test_find_by_exact_name_in_school_ignores_other_schools(): void
    {
        $this->assertLessThan($this->localStudent->id, $this->foreignTwin->id);

        $resolved = User::findByExactNameInSchool('Grace Auma', (int) $this->school->id, 6);

        $this->assertNotNull($resolved);
        $this->assertSame($this->localStudent->id, $resolved->id);
    }

    public function test_admin_medical_history_survives_cross_school_duplicate_name(): void
    {
        $this->assertLessThan($this->localStudent->id, $this->foreignTwin->id);
        $this->assertGreaterThan(1, User::where('name', 'Grace Auma')->count());

        // Unscoped first() would return the foreign twin and wrong medical text.
        $this->assertSame(
            'ForeignOnlyAllergy',
            User::where('name', 'Grace Auma')->first()->studentAcademicLatest->medication_problems
        );

        $this->actingAs($this->admin)
            ->get('/admin/student/show/medicalHistory/Grace Auma')
            ->assertOk()
            ->assertJsonFragment(['medication_problems' => 'LocalAsthma'])
            ->assertJsonMissing(['medication_problems' => 'ForeignOnlyAllergy']);
    }
}
