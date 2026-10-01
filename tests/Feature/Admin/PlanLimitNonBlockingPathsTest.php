<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Soft-launch rule: every data-entry path saves first; plan over-limit is a notice.
 * Paths with no plan gate must stay ungated (regression against accidental blocks).
 */
class PlanLimitNonBlockingPathsTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;
    private User $admin;
    private Plan $plan;

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
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Paths School '.Str::random(4),
            'slug' => 'paths-'.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('academic_years')->insert([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->plan = Plan::create([
            'cycle' => 30,
            'name' => 'PathsPlan'.Str::random(4),
            'display_name' => 'Paths Plan',
            'no_of_students' => 1,
            'no_of_users' => 1,
            'is_active' => 1,
            'order' => 99,
            'amount' => 0,
        ]);

        CurrentPlan::create([
            'school_id' => $this->schoolId,
            'plan_id' => $this->plan->id,
            'status' => 'running',
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->schoolId,
            'name' => 'Paths Admin',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'firstname' => 'Paths',
            'lastname' => 'Admin',
        ]);
    }

    /** @test */
    public function student_create_form_never_hard_blocks_at_limit(): void
    {
        User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->schoolId,
            'status' => 'active',
        ]);

        config(['app.url' => 'http://127.0.0.1:8899']);

        $response = $this->actingAs($this->admin)->get('/admin/student/add');

        $response->assertOk();
        $response->assertDontSee('Upgrade Plan to Add More Students', false);
        $response->assertSee('create-member', false);
        $response->assertSee('href="/pricing"', false);
        $response->assertDontSee('http://127.0.0.1:8899/pricing', false);
    }

    /** @test */
    public function parent_create_has_no_plan_upgrade_gate(): void
    {
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->schoolId,
            'name' => 'Child'.Str::random(4),
            'status' => 'active',
        ]);

        // Route is /admin/parent/add?ref_name=… (not a path segment).
        $response = $this->actingAs($this->admin)->get('/admin/parent/add?ref_name='.$student->name);

        $response->assertOk();
        $response->assertDontSee('Upgrade Plan', false);
        $response->assertSee('create-parent', false);
    }

    /** @test */
    public function marks_attendance_fees_timetable_controllers_have_no_plan_limit_gate(): void
    {
        // Static contract: these domains must not grow hard plan-limit blocks.
        $paths = [
            app_path('Http/Controllers/Admin'),
            app_path('Services'),
            app_path('Imports'),
            app_path('Traits/AdmissionUser.php'),
        ];

        $forbidden = [
            'enforcePlanLimit',
            'Upgrade Plan to Add More',
            "withErrors(['plan_limit'",
            'withErrors(["plan_limit"',
        ];

        $scopedFiles = [];
        foreach ([
            '*Mark*', '*Attendance*', '*Fee*', '*Timetable*', '*Schedule*',
            '*Lesson*', '*Homework*',
        ] as $pattern) {
            $scopedFiles = array_merge($scopedFiles, glob(app_path('Http/Controllers/Admin/'.$pattern.'.php') ?: []));
            $scopedFiles = array_merge($scopedFiles, glob(app_path('Services/'.$pattern.'.php') ?: []));
        }
        $scopedFiles[] = app_path('Traits/AdmissionUser.php');
        $scopedFiles = array_values(array_unique(array_filter($scopedFiles, 'is_file')));

        $this->assertNotEmpty($scopedFiles, 'expected mark/attendance/fee/timetable/admission files to exist');

        foreach ($scopedFiles as $file) {
            $contents = file_get_contents($file);
            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $contents,
                    basename($file).' must not hard-gate on plan limits (found '.$needle.')'
                );
            }
        }
    }

    /** @test */
    public function admission_trait_has_no_plan_limit_block_or_truncate(): void
    {
        $contents = file_get_contents(app_path('Traits/AdmissionUser.php'));
        $this->assertStringNotContainsString('enforcePlanLimit', $contents);
        $this->assertStringNotContainsString('no_of_students', $contents);
        $this->assertStringNotContainsString('array_slice', $contents);
    }

    /** @test */
    public function onboarding_engine_save_students_has_no_plan_truncate(): void
    {
        $contents = file_get_contents(app_path('Services/OnboardingEngine.php'));
        // saveStudents must not call enforcePlanLimit or slice by plan size.
        $this->assertStringNotContainsString('enforcePlanLimit', $contents);
        $this->assertDoesNotMatchRegularExpression(
            '/function saveStudents[\s\S]{0,800}array_slice/',
            $contents,
            'saveStudents must not truncate rows by plan size'
        );
    }

    /** @test */
    public function plan_limit_notice_helper_returns_message_only_when_over(): void
    {
        $this->assertNull(ToshiActionService::planLimitNotice($this->schoolId, 'students'));

        User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->schoolId,
            'status' => 'active',
        ]);

        $notice = ToshiActionService::planLimitNotice($this->schoolId, 'students');
        $this->assertNotNull($notice);
        $this->assertStringContainsString('upgrade', strtolower($notice));
    }
}
