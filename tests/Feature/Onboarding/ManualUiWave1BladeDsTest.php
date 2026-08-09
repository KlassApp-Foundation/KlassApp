<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Wave 1 manual-UI redesign — pure Blade DS restyle of five create screens.
 * Asserts the pages render with design-system primitives and that create POSTs
 * still validate / persist with unchanged field names.
 */
class ManualUiWave1BladeDsTest extends TestCase
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
            'name' => 'Wave1 DS School',
            'email' => 'wave1@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'wave1-ds-school',
            'status' => 1,
            'curriculum' => 'UNEB',
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Wave1 Admin',
            'email' => 'admin@wave1.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Wave1',
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

        Standard::create([
            'school_id' => $this->school->id,
            'name' => 'Primary',
            'status' => 1,
            'order' => 1,
        ]);
    }

    public function test_sections_create_form_uses_design_system_primitives(): void
    {
        $view = $this->actingAs($this->admin)
            ->withViewErrors([])
            ->view('admin.school.sections.create', [
                'classes' => ['Primary One', 'Primary Two'],
            ]);

        $view->assertSee('ds-page-head', false);
        $view->assertSee('ds-card', false);
        $view->assertSee('ds-form-group', false);
        $view->assertSee('ds-btn', false);
        $view->assertSee('name="name"', false);
        $view->assertDontSee('admin-h1');
    }

    public function test_term_create_uses_design_system_primitives(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/academic-term/create');

        $response->assertOk();
        $response->assertSee('ds-card', false);
        $response->assertSee('ds-form-group', false);
        $response->assertSee('ds-btn', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="starts_on"', false);
        $response->assertSee('name="ends_on"', false);
        $response->assertDontSee('admin-h1');
    }

    public function test_fees_create_uses_design_system_primitives(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/fees-categories/create');

        $response->assertOk();
        $response->assertSee('ds-card', false);
        $response->assertSee('ds-form-group', false);
        $response->assertSee('name="standard_id"', false);
        $response->assertSee('name="section_id"', false);
        $response->assertSee('name="academic_term_id"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="amount"', false);
    }

    public function test_standards_create_header_uses_ds_page_head(): void
    {
        // Orphaned Blade file still restyled in Wave 1 for consistency.
        $orphaned = $this->actingAs($this->admin)
            ->withViewErrors([])
            ->view('admin.school.standards.create');

        $orphaned->assertSee('ds-page-head', false);
        $orphaned->assertSee('ds-btn', false);
        $orphaned->assertSee('Add Standards');
        $orphaned->assertDontSee('admin-h1');
        $orphaned->assertDontSee('/uploads/icons/back.svg');
    }

    public function test_live_standard_create_route_uses_standards_add_with_ds_header(): void
    {
        // LIVE /admin/standard/create → admin.school.standards.add (Vue <standard-setup>).
        $response = $this->actingAs($this->admin)->get('/admin/standard/create');

        $response->assertOk();
        $response->assertSee('ds-page-head', false);
        $response->assertSee('ds-btn', false);
        $response->assertSee('Setup Standards');
        $response->assertSee('standard-setup', false);
        $response->assertDontSee('admin-h1');
    }

    public function test_academics_create_header_uses_ds_page_head_and_keeps_vue_component(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/academic/add');

        $response->assertOk();
        $response->assertSee('ds-page-head', false);
        $response->assertSee('ds-btn', false);
        $response->assertSee('create-academic-year', false);
        $response->assertDontSee('admin-h1');
    }

    public function test_term_store_still_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/academic-term/store', [
            'name' => '',
            'starts_on' => '',
            'ends_on' => '',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_section_save_still_persists_with_name_field(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.class.add'), [
            'name' => 'Primary Three',
        ]);

        $response->assertRedirect(route('admin.classes'));
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Primary Three',
        ]);
    }
}
