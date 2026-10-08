<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A5 — quick fixes from the demo role crawl:
 *  - SchoolSubadmin's nav no longer 404s on the year list / notification feed
 *    (routes/setting.php used to shadow the admin definitions with fullschooladmin);
 *  - the admin sidebar hides Settings + Classes & Streams from SchoolSubadmin
 *    (their routes exclude ug4 by design);
 *  - the teacher sidebar no longer links to the unbuilt library activity page;
 *  - the calendar icon asset the stylesheet references actually exists.
 */
class RoleCrawlNitsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $head;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Role Crawl Nits School',
            'slug' => 'role-crawl-nits-' . uniqid(),
            'email' => 'role-crawl-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'email' => 'role-admin@t.sch.ug',
        ]);

        $this->head = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 4,
            'email' => 'role-head@t.sch.ug',
        ]);

        $this->teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'email' => 'role-teacher@t.sch.ug',
        ]);
    }

    public function test_school_subadmin_can_load_the_year_list_endpoint(): void
    {
        $this->actingAs($this->head)
            ->get('/admin/list/academicyear')
            ->assertOk()
            ->assertJsonStructure(['academiclist', 'current_year']);
    }

    public function test_school_subadmin_can_load_the_notification_list_endpoint(): void
    {
        $this->actingAs($this->head)
            ->get('/admin/notification/showList')
            ->assertOk();
    }

    public function test_admin_can_still_load_both_nav_endpoints(): void
    {
        $this->actingAs($this->admin)->get('/admin/list/academicyear')->assertOk();
        $this->actingAs($this->admin)->get('/admin/notification/showList')->assertOk();
    }

    public function test_subadmin_dashboard_hides_settings_and_classes_links(): void
    {
        $response = $this->actingAs($this->head)->get('/subadmin/dashboard');
        $response->assertOk();
        $response->assertDontSee(url('/admin/settings'));
        $response->assertDontSee(url('/admin/classes'));
    }

    public function test_admin_dashboard_still_shows_settings_and_classes_links(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertOk();
        $response->assertSee(url('/admin/settings'), false);
        $response->assertSee(url('/admin/classes'), false);
    }

    public function test_teacher_sidebar_hides_the_unbuilt_library_link(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');
        $response->assertOk();
        $response->assertDontSee('teacher/libraryactivity', false);
    }

    public function test_calendar_icon_asset_exists(): void
    {
        $this->assertFileExists(public_path('uploads/calendar.png'));
    }
}
