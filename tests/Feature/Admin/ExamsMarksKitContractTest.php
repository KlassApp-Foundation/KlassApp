<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamsMarksKitContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_filter_blade_matches_kit_composition(): void
    {
        $blade = file_get_contents(resource_path('views/admin/marks/filter.blade.php'));
        $grid = file_get_contents(resource_path('views/admin/marks/results-table2.blade.php'));

        $this->assertStringContainsString('ds-page-head', $blade);
        $this->assertStringContainsString('ds-reminder-banner', $blade);
        $this->assertStringContainsString('exams-marks-grid', $blade);
        $this->assertStringContainsString('x-ds-kpi-card', $blade);
        $this->assertStringContainsString('ds-grid-marks', $grid);
        $this->assertStringContainsString('gm-subject-code', $grid);
        $this->assertStringContainsString('/100', $grid);
        $this->assertStringContainsString('gm-total', $grid);
        $this->assertStringContainsString('gm-agg', $grid);
        $this->assertStringContainsString('gm-pos', $grid);
    }

    public function test_unfiltered_marks_page_renders_kit_empty_state(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $school = School::create([
            'name' => 'Marks Kit School',
            'email' => 'marks-kit@test.sch.ug',
            'phone' => '0700000111',
            'slug' => 'marks-kit-school',
            'status' => 1,
        ]);

        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        Section::create([
            'school_id' => $school->id,
            'name' => 'S.2',
            'status' => 1,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Marks Admin',
            'email' => 'marks.admin@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Marks',
            'lastname' => 'Admin',
        ]);

        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
            \App\Http\Middleware\MustBeFullSchoolAdmin::class,
            \App\Http\Middleware\MustBePrivilege::class,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.marks.filter'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-testid="exams-page-head"', $html);
        $this->assertStringContainsString('data-testid="exams-empty"', $html);
        $this->assertStringContainsString('Ready when you are', $html);
        $this->assertStringContainsString('ds-page-head-title', $html);
    }

    public function test_pulse_toshi_css_still_greens_kpi_and_blurs_ledger(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));

        $this->assertStringContainsString('.ds-kpi-card .ds-kpi-value', $css);
        $this->assertStringContainsString('color: #22C55E', $css);
        $this->assertStringContainsString('.ds-table-ledger thead', $css);
        $this->assertStringContainsString('backdrop-filter: blur(12px)', $css);
    }
}
