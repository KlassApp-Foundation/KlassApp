<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\StudentHealthIncident;
use App\Models\StudentHealthProfile;
use App\Models\StudentImmunization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * /admin/health used to be a closure that redirected to the student list. It is now a real
 * school-level summary of the per-student health data, scoped by the authenticated school.
 */
class AdminHealthOverviewTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // The admin group runs MustBePrivilege, the onboarding gate that bounces a school
        // with no academic year or standards. The health page itself is what is under test.
        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Health Overview School', 'slug' => 'health-ov-'.uniqid(),
            'email' => 'ho-'.uniqid().'@t.sch.ug', 'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        Cache::flush();

        $this->admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $this->school->id, 'email' => 'ho.admin@t.sch.ug']);
        $this->student = User::factory()->create(['usergroup_id' => 6, 'school_id' => $this->school->id, 'name' => 'HEALTHPUPIL One', 'status' => 'active', 'email' => 'ho.pupil@t.sch.ug']);

        StudentHealthProfile::create([
            'school_id' => $this->school->id, 'user_id' => $this->student->id,
            'allergies' => 'Peanuts', 'chronic_conditions' => 'Asthma', 'blood_type' => 'O+',
        ]);
        StudentImmunization::create([
            'school_id' => $this->school->id, 'user_id' => $this->student->id,
            'vaccine_name' => 'Measles', 'administered_date' => now()->subMonths(6)->toDateString(),
            'next_due_date' => now()->subDays(5)->toDateString(),
        ]);
        StudentHealthIncident::create([
            'school_id' => $this->school->id, 'user_id' => $this->student->id,
            'incident_date' => now()->subDays(10)->toDateString(),
            'description' => 'Fell during games and grazed a knee', 'action_taken' => 'Cleaned and dressed',
            'severity' => 'moderate', 'recorded_by' => $this->admin->id,
        ]);
    }

    public function test_health_overview_renders_instead_of_redirecting(): void
    {
        $r = $this->actingAs($this->admin)->get('/admin/health');

        $r->assertOk();
        $this->assertFalse($r->isRedirect(), 'the health page must no longer redirect to the student list');
        $this->assertStringContainsString('Health overview', $r->getContent());
    }

    public function test_it_shows_real_health_counts(): void
    {
        $r = $this->actingAs($this->admin)->get('/admin/health');
        $content = $r->getContent();

        // one profile, one immunisation with one overdue, one incident in the last 30 days
        $this->assertStringContainsString('Health profiles', $content);
        $this->assertStringContainsString('1 overdue', $content);
        $this->assertStringContainsString('Incidents (30 days)', $content);
        $this->assertStringContainsString('1 allergies, 1 chronic', $content);
        $this->assertStringContainsString('HEALTHPUPIL One', $content, 'the recent incident row should name the student');
        $this->assertStringContainsString('Moderate', $content);
        $this->assertStringContainsString('Fell during games', $content);
    }

    public function test_it_is_scoped_to_the_authenticated_school(): void
    {
        $other = School::create([
            'name' => 'Health Other School', 'slug' => 'health-other-'.uniqid(),
            'email' => 'ho2-'.uniqid().'@t.sch.ug', 'phone' => '071'.random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $otherAdmin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $other->id, 'email' => 'ho2.admin@t.sch.ug']);
        $otherPupil = User::factory()->create(['usergroup_id' => 6, 'school_id' => $other->id, 'name' => 'OTHERPUPIL Two', 'status' => 'active', 'email' => 'ho2.pupil@t.sch.ug']);
        StudentHealthIncident::create([
            'school_id' => $other->id, 'user_id' => $otherPupil->id,
            'incident_date' => now()->subDays(3)->toDateString(),
            'description' => 'Other school confidential incident',
            'severity' => 'serious', 'recorded_by' => $otherAdmin->id,
        ]);

        $mine = $this->actingAs($this->admin)->get('/admin/health');
        $this->assertStringNotContainsString('OTHERPUPIL Two', $mine->getContent());
        $this->assertStringNotContainsString('Other school confidential incident', $mine->getContent());

        $theirs = $this->actingAs($otherAdmin)->get('/admin/health');
        $this->assertStringContainsString('OTHERPUPIL Two', $theirs->getContent());
        $this->assertStringNotContainsString('HEALTHPUPIL One', $theirs->getContent(), 'no cross-school leakage');
    }
}
