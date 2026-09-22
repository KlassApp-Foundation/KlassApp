<?php

namespace Tests\Feature\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The teacher copies of the reception-desk surfaces (visitor log, call log, postal record)
 * are gated behind the per-school teacher_receptionist_access setting, DISABLED by default.
 *
 * Note the delete actions are Route::get, so the gate matches on path as well as method;
 * a method-only check would have missed every delete.
 */
class TeacherReceptionistAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private User $teacher;

    private AcademicYear $year;

    private VisitorLog $ownLog;

    private VisitorLog $foreignLog;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Reception Gate School', 'slug' => 'recep-gate-'.uniqid(),
            'email' => 'rg-'.uniqid().'@t.sch.ug', 'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $this->otherSchool = School::create([
            'name' => 'Other Reception School', 'slug' => 'recep-other-'.uniqid(),
            'email' => 'ro-'.uniqid().'@t.sch.ug', 'phone' => '071'.random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        $this->teacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'rg.teacher@t.sch.ug']);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => (string) now()->year,
            'description' => 'Current Academic Year',
            'start_date' => now()->subMonths(2)->startOfDay(), 'end_date' => now()->addMonths(6)->endOfDay(),
            'status' => 1,
        ]);
        Cache::flush();

        $make = fn (School $s, int $yearId) => VisitorLog::create([
            'school_id' => $s->id, 'academic_year_id' => $yearId,
            'name' => 'Visitor '.uniqid(), 'relation' => 'other', 'visiting_purpose' => 'enquiry',
            'date_of_visit' => now()->format('Y-m-d'),
        ]);

        $this->ownLog = $make($this->school, $this->year->id);
        $otherYear = AcademicYear::create([
            'school_id' => $this->otherSchool->id, 'name' => (string) now()->year,
            'description' => 'Other', 'start_date' => now()->subMonths(2)->startOfDay(),
            'end_date' => now()->addMonths(6)->endOfDay(), 'status' => 1,
        ]);
        $this->foreignLog = $make($this->otherSchool, $otherYear->id);
    }

    private function enable(): void
    {
        $this->school->setDetailValue(SiteHelper::TEACHER_RECEPTIONIST_ACCESS_KEY, '1');
        SiteHelper::forgetTeacherReceptionistAccess($this->school->id);
    }

    public function test_writes_are_refused_by_default(): void
    {
        $this->actingAs($this->teacher)->post('/teacher/visitorlog/add', [])->assertForbidden();
        $this->actingAs($this->teacher)->post('/teacher/calllog/add', [])->assertForbidden();
        $this->actingAs($this->teacher)->post('/teacher/postalrecord/add', [])->assertForbidden();
    }

    public function test_destructive_delete_via_get_is_refused_by_default(): void
    {
        $this->actingAs($this->teacher)
            ->get('/teacher/visitorlog/delete/'.$this->ownLog->id)
            ->assertForbidden();

        $this->assertNull(VisitorLog::withTrashed()->find($this->ownLog->id)->deleted_at);
    }

    public function test_read_actions_are_not_gated(): void
    {
        $this->assertNotSame(403, $this->actingAs($this->teacher)->get('/teacher/visitorlog')->status());
        $this->assertNotSame(403, $this->actingAs($this->teacher)->get('/teacher/visitorlog/list')->status());
    }

    public function test_missing_or_unknown_setting_resolves_to_disabled(): void
    {
        $this->assertFalse(SiteHelper::teacherReceptionistAccessEnabled($this->school->id));

        $this->school->setDetailValue(SiteHelper::TEACHER_RECEPTIONIST_ACCESS_KEY, 'yes-please');
        SiteHelper::forgetTeacherReceptionistAccess($this->school->id);
        $this->assertFalse(SiteHelper::teacherReceptionistAccessEnabled($this->school->id));

        $this->actingAs($this->teacher)->get('/teacher/visitorlog/delete/'.$this->ownLog->id)->assertForbidden();
    }

    public function test_enabling_the_setting_allows_the_write(): void
    {
        $this->enable();
        $this->assertTrue(SiteHelper::teacherReceptionistAccessEnabled($this->school->id));

        $this->actingAs($this->teacher)
            ->get('/teacher/visitorlog/delete/'.$this->ownLog->id)
            ->assertOk();

        $this->assertNotNull(VisitorLog::withTrashed()->find($this->ownLog->id)->deleted_at);
    }

    public function test_cross_school_record_is_not_reachable_even_when_enabled(): void
    {
        $this->enable();

        $this->actingAs($this->teacher)
            ->get('/teacher/visitorlog/delete/'.$this->foreignLog->id)
            ->assertNotFound();

        $this->assertNull(VisitorLog::withTrashed()->find($this->foreignLog->id)->deleted_at);
    }
}
