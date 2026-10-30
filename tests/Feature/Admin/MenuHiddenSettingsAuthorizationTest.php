<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Settings rows are hidden from a head teacher (usergroup 4) by
 * config/navigation.php condition school_admin. The server must refuse those
 * URLs too. School admin (3) and site admin (1) stay allowed, the same
 * allow-list as the settings middleware.
 */
class MenuHiddenSettingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $siteAdmin;

    private User $head;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Menu Gate School',
            'slug' => 'menu-gate-school',
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => '1',
        ]);

        $this->admin = $this->makeUser('School Admin', 'admin@menu-gate.test', 3);
        $this->siteAdmin = $this->makeUser('Site Admin', 'site@menu-gate.test', 1);
        $this->head = $this->makeUser('Head Teacher', 'head@menu-gate.test', 4);
    }

    public function test_head_teacher_is_forbidden_on_school_details(): void
    {
        $this->actingAs($this->head)
            ->get('/admin/schooldetails')
            ->assertForbidden();

        $this->actingAs($this->head)
            ->get('/admin/schooldetails/editdetail/'.$this->school->id)
            ->assertForbidden();

        $this->actingAs($this->head)
            ->get('/admin/school-profile')
            ->assertForbidden();
    }

    public function test_school_admin_and_site_admin_can_open_school_details(): void
    {
        foreach ([$this->admin, $this->siteAdmin] as $user) {
            $this->actingAs($user)
                ->get('/admin/schooldetails')
                ->assertOk();

            $this->actingAs($user)
                ->get('/admin/schooldetails/editdetail/'.$this->school->id)
                ->assertOk();

            $this->actingAs($user)
                ->get('/admin/school-profile')
                ->assertRedirect('/admin/schooldetails/editdetail/'.$this->school->id);
        }
    }

    public function test_head_teacher_is_refused_on_academic_years(): void
    {
        // The index URL is registered again in routes/setting.php, later, under
        // fullschooladmin, which already answers 404. The list behind that page
        // was still open.
        $this->actingAs($this->head)
            ->get('/admin/academics')
            ->assertNotFound();

        $this->actingAs($this->head)
            ->get('/admin/academic/list')
            ->assertForbidden();
    }

    public function test_school_admin_and_site_admin_can_open_academic_years(): void
    {
        $this->actingAs($this->admin)->get('/admin/academics')->assertOk();
        $this->actingAs($this->siteAdmin)->get('/admin/academics')->assertOk();

        $this->actingAs($this->admin)->get('/admin/academic/list')->assertOk();
        $this->actingAs($this->siteAdmin)->get('/admin/academic/list')->assertOk();
    }

    public function test_head_teacher_is_forbidden_on_academic_terms(): void
    {
        $this->actingAs($this->head)
            ->get('/admin/academic-term/create')
            ->assertForbidden();
    }

    public function test_school_admin_and_site_admin_can_open_academic_terms(): void
    {
        $this->actingAs($this->admin)->get('/admin/academic-term/create')->assertOk();
        $this->actingAs($this->siteAdmin)->get('/admin/academic-term/create')->assertOk();
    }

    public function test_head_teacher_is_forbidden_on_classes_and_streams(): void
    {
        $this->actingAs($this->head)->get('/admin/sections')->assertForbidden();
        $this->actingAs($this->head)->get('/admin/classes/add')->assertForbidden();
        $this->actingAs($this->head)->get('/admin/classes')->assertForbidden();
    }

    public function test_school_admin_and_site_admin_can_open_classes_and_streams(): void
    {
        $this->actingAs($this->admin)->get('/admin/sections')->assertRedirect(route('admin.classes.index'));
        $this->actingAs($this->siteAdmin)->get('/admin/sections')->assertRedirect(route('admin.classes.index'));

        $this->actingAs($this->admin)->get('/admin/classes/add')->assertOk();
        $this->actingAs($this->siteAdmin)->get('/admin/classes/add')->assertOk();

        $this->actingAs($this->admin)->get('/admin/classes')->assertOk();
        $this->actingAs($this->siteAdmin)->get('/admin/classes')->assertOk();
    }

    public function test_head_teacher_is_forbidden_on_data_exports(): void
    {
        $this->actingAs($this->head)
            ->get('/admin/reports')
            ->assertForbidden();
    }

    public function test_school_admin_and_site_admin_can_open_data_exports(): void
    {
        $this->actingAs($this->admin)->get('/admin/reports')->assertOk();
        $this->actingAs($this->siteAdmin)->get('/admin/reports')->assertOk();
    }

    public function test_head_teacher_can_open_exports_linked_from_fees_and_parents(): void
    {
        // Fees and Parents stay in the head-teacher menu. Those pages link to
        // these two downloads, so they stay outside the data-exports gate.
        $this->actingAs($this->head)
            ->get('/admin/fees/payments')
            ->assertOk()
            ->assertSee('Export statement', false)
            ->assertSee('/admin/report/fees', false);

        $this->actingAs($this->head)
            ->get('/admin/parents')
            ->assertOk()
            ->assertSee('/admin/report/parents', false);

        $this->assertSame(200, $this->downloadStatus('/admin/report/fees'));
        $this->assertSame(200, $this->downloadStatus('/admin/report/parents'));

        $this->actingAs($this->admin);
        $this->assertSame(200, $this->downloadStatus('/admin/report/fees'));
        $this->assertSame(200, $this->downloadStatus('/admin/report/parents'));
    }

    public function test_head_teacher_is_still_forbidden_on_settings_only_exports(): void
    {
        // These are linked from the data-exports index, not from a page a
        // head teacher is meant to use.
        $this->actingAs($this->head)->get('/admin/report/activeStudents')->assertForbidden();
        $this->actingAs($this->head)->get('/admin/report/exportHolidays')->assertForbidden();
        $this->actingAs($this->head)->get('/admin/report/birthday/student')->assertForbidden();
    }

    public function test_head_teacher_report_cards_do_not_link_to_school_details(): void
    {
        $this->actingAs($this->head)
            ->get('/admin/reports/cards')
            ->assertOk()
            ->assertDontSee('/admin/schooldetails', false)
            ->assertSee('A school admin manages the school logo', false);

        $this->actingAs($this->admin)
            ->get('/admin/reports/cards')
            ->assertOk()
            ->assertSee('/admin/schooldetails/editdetail/'.$this->school->id, false)
            ->assertDontSee('A school admin manages the school logo', false);
    }

    public function test_head_teacher_cannot_post_school_details_or_store_a_class(): void
    {
        $this->actingAs($this->head)
            ->post('/admin/schooldetails/update/'.$this->school->id, ['name' => 'Renamed'])
            ->assertForbidden();

        $this->actingAs($this->head)
            ->post('/admin/classes/store', ['name' => 'P.1'])
            ->assertForbidden();
    }

    /**
     * The fee and parent exports write a CSV straight to the output buffer.
     */
    private function downloadStatus(string $path): int
    {
        ob_start();
        try {
            return $this->get($path)->getStatusCode();
        } finally {
            ob_end_clean();
        }
    }

    private function makeUser(string $name, string $email, int $usergroupId): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password123'),
            'usergroup_id' => $usergroupId,
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'usergroup_id' => $usergroupId,
            'status' => 'active',
        ]);

        return $user;
    }
}
