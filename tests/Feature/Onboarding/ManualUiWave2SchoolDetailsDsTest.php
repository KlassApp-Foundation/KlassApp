<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Wave 2 manual-UI redesign — School Details editdetail Blade + Vue DS tokens.
 * Asserts the live edit route renders with design-system primitives while the
 * address/Maps portal markup stays on legacy tw-form classes.
 */
class ManualUiWave2SchoolDetailsDsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Wave2 DS School',
            'email' => 'wave2@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'wave2-ds-school',
            'status' => 1,
            'curriculum' => 'UNEB',
            'toshi_enabled' => 0,
            'address' => 'P.O.Box 1, Kampala',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Wave2 Admin',
            'email' => 'admin@wave2.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Wave2',
            'lastname' => 'Admin',
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'type' => 'Current Academic Year',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);
    }

    public function test_editdetail_uses_design_system_primitives(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/schooldetails/editdetail/'.$this->school->id);

        $response->assertOk();
        $response->assertSee('Edit School Details', false);
        $response->assertSee('ds-page-head', false);
        $response->assertSee('ds-card', false);
        $response->assertSee('edit-schooldetail', false);
        $response->assertDontSee('admin-h1');
    }

    public function test_editdetail_keeps_address_maps_portal_on_legacy_tw_form(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/schooldetails/editdetail/'.$this->school->id);

        $response->assertOk();
        $response->assertSee('portal to="edit_school_address"', false);
        $response->assertSee('tw-form-group', false);
        $response->assertSee('tw-form-label', false);
        $response->assertSee('id="map_canvas"', false);
        $response->assertSee('name="address"', false);
    }
}
