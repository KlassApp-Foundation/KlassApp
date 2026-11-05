<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\Superadmin\Academics\SchoolDetail;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use App\Services\PlanLimitService;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlanLimitEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;
    private User $admin;
    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        // Seed usergroups
        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // School
        $this->schoolId = DB::table('schools')->insertGetId([
            'name'                 => 'Plan Limit Test School',
            'slug'                 => 'plan-limit-test',
            'email'                => 'planlimit@test.sch.ug',
            'phone'                => '+256700000001',
            'status'               => 1,
            'toshi_mode'           => 'preview',
            'registration_country' => 'Uganda',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Academic year + standard (satisfies MustBePrivilege middleware)
        DB::table('academic_years')->insert([
            'school_id'   => $this->schoolId,
            'name'        => '2026',
            'description' => 'Current Academic Year',
            'status'      => 1,
            'start_date'  => '2026-01-01',
            'end_date'    => '2026-12-31',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        DB::table('standards')->insert([
            'school_id'  => $this->schoolId,
            'name'       => 'Primary 1',
            'order'      => 1,
            'status'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Plan with limit of 2 students, 2 teachers
        $planId = DB::table('plans')->insertGetId([
            'name'          => 'TestPlan',
            'display_name'  => 'TestPlan',
            'cycle'         => 30,
            'no_of_students'=> 2,
            'no_of_users'   => 2,
            'amount'        => 0,
            'order'         => 1,
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $this->plan = Plan::find($planId);

        // CurrentPlan (canonical runtime source)
        CurrentPlan::create([
            'school_id' => $this->schoolId,
            'plan_id'   => $this->plan->id,
            'status'    => 'running',
        ]);

        // Admin
        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id'    => $this->schoolId,
            'name'         => 'Plan Admin',
            'email'        => 'planadmin@test.sch.ug',
            'password'     => bcrypt('password'),
            'status'       => 'active',
            'email_verified' => 1,
            // Factory numbers are KLS001 plus a random suffix. School 1's first
            // generated id is KLS0010001, so a factory draw of 0001 collides.
            'registration_number' => null,
        ]);
    }

    // ── enforcePlanLimit() unit-style tests ──

    #[Test]
    public function enforce_plan_limit_passes_when_under_limit(): void
    {
        // 0 students exist, limit is 2
        $result = ToshiActionService::enforcePlanLimit($this->schoolId, 'students');

        $this->assertTrue($result['success']);
        $this->assertSame('', $result['message']);
    }

    #[Test]
    public function enforce_plan_limit_blocks_when_at_limit(): void
    {
        // Fill to exactly 2 students
        User::factory()->count(2)->create([
            'usergroup_id' => 6,
            'school_id'    => $this->schoolId,
        ]);

        $result = ToshiActionService::enforcePlanLimit($this->schoolId, 'students');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('allows 2 students', $result['message']);
        $this->assertStringContainsString('You already have 2', $result['message']);
        $this->assertStringContainsString('Talk to sales', $result['message']);
        $this->assertStringContainsString('upgrade', $result['message']);
    }

    #[Test]
    public function enforce_plan_limit_passes_when_no_plan_configured(): void
    {
        // School with no CurrentPlan
        $otherSchoolId = DB::table('schools')->insertGetId([
            'name' => 'No Plan School', 'slug' => 'no-plan',
            'email' => 'noplan@test.sch.ug', 'phone' => '+256700000099',
            'status' => 1, 'toshi_mode' => 'preview', 'registration_country' => 'Uganda',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $result = ToshiActionService::enforcePlanLimit($otherSchoolId, 'students');

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function enforce_plan_limit_blocks_teachers_separately(): void
    {
        // 2 teachers already (at limit)
        User::factory()->count(2)->create([
            'usergroup_id' => 5,
            'school_id'    => $this->schoolId,
        ]);

        $result = ToshiActionService::enforcePlanLimit($this->schoolId, 'teachers');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('2 teachers', $result['message']);
    }

    #[Test]
    public function enforce_plan_limit_blocks_admins_separately(): void
    {
        // Admin + 1 additional co-admin = 2 users with usergroup_id=3
        // The admin was created in setUp, so count is already 1
        User::factory()->create([
            'usergroup_id' => 3,
            'school_id'    => $this->schoolId,
        ]);

        $result = ToshiActionService::enforcePlanLimit($this->schoolId, 'admins');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('2 administrators', $result['message']);
    }

    // ── ToshiActionService integration tests ──

    #[Test]
    public function toshi_add_student_is_blocked_at_plan_limit(): void
    {
        // Fill to exactly 2 students. Leave registration_number empty so it
        // cannot occupy the id addStudent is about to assign.
        User::factory()->count(2)->create([
            'usergroup_id' => 6,
            'school_id'    => $this->schoolId,
            'registration_number' => null,
        ]);

        $result = ToshiActionService::addStudent($this->admin, ['name' => 'Excess Student']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('allows 2 students', $result['message']);
        $this->assertStringContainsString('Talk to sales', $result['message']);
        $this->assertSame(
            2,
            User::where('school_id', $this->schoolId)->where('usergroup_id', 6)->count()
        );
    }

    #[Test]
    public function toshi_add_teacher_is_blocked_at_plan_limit(): void
    {
        // Fill to exactly 2 teachers
        User::factory()->count(2)->create([
            'usergroup_id' => 5,
            'school_id'    => $this->schoolId,
        ]);

        $result = ToshiActionService::addTeacher($this->admin, [
            'name' => 'Excess Teacher', 'email' => 'excess@teacher.test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('allows 2 teachers', $result['message']);
        $this->assertStringContainsString('Talk to sales', $result['message']);
        $this->assertSame(
            2,
            User::where('school_id', $this->schoolId)->where('usergroup_id', 5)->count()
        );
    }

    #[Test]
    public function toshi_add_coadmin_saves_with_notice_when_at_plan_limit(): void
    {
        // Admin exists from setUp + 1 more = 2 total (at limit)
        User::factory()->create([
            'usergroup_id' => 3,
            'school_id'    => $this->schoolId,
        ]);

        $result = ToshiActionService::addCoAdmin($this->admin, [
            'name' => 'Excess Admin', 'email' => 'excess@admin.test',
        ]);

        $this->assertTrue($result['success'], 'over-limit must not block Toshi co-admin invite');
        $this->assertStringContainsString('allows 2 administrators', $result['message']);
        $this->assertStringContainsString('Talk to sales', $result['message']);
    }

    // ── HTTP StudentController integration tests ──

    #[Test]
    public function student_controller_store_is_blocked_at_plan_limit(): void
    {
        // Fill to exactly 2 students. Leave registration_number empty so it
        // cannot occupy the id the add-student form is about to assign.
        User::factory()->count(2)->create([
            'usergroup_id' => 6,
            'school_id'    => $this->schoolId,
            'registration_number' => null,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post('/admin/student/add', [
            '_token' => csrf_token(),
            'firstname' => 'OverLimit',
            'lastname'  => 'Student',
            'gender'    => 'male',
            'class'     => 'Primary 1',
        ]);

        $response->assertSessionHas('overlimit', function ($message) {
            return str_contains($message, 'allows 2 students')
                && str_contains($message, 'Talk to sales');
        });
        $response->assertRedirect();
        $this->assertSame(
            2,
            User::where('school_id', $this->schoolId)->where('usergroup_id', 6)->count()
        );
    }

    #[Test]
    public function student_controller_store_succeeds_when_under_limit(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/student/add', [
            '_token' => csrf_token(),
            'firstname' => 'Valid',
            'lastname'  => 'Student',
            'gender'    => 'male',
            'date_of_birth' => '2015-01-01',
            'class'     => 'Primary 1',
        ]);

        $response->assertSessionMissing('plan_limit');
    }

    // ── Divergence regression test ──

    #[Test]
    public function enforcement_uses_current_plan_not_stale_subscription(): void
    {
        // Simulate divergence: admin changed plan via CurrentPlanController
        // without updating Subscription.
        $oldPlanId = DB::table('plans')->insertGetId([
            'name'          => 'OldUnlimited',
            'display_name'  => 'OldUnlimited',
            'cycle'         => 30,
            'no_of_students'=> 999,
            'no_of_users'   => 999,
            'amount'        => 0,
            'order'         => 2,
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $oldPlan = Plan::find($oldPlanId);

        $newPlanId = DB::table('plans')->insertGetId([
            'name'          => 'NewStrict',
            'display_name'  => 'NewStrict',
            'cycle'         => 30,
            'no_of_students'=> 1,
            'no_of_users'   => 1,
            'amount'        => 0,
            'order'         => 3,
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $newPlan = Plan::find($newPlanId);

        DB::table('subscriptions')->insert([
            'school_id' => $this->schoolId,
            'user_id'   => $this->admin->id,
            'plan_id'   => $oldPlan->id,
            'status'    => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CurrentPlan::where('school_id', $this->schoolId)->update(['plan_id' => $newPlan->id]);

        // One student already exists — at NewStrict's limit of 1
        User::factory()->create([
            'usergroup_id' => 6,
            'school_id'    => $this->schoolId,
        ]);

        $result = ToshiActionService::enforcePlanLimit($this->schoolId, 'students');

        $this->assertFalse($result['success'], 'Should read from CurrentPlan (NewStrict), not stale Subscription (OldUnlimited)');
        $this->assertStringContainsString('NewStrict', $result['message']);
        $this->assertStringContainsString('1 students', $result['message']);
    }

    // ── Message format ──

    #[Test]
    public function enforce_plan_limit_message_is_safe_for_toshi_and_http(): void
    {
        // Fill to exactly 2 students (at the limit)
        User::factory()->count(2)->create([
            'usergroup_id' => 6,
            'school_id'    => $this->schoolId,
        ]);

        $result = ToshiActionService::enforcePlanLimit($this->schoolId, 'students');

        $this->assertFalse($result['success']);
        $this->assertStringNotContainsString('<', $result['message']);
        $this->assertStringNotContainsString('route', $result['message']);
        $this->assertStringNotContainsString('url', $result['message']);
        $this->assertStringNotContainsString('http', $result['message']);
    }

    // ── Early adopter and platform assignment ──

    #[Test]
    public function early_adopter_plan_has_no_teacher_or_student_limits(): void
    {
        Mail::fake();

        $early = Plan::create([
            'name' => 'early_adopter_test',
            'display_name' => 'Early adopter',
            'cycle' => 30,
            'order' => 0,
            'is_active' => 1,
            'is_platform_only' => 1,
            'amount' => 0,
            'no_of_users' => 0,
            'no_of_students' => 0,
        ]);

        CurrentPlan::where('school_id', $this->schoolId)->update(['plan_id' => $early->id]);

        $limits = app(PlanLimitService::class);
        $this->assertNull($limits->limit($this->schoolId, 'teachers'));
        $this->assertNull($limits->limit($this->schoolId, 'students'));
        $this->assertFalse($limits->status($this->schoolId, 'students')['blocked']);

        // Two of each would already fill the old test plan.
        User::factory()->count(2)->create(['usergroup_id' => 5, 'school_id' => $this->schoolId]);
        User::factory()->count(2)->create(['usergroup_id' => 6, 'school_id' => $this->schoolId]);

        $teacher = ToshiActionService::addTeacher($this->admin, [
            'name' => 'Early Teacher',
            'email' => 'early.teacher@example.test',
        ]);
        $this->assertTrue($teacher['success'], (string) ($teacher['message'] ?? ''));

        $student = ToshiActionService::addStudent($this->admin, ['name' => 'Early Student']);
        $this->assertTrue($student['success'], (string) ($student['message'] ?? ''));

        // T-091 (#1262): Toshi's add-teacher invites by email and stores no user
        // row until the teacher sets a password, so the invite is the proof the
        // early-adopter plan did not block the add.
        $this->assertSame(2, User::where('school_id', $this->schoolId)->where('usergroup_id', 5)->count());
        $this->assertDatabaseHas('teacher_invites', [
            'school_id' => $this->schoolId,
            'email' => 'early.teacher@example.test',
            'user_id' => null,
        ]);
        $this->assertSame(3, User::where('school_id', $this->schoolId)->where('usergroup_id', 6)->count());
    }

    #[Test]
    public function a_platform_admin_can_assign_early_adopter_to_a_school(): void
    {
        $early = Plan::create([
            'name' => 'early_adopter_assign',
            'display_name' => 'Early adopter',
            'cycle' => 30,
            'order' => 0,
            'is_active' => 1,
            'is_platform_only' => 1,
            'amount' => 0,
            'no_of_users' => 0,
            'no_of_students' => 0,
        ]);

        Livewire::test(SchoolDetail::class, ['id' => $this->schoolId])
            ->set('selectedPlanId', $early->id)
            ->call('assignPlan')
            ->assertHasNoErrors();

        $this->assertSame(
            $early->id,
            (int) CurrentPlan::where('school_id', $this->schoolId)->value('plan_id')
        );
    }

    #[Test]
    public function a_school_cannot_choose_a_platform_only_plan_itself(): void
    {
        $early = Plan::create([
            'name' => 'early_adopter_hidden',
            'display_name' => 'Early adopter',
            'cycle' => 30,
            'order' => 0,
            'is_active' => 1,
            'is_platform_only' => 1,
            'amount' => 0,
            'no_of_users' => 0,
            'no_of_students' => 0,
        ]);

        $this->expectException(ValidationException::class);
        app(OnboardingEngine::class)->savePlan(School::findOrFail($this->schoolId), $early->id, true);
    }

    // ── Engine cap (the wizard and Toshi write through this) ──

    #[Test]
    public function onboarding_engine_caps_teachers_at_the_plan_limit(): void
    {
        User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->schoolId]);

        $school = School::findOrFail($this->schoolId);
        $year = \App\Models\AcademicYear::where('school_id', $this->schoolId)->firstOrFail();

        $result = app(OnboardingEngine::class)->saveTeachers($school, $year, [
            ['name' => 'Teacher One', 'email' => 'cap.teacher.one@example.test'],
            ['name' => 'Teacher Two', 'email' => 'cap.teacher.two@example.test'],
            ['name' => 'Teacher Three', 'email' => 'cap.teacher.three@example.test'],
        ]);

        $this->assertCount(1, $result['created']);
        $this->assertCount(2, $result['skipped']);
        $this->assertStringContainsString('allows 2 teachers', $result['skipped'][0]['reason']);
        $this->assertStringContainsString('Talk to sales', $result['skipped'][0]['reason']);
        $this->assertSame(2, User::where('school_id', $this->schoolId)->where('usergroup_id', 5)->count());
    }

    #[Test]
    public function teacher_add_form_is_blocked_at_the_plan_limit(): void
    {
        User::factory()->count(2)->create(['usergroup_id' => 5, 'school_id' => $this->schoolId]);

        $response = $this->actingAs($this->admin)->post('/admin/teacher/add', [
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('overlimit', function ($message) {
            return str_contains($message, 'allows 2 teachers')
                && str_contains($message, 'Talk to sales');
        });
        $this->assertSame(2, User::where('school_id', $this->schoolId)->where('usergroup_id', 5)->count());
    }

    #[Test]
    public function teacher_import_stops_at_the_limit_and_reports_the_rest(): void
    {
        User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->schoolId]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['firstname', 'lastname', 'email', 'mobile_no', 'gender'], null, 'A1');
        for ($i = 1; $i <= 4; $i++) {
            $sheet->fromArray([
                'Imported '.$i,
                'Teacher',
                'imported.teacher.'.$i.'@example.test',
                '+2567000000'.$i,
                'male',
            ], null, 'A'.($i + 1));
        }
        $tmp = tempnam(sys_get_temp_dir(), 'teacher-limit').'.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);

        $file = new UploadedFile(
            $tmp,
            'teachers.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $response = $this->actingAs($this->admin)->post('/admin/importTeachers', [
            'import_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('overlimit', function ($message) {
            return str_contains($message, 'Imported 1 teacher')
                && str_contains($message, '3 teachers were not imported')
                && str_contains($message, 'Talk to sales');
        });
        $this->assertSame(2, User::where('school_id', $this->schoolId)->where('usergroup_id', 5)->count());

        @unlink($tmp);
    }
}
