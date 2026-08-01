<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Events;
use App\Models\Standard;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LegacyPortalEventDestroyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolA;

    private int $schoolB;

    private int $academicYearA;

    private int $academicYearB;

    private User $siteAdmin;

    private User $adminA;

    private User $adminB;

    private Events $eventA;

    private Events $eventB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        require_once __DIR__.'/../Support/activity_stub.php';

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = DB::table('schools')->insertGetId([
            'name' => 'Event School A',
            'slug' => 'event-school-a',
            'email' => 'event-a@test.sch.ug',
            'phone' => '+256700000111',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolB = DB::table('schools')->insertGetId([
            'name' => 'Event School B',
            'slug' => 'event-school-b',
            'email' => 'event-b@test.sch.ug',
            'phone' => '+256700000222',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearA = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolA,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearB = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolB,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$this->schoolA, $this->schoolB] as $sid) {
            Standard::create([
                'school_id' => $sid,
                'name' => 'Primary 1',
                'order' => 1,
                'status' => 1,
            ]);
        }

        $this->siteAdmin = User::factory()->create([
            'school_id' => $this->schoolA,
            'usergroup_id' => 1,
            'email' => 'siteadmin.event@test.sch.ug',
            'name' => 'Site Admin',
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $this->siteAdmin->id,
            'school_id' => $this->schoolA,
            'usergroup_id' => 1,
            'firstname' => 'Site',
            'lastname' => 'Admin',
        ]);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA,
            'usergroup_id' => 3,
            'email' => 'admin.a.event@test.sch.ug',
            'name' => 'Admin A',
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $this->adminA->id,
            'school_id' => $this->schoolA,
            'usergroup_id' => 3,
            'firstname' => 'Admin',
            'lastname' => 'A',
        ]);

        $this->adminB = User::factory()->create([
            'school_id' => $this->schoolB,
            'usergroup_id' => 3,
            'email' => 'admin.b.event@test.sch.ug',
            'name' => 'Admin B',
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $this->adminB->id,
            'school_id' => $this->schoolB,
            'usergroup_id' => 3,
            'firstname' => 'Admin',
            'lastname' => 'B',
        ]);

        $this->eventA = $this->makeEvent(
            schoolId: $this->schoolA,
            academicYearId: $this->academicYearA,
            title: 'School A Event',
            createdBy: $this->adminA->id,
            start: '2026-08-10 08:00:00',
            end: '2026-08-10 17:00:00',
        );

        $this->eventB = $this->makeEvent(
            schoolId: $this->schoolB,
            academicYearId: $this->academicYearB,
            title: 'School B Event',
            createdBy: $this->adminB->id,
            start: '2026-08-11 08:00:00',
            end: '2026-08-11 17:00:00',
        );
    }

    public function test_event_destroy_gate_allows_ug3_own_school_and_denies_other(): void
    {
        $this->actingAs($this->adminA);
        $this->assertTrue(Gate::allows('event-destroy', $this->eventA));
        $this->assertTrue(Gate::denies('event-destroy', $this->eventB));

        $this->actingAs($this->adminB);
        $this->assertTrue(Gate::allows('event-destroy', $this->eventB));
        $this->assertTrue(Gate::denies('event-destroy', $this->eventA));
    }

    public function test_event_destroy_gate_allows_ug1_cross_school(): void
    {
        $this->actingAs($this->siteAdmin);
        $this->assertTrue(Gate::allows('event-destroy', $this->eventA));
        $this->assertTrue(Gate::allows('event-destroy', $this->eventB));
    }

    public function test_ug3_can_destroy_own_school_event(): void
    {
        $this->actingAs($this->adminA);

        $this->get('/admin/events/delete/'.$this->eventA->id)
            ->assertRedirect('/admin/events');

        $this->assertSoftDeleted('events', [
            'id' => $this->eventA->id,
        ]);
    }

    public function test_ug3_cannot_destroy_other_school_event(): void
    {
        $this->actingAs($this->adminA);

        $this->get('/admin/events/delete/'.$this->eventB->id)
            ->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $this->eventB->id,
            'deleted_at' => null,
        ]);
    }

    public function test_ug1_can_destroy_cross_school_event(): void
    {
        $this->actingAs($this->siteAdmin);

        $this->get('/admin/events/delete/'.$this->eventB->id)
            ->assertRedirect('/admin/events');

        $this->assertSoftDeleted('events', [
            'id' => $this->eventB->id,
        ]);
    }

    public function test_event_read_gate_unchanged_for_school_scoped_details(): void
    {
        $this->actingAs($this->adminA);
        $this->assertTrue(Gate::allows('event', $this->eventA));
        $this->assertTrue(Gate::denies('event', $this->eventB));
    }

    private function makeEvent(
        int $schoolId,
        int $academicYearId,
        string $title,
        int $createdBy,
        string $start,
        string $end,
        string $selectType = 'school',
        ?int $standardId = null,
        string $category = 'holidays',
    ): Events {
        $event = new Events([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
            'select_type' => $selectType,
            'standard_id' => $standardId,
            'title' => $title,
            'description' => $title,
            'category' => $category,
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'active',
            'created_by' => $createdBy,
        ]);
        $event->batch = '';
        $event->color = 'green';
        $event->save();

        return $event->fresh();
    }
}
