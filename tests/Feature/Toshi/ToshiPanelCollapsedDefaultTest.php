<?php

namespace Tests\Feature\Toshi;

use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Soft-launch 1b: Toshi dock stays collapsed by default on every admin/teacher
 * page — never auto-opens or auto-maximizes. Sub-1280 CSS must not force the
 * drawer visible while body/html.toshi-collapsed is set.
 */
class ToshiPanelCollapsedDefaultTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Collapsed Default School '.Str::random(4),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => 'onboarding',
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => Str::random(8).'@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Collapse',
            'lastname' => 'Admin',
        ]);
    }

    public function test_published_css_hides_panel_when_collapsed_below_1280(): void
    {
        $source = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $published = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));

        $this->assertSame($source, $published, 'Run: php artisan vendor:publish --tag=toshi-ui-css --force');

        foreach ([$source, $published] as $css) {
            $this->assertStringContainsString('@media (max-width: 1279px)', $css);
            $this->assertStringContainsString(
                'body.toshi-collapsed [data-toshi-root] .toshi-panel',
                $css,
            );
            $this->assertMatchesRegularExpression(
                '/body\.toshi-collapsed\s+\[data-toshi-root\]\s+\.toshi-panel\s*,\s*html\.toshi-collapsed\s+\[data-toshi-root\]\s+\.toshi-panel\s*\{[^}]*display:\s*none\s*!important/s',
                $css,
                'Collapsed dock must hide the mobile drawer (display:none !important)',
            );
            $this->assertStringContainsString('display: none !important', $css);
        }
    }

    public function test_prepaint_defaults_to_collapsed_when_local_storage_absent(): void
    {
        $prepaint = file_get_contents(resource_path('views/layouts/partials/toshi-prepaint.blade.php'));

        $this->assertStringContainsString("toshi_split_collapsed') !== '0'", $prepaint);
        $this->assertStringContainsString("classList.add('toshi-collapsed')", $prepaint);
    }

    public function test_agent_toshi_blade_syncs_visible_false_when_dock_collapsed(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/agent-toshi.blade.php'));

        $this->assertStringContainsString("\$wire.set('visible', false)", $blade);
        $this->assertStringContainsString('syncVisibleFromDock', $blade);
    }

    public function test_restored_session_state_does_not_leave_panel_visible(): void
    {
        session(['toshi_state' => [
            'mode' => 'complete',
            'visible' => true,
            'maximized' => true,
            'messages' => [],
            'step' => 1,
        ]]);

        Livewire::actingAs($this->admin->fresh())
            ->test(AgentToshi::class)
            ->assertSet('visible', false)
            ->assertSet('maximized', false);
    }

    public function test_teachers_and_dashboard_pages_do_not_auto_maximize_script(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\MustBePrivilege::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
        ]);

        DB::table('academic_years')->insert([
            'school_id' => $this->admin->school_id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teachers = $this->actingAs($this->admin)->get('/admin/teachers');
        $teachers->assertOk();
        $teachers->assertDontSee('Wave 3: open Toshi maximized', false);

        $dashboard = $this->actingAs($this->admin)->get('/admin/dashboard');
        if ($dashboard->isRedirect()) {
            $dashboard = $this->actingAs($this->admin)->get($dashboard->headers->get('Location'));
        }
        $dashboard->assertOk();
        $dashboard->assertDontSee('Wave 3: open Toshi maximized', false);
    }
}
