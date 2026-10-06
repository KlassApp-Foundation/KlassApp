<?php

namespace Tests\Feature\Admin;

use App\Events\Notification\SingleNotificationEvent;
use App\Events\SinglePushEvent;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Approval;
use App\Models\LessonPlan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Admin approvals are deny-by-default and school-scoped: only known
 * approvable types can be actioned, and another school's approval must
 * 403 with nothing changed.
 */
class ApprovalSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolA;
    private int $schoolB;
    private int $academicYearA;
    private int $academicYearB;
    private User $adminA;
    private LessonPlan $lessonPlanA;
    private LessonPlan $lessonPlanB;
    private Approval $lessonPlanApprovalA;
    private Approval $lessonPlanApprovalB;
    private Approval $unknownApproval;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        require_once __DIR__.'/../../Support/activity_stub.php';

        Event::fake([
            SingleNotificationEvent::class,
            SinglePushEvent::class,
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = DB::table('schools')->insertGetId([
            'name' => 'Approval School A',
            'slug' => 'approval-school-a',
            'email' => 'approval-a@test.sch.ug',
            'phone' => '+256700000511',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolB = DB::table('schools')->insertGetId([
            'name' => 'Approval School B',
            'slug' => 'approval-school-b',
            'email' => 'approval-b@test.sch.ug',
            'phone' => '+256700000522',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearA = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolA,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearB = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolB,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->adminA = $this->makeUser(3, $this->schoolA, 'approval-admin-a@test.sch.ug', 'Approval Admin A');
        $teacherA = $this->makeUser(5, $this->schoolA, 'approval-teacher-a@test.sch.ug', 'Approval Teacher A');
        $teacherB = $this->makeUser(5, $this->schoolB, 'approval-teacher-b@test.sch.ug', 'Approval Teacher B');

        $lessonPlanATeacherLink = $this->makeTeacherLink($this->schoolA, $this->academicYearA, $teacherA);
        $lessonPlanBTeacherLink = $this->makeTeacherLink($this->schoolB, $this->academicYearB, $teacherB);

        $this->lessonPlanA = LessonPlan::create([
            'school_id' => $this->schoolA,
            'teacher_link_id' => $lessonPlanATeacherLink,
            'unit_no' => '1',
            'unit_name' => 'Unit 1',
            'title' => 'Lesson Plan A',
            'duration' => '00:40:00',
            'description' => 'Plan A description',
            'status' => 'pending',
        ]);

        $this->lessonPlanB = LessonPlan::create([
            'school_id' => $this->schoolB,
            'teacher_link_id' => $lessonPlanBTeacherLink,
            'unit_no' => '1',
            'unit_name' => 'Unit 1',
            'title' => 'Lesson Plan B',
            'duration' => '00:40:00',
            'description' => 'Plan B description',
            'status' => 'pending',
        ]);

        $this->lessonPlanApprovalA = Approval::create([
            'approvable_type' => LessonPlan::class,
            'approvable_id' => $this->lessonPlanA->id,
            'state' => Pending::class,
            'requested_by' => $teacherA->id,
        ]);

        $this->lessonPlanApprovalB = Approval::create([
            'approvable_type' => LessonPlan::class,
            'approvable_id' => $this->lessonPlanB->id,
            'state' => Pending::class,
            'requested_by' => $teacherB->id,
        ]);

        // A type the approvals inbox has no handling for at all.
        $this->unknownApproval = Approval::create([
            'approvable_type' => School::class,
            'approvable_id' => $this->schoolA,
            'state' => Pending::class,
            'requested_by' => $this->adminA->id,
        ]);
    }

    private function makeUser(int $usergroupId, int $schoolId, string $email, string $name): User
    {
        $user = User::factory()->create([
            'school_id' => $schoolId,
            'usergroup_id' => $usergroupId,
            'email' => $email,
            'name' => $name,
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $user->id,
            'school_id' => $schoolId,
            'usergroup_id' => $usergroupId,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name)[1] ?? 'User',
        ]);

        return $user;
    }

    private function makeTeacherLink(int $schoolId, int $academicYearId, User $teacher): int
    {
        $standard = Standard::create(['school_id' => $schoolId, 'name' => 'P1', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $schoolId, 'name' => 'A', 'status' => 1]);
        $link = StandardLink::create([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'no_of_students' => 10,
            'status' => 1,
        ]);

        $subject = Subject::create([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Math',
            'type' => 'core',
            'status' => 1,
        ]);

        return Teacherlink::create([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
            'standardLink_id' => $link->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ])->id;
    }

    public function test_approving_another_schools_lesson_plan_approval_gets_a_403_and_changes_nothing(): void
    {
        $this->actingAs($this->adminA);

        $this->post('/admin/approvals/'.$this->lessonPlanApprovalB->id.'/approve', [
            'comments' => 'Cross school',
        ])->assertForbidden();

        $this->lessonPlanApprovalB->refresh();
        $this->assertSame(Pending::class, $this->lessonPlanApprovalB->state::class);
    }

    public function test_rejecting_another_schools_lesson_plan_approval_gets_a_403_and_changes_nothing(): void
    {
        $this->actingAs($this->adminA);

        $this->post('/admin/approvals/'.$this->lessonPlanApprovalB->id.'/reject', [
            'comments' => 'Cross school',
        ])->assertForbidden();

        $this->lessonPlanApprovalB->refresh();
        $this->assertSame(Pending::class, $this->lessonPlanApprovalB->state::class);
    }

    public function test_an_approval_with_an_unknown_approvable_type_cannot_be_approved(): void
    {
        $this->actingAs($this->adminA);

        $this->post('/admin/approvals/'.$this->unknownApproval->id.'/approve', [
            'comments' => 'Nope',
        ])->assertForbidden();

        $this->unknownApproval->refresh();
        $this->assertSame(Pending::class, $this->unknownApproval->state::class);
    }

    public function test_approving_own_schools_lesson_plan_approval_still_works(): void
    {
        $this->actingAs($this->adminA);

        $this->from('/admin/approvals')
            ->post('/admin/approvals/'.$this->lessonPlanApprovalA->id.'/approve', [
                'comments' => 'Approved',
            ])
            ->assertRedirect();

        $this->lessonPlanApprovalA->refresh();
        $this->assertSame(Approved::class, $this->lessonPlanApprovalA->state::class);
    }
}
