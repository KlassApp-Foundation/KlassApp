<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $this->assertStringContainsString('allows a maximum of 2 students', $result['message']);
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
    public function toshi_add_student_saves_with_notice_when_at_plan_limit(): void
    {
        // Fill to exactly 2 students. Leave registration_number empty so it
        // cannot occupy the id addStudent is about to assign.
        User::factory()->count(2)->create([
            'usergroup_id' => 6,
            'school_id'    => $this->schoolId,
            'registration_number' => null,
        ]);

        $result = ToshiActionService::addStudent($this->admin, ['name' => 'Excess Student']);

        $this->assertTrue($result['success'], 'over-limit must not block Toshi student create');
        $this->assertStringContainsString('allows a maximum of 2 students', $result['message']);
        $this->assertSame(
            3,
            User::where('school_id', $this->schoolId)->where('usergroup_id', 6)->count()
        );
    }

    #[Test]
    public function toshi_add_teacher_saves_with_notice_when_at_plan_limit(): void
    {
        // Fill to exactly 2 teachers
        User::factory()->count(2)->create([
            'usergroup_id' => 5,
            'school_id'    => $this->schoolId,
        ]);

        $result = ToshiActionService::addTeacher($this->admin, [
            'name' => 'Excess Teacher', 'email' => 'excess@teacher.test',
        ]);

        $this->assertTrue($result['success'], 'over-limit must not block Toshi teacher create');
        $this->assertStringContainsString('allows a maximum of 2 teachers', $result['message']);
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
        $this->assertStringContainsString('allows a maximum of 2 administrators', $result['message']);
    }

    // ── HTTP StudentController integration tests ──

    #[Test]
    public function student_controller_store_saves_with_overlimit_notice_when_at_plan_limit(): void
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

        $response->assertSessionMissing('errors');
        $response->assertSessionHas('overlimit');
        $response->assertRedirect();
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
}
