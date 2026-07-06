<?php

namespace Tests\Feature;

use App\Models\StudentHealthIncident;
use App\Models\StudentHealthProfile;
use App\Models\StudentImmunization;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentHealthModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $schoolAdmin;
    private User $studentA;
    private User $studentB;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin'],
            ['id' => 3, 'name' => 'schooladmin'],
            ['id' => 5, 'name' => 'teacher'],
            ['id' => 6, 'name' => 'student'],
        ]);

        // School A
        $schoolA = \App\Models\School::create([
            'name' => 'Health Test School A', 'email' => 'healthA@test.sch.ug',
            'phone' => '+256700000001', 'slug' => 'health-a', 'status' => '1',
        ]);

        $this->schoolAdmin = User::factory()->create([
            'usergroup_id' => 3, 'school_id' => $schoolA->id,
            'email' => 'admin@healthA.sch.ug',
        ]);
        Userprofile::create(['user_id' => $this->schoolAdmin->id, 'school_id' => $schoolA->id, 'usergroup_id' => 3, 'firstname' => 'Health', 'lastname' => 'Admin', 'status' => 'active']);

        $this->studentA = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $schoolA->id,
            'email' => 'studentA@healthA.sch.ug',
        ]);

        // School B (for cross-school isolation)
        $schoolB = \App\Models\School::create([
            'name' => 'Health Test School B', 'email' => 'healthB@test.sch.ug',
            'phone' => '+256700000002', 'slug' => 'health-b', 'status' => '1',
        ]);

        $schoolBAdmin = User::factory()->create([
            'usergroup_id' => 3, 'school_id' => $schoolB->id,
            'email' => 'admin@healthB.sch.ug',
        ]);

        $this->studentB = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $schoolB->id,
            'email' => 'studentB@healthB.sch.ug',
        ]);

        $this->teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $schoolA->id,
            'email' => 'teacher@healthA.sch.ug',
        ]);
        Userprofile::create(['user_id' => $this->teacher->id, 'school_id' => $schoolA->id, 'usergroup_id' => 5, 'firstname' => 'Health', 'lastname' => 'Teacher', 'status' => 'active']);
    }

    /** @test */
    public function admin_can_create_health_profile_for_student(): void
    {
        $this->actingAs($this->schoolAdmin);

        $response = $this->post("/admin/student/health/profile/{$this->studentA->id}", [
            'allergies' => 'Peanuts, Dust',
            'chronic_conditions' => 'Asthma',
            'emergency_contact_name' => 'Mother',
            'emergency_contact_phone' => '+256701234567',
            'blood_type' => 'O+',
            'medical_notes' => 'Requires inhaler',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_health_profiles', [
            'school_id' => $this->schoolAdmin->school_id,
            'user_id' => $this->studentA->id,
            'allergies' => 'Peanuts, Dust',
            'blood_type' => 'O+',
        ]);
    }

    /** @test */
    public function admin_can_update_existing_health_profile(): void
    {
        $this->actingAs($this->schoolAdmin);

        StudentHealthProfile::create([
            'school_id' => $this->schoolAdmin->school_id,
            'user_id' => $this->studentA->id,
            'allergies' => 'Old',
        ]);

        $this->post("/admin/student/health/profile/{$this->studentA->id}", [
            'allergies' => 'Updated',
            'medical_notes' => 'New notes',
        ]);

        $this->assertDatabaseHas('student_health_profiles', [
            'school_id' => $this->schoolAdmin->school_id,
            'user_id' => $this->studentA->id,
            'allergies' => 'Updated',
        ]);
    }

    /** @test */
    public function admin_can_add_immunization_record(): void
    {
        $this->actingAs($this->schoolAdmin);

        $response = $this->post("/admin/student/health/immunizations/{$this->studentA->id}", [
            'vaccine_name' => 'BCG',
            'administered_date' => '2026-01-15',
            'administered_by' => 'Dr. Smith',
            'next_due_date' => '2027-01-15',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_immunizations', [
            'school_id' => $this->schoolAdmin->school_id,
            'user_id' => $this->studentA->id,
            'vaccine_name' => 'BCG',
        ]);
    }

    /** @test */
    public function admin_can_delete_immunization_record(): void
    {
        $this->actingAs($this->schoolAdmin);

        $immunization = StudentImmunization::create([
            'school_id' => $this->schoolAdmin->school_id,
            'user_id' => $this->studentA->id,
            'vaccine_name' => 'Polio',
            'administered_date' => '2026-01-15',
        ]);

        $this->delete("/admin/student/health/immunizations/{$immunization->id}");

        $this->assertModelMissing($immunization);
    }

    /** @test */
    public function admin_can_record_health_incident(): void
    {
        $this->actingAs($this->schoolAdmin);

        $response = $this->post("/admin/student/health/incidents/{$this->studentA->id}", [
            'incident_date' => '2026-07-05',
            'description' => 'Fell during recess, scraped knee',
            'action_taken' => 'Cleaned and bandaged wound',
            'severity' => 'minor',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_health_incidents', [
            'school_id' => $this->schoolAdmin->school_id,
            'user_id' => $this->studentA->id,
            'description' => 'Fell during recess, scraped knee',
            'recorded_by' => $this->schoolAdmin->id,
            'severity' => 'minor',
        ]);
    }

    /** @test */
    public function admin_can_delete_health_incident(): void
    {
        $this->actingAs($this->schoolAdmin);

        $incident = StudentHealthIncident::create([
            'school_id' => $this->schoolAdmin->school_id,
            'user_id' => $this->studentA->id,
            'incident_date' => '2026-07-05',
            'description' => 'Test incident',
            'recorded_by' => $this->schoolAdmin->id,
        ]);

        $this->delete("/admin/student/health/incidents/{$incident->id}");

        $this->assertModelMissing($incident);
    }

    /** @test */
    public function cross_school_isolation_prevents_access_to_other_school_health_data(): void
    {
        // School A admin creates health data for School A student
        StudentHealthProfile::create([
            'school_id' => $this->schoolAdmin->school_id,
            'user_id' => $this->studentA->id,
            'allergies' => 'School A only',
        ]);

        // School B admin cannot create profile for School A student
        $schoolBAdmin = User::where('email', 'admin@healthB.sch.ug')->first();
        $this->actingAs($schoolBAdmin);

        // Attempting to access School A student's health should fail
        $response = $this->get("/admin/student/health/{$this->studentA->id}");

        // The controller uses findOrFail scoped by school_id — School B admin
        // can't see School A student, so this should 404
        $response->assertStatus(404);

        // School B admin cannot create profile for School A student
        $response = $this->post("/admin/student/health/profile/{$this->studentA->id}", [
            'allergies' => 'Should not save',
        ]);
        $response->assertStatus(404);
    }

    /** @test */
    public function health_records_do_not_affect_plan_limits(): void
    {
        // Health records are attached to existing students and don't create
        // new billable entities. This test verifies no User::create is triggered.
        $this->actingAs($this->schoolAdmin);

        $userCount = User::count();

        $this->post("/admin/student/health/profile/{$this->studentA->id}", [
            'allergies' => 'Test',
        ]);
        $this->post("/admin/student/health/immunizations/{$this->studentA->id}", [
            'vaccine_name' => 'Test', 'administered_date' => '2026-01-01',
        ]);
        $this->post("/admin/student/health/incidents/{$this->studentA->id}", [
            'incident_date' => '2026-01-01', 'description' => 'Test',
        ]);

        $this->assertEquals($userCount, User::count(),
            'Health module must not create new user records'
        );
    }
}
