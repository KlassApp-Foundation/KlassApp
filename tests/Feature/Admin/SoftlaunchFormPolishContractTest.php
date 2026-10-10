<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Soft-launch 1e: empty DOB on Add Student, staff KPI spacing, search icon padding,
 * academic-year selector selects the current (status=1) year.
 */
class SoftlaunchFormPolishContractTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private int $schoolId;

    private int $currentYearId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            \App\Http\Middleware\MustBePrivilege::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
        ]);

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Form Polish School '.Str::random(4),
            'slug' => 'form-polish-'.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Older year first by name ASC so a broken selector would show this instead of current.
        DB::table('academic_years')->insert([
            'school_id' => $this->schoolId,
            'name' => '2024–2025',
            'description' => 'Previous',
            'status' => 0,
            'start_date' => '2024-02-01',
            'end_date' => '2024-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->currentYearId = (int) DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => '2025–2026',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2025-02-01',
            'end_date' => '2025-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::forget('academic_year');
        Cache::forget('academic_year_for_school_'.$this->schoolId);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->schoolId,
            'name' => 'Polish Admin',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'firstname' => 'Polish',
            'lastname' => 'Admin',
        ]);
    }

    public function test_add_student_member_payload_has_empty_date_of_birth(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/admin/student');

        $response->assertOk();
        $payload = $response->json();
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('date_of_birth', $payload);
        $this->assertSame('', $payload['date_of_birth']);
        $this->assertDoesNotMatchRegularExpression(
            '/^\d{4}-\d{2}-\d{2}$/',
            (string) $payload['date_of_birth'],
            'Add Student must not pre-fill a concrete date of birth'
        );
    }

    public function test_teachers_page_kpi_has_spacing_and_search_uses_icon_padding_class(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/teachers')->assertOk()->getContent();

        $this->assertStringContainsString('data-people-list="teachers"', $html);
        $this->assertStringContainsString('ds-form-input--with-icon', $html);
        $this->assertStringContainsString('id="teachers-search"', $html);
    }

    public function test_students_search_uses_icon_padding_class(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/students')->assertOk()->getContent();

        $this->assertStringContainsString('ds-form-input--with-icon', $html);
        $this->assertStringContainsString('id="students-search"', $html);
    }

    public function test_dashboard_refresh_css_defines_with_icon_left_padding(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));
        $this->assertIsString($css);
        $this->assertMatchesRegularExpression(
            '/\.ds-form-input\.ds-form-input--with-icon\s*\{\s*padding-left:\s*2\.5rem;/',
            $css
        );
    }

    public function test_navigation_vue_coerces_academic_year_ids_to_strings(): void
    {
        $source = file_get_contents(resource_path('assets/js/components/Navigation.vue'));
        $this->assertIsString($source);
        $this->assertStringContainsString('String(current.id)', $source);
        $this->assertStringContainsString(':value="String(academic.id)"', $source);
    }

    public function test_academic_year_list_marks_status_one_year_as_current(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/admin/list/academicyear');

        $response->assertOk();
        $current = $response->json('current_year');
        $this->assertIsArray($current);
        $this->assertSame($this->currentYearId, (int) $current['id']);
        $this->assertSame('2025–2026', $current['name']);
    }
}
