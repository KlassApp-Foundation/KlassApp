<?php

namespace Tests\Feature;

use App\Events\Notification\SingleNotificationEvent;
use App\Events\SinglePushEvent;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Approval;
use App\Models\Assignment;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAssignment;
use App\Models\Subject;
use App\Models\TeacherLeaveApplication;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\States\Approval\Pending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LegacyPortalTeacherLeaveAssignmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolA;

    private int $schoolB;

    private int $academicYearA;

    private int $academicYearB;

    private int $standardLinkA;

    private int $subjectA;

    private User $siteAdmin;

    private User $adminA;

    private User $adminB;

    private User $teacherOwner;

    private User $teacherPeer;

    private User $teacherUnassigned;

    private User $studentOwner;

    private TeacherLeaveApplication $leaveA;

    private TeacherLeaveApplication $leaveB;

    private Assignment $assignmentA;

    private Assignment $assignmentB;

    private StudentAssignment $submissionA;

    private StudentAssignment $submissionB;

    private Approval $leaveApprovalA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        require_once __DIR__.'/../Support/activity_stub.php';

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
            'name' => 'Leave School A',
            'slug' => 'leave-school-a',
            'email' => 'leave-a@test.sch.ug',
            'phone' => '+256700000411',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolB = DB::table('schools')->insertGetId([
            'name' => 'Leave School B',
            'slug' => 'leave-school-b',
            'email' => 'leave-b@test.sch.ug',
            'phone' => '+256700000422',
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

        $standardA = Standard::create(['school_id' => $this->schoolA, 'name' => 'P1', 'order' => 1, 'status' => 1]);
        $sectionA = Section::create(['school_id' => $this->schoolA, 'name' => 'A', 'status' => 1]);
        $linkA = StandardLink::create([
            'school_id' => $this->schoolA,
            'academic_year_id' => $this->academicYearA,
            'standard_id' => $standardA->id,
            'section_id' => $sectionA->id,
            'no_of_students' => 10,
            'status' => 1,
        ]);
        $this->standardLinkA = $linkA->id;

        $standardB = Standard::create(['school_id' => $this->schoolB, 'name' => 'P1', 'order' => 1, 'status' => 1]);
        $sectionB = Section::create(['school_id' => $this->schoolB, 'name' => 'A', 'status' => 1]);
        $linkB = StandardLink::create([
            'school_id' => $this->schoolB,
            'academic_year_id' => $this->academicYearB,
            'standard_id' => $standardB->id,
            'section_id' => $sectionB->id,
            'no_of_students' => 10,
            'status' => 1,
        ]);

        $subjectA = Subject::create([
            'school_id' => $this->schoolA,
            'academic_year_id' => $this->academicYearA,
            'standard_id' => $standardA->id,
            'section_id' => $sectionA->id,
            'name' => 'Math',
            'type' => 'core',
            'status' => 1,
        ]);
        $this->subjectA = $subjectA->id;

        $subjectB = Subject::create([
            'school_id' => $this->schoolB,
            'academic_year_id' => $this->academicYearB,
            'standard_id' => $standardB->id,
            'section_id' => $sectionB->id,
            'name' => 'Math',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->siteAdmin = $this->makeUser(1, $this->schoolA, 'siteadmin.leave@test.sch.ug', 'Site Admin');
        $this->adminA = $this->makeUser(3, $this->schoolA, 'admin.a.leave@test.sch.ug', 'Admin A');
        $this->adminB = $this->makeUser(3, $this->schoolB, 'admin.b.leave@test.sch.ug', 'Admin B');
        $this->teacherOwner = $this->makeUser(5, $this->schoolA, 'teacher.owner.leave@test.sch.ug', 'Teacher Owner');
        $this->teacherPeer = $this->makeUser(5, $this->schoolA, 'teacher.peer.leave@test.sch.ug', 'Teacher Peer');
        $this->teacherUnassigned = $this->makeUser(5, $this->schoolA, 'teacher.unassigned.leave@test.sch.ug', 'Teacher Unassigned');
        $this->studentOwner = $this->makeUser(6, $this->schoolA, 'student.owner.leave@test.sch.ug', 'Student Owner');

        Teacherlink::create([
            'school_id' => $this->schoolA,
            'academic_year_id' => $this->academicYearA,
            'standardLink_id' => $this->standardLinkA,
            'subject_id' => $this->subjectA,
            'teacher_id' => $this->teacherOwner->id,
        ]);

        $this->leaveA = TeacherLeaveApplication::create([
            'school_id' => $this->schoolA,
            'academic_year_id' => $this->academicYearA,
            'user_id' => $this->teacherOwner->id,
            'from_date' => '2026-08-10 08:00:00',
            'to_date' => '2026-08-10 17:00:00',
            'remarks' => 'Personal',
            'status' => 'pending',
        ]);

        $peerB = $this->makeUser(5, $this->schoolB, 'teacher.b.leave@test.sch.ug', 'Teacher B');
        $this->leaveB = TeacherLeaveApplication::create([
            'school_id' => $this->schoolB,
            'academic_year_id' => $this->academicYearB,
            'user_id' => $peerB->id,
            'from_date' => '2026-08-11 08:00:00',
            'to_date' => '2026-08-11 17:00:00',
            'remarks' => 'Travel',
            'status' => 'pending',
        ]);

        $this->leaveApprovalA = Approval::create([
            'approvable_type' => TeacherLeaveApplication::class,
            'approvable_id' => $this->leaveA->id,
            'state' => Pending::class,
            'requested_by' => $this->teacherOwner->id,
        ]);

        $this->assignmentA = Assignment::create([
            'school_id' => $this->schoolA,
            'academic_year_id' => $this->academicYearA,
            'standardLink_id' => $this->standardLinkA,
            'subject_id' => $this->subjectA,
            'teacher_id' => $this->teacherOwner->id,
            'title' => 'School A assignment',
            'description' => 'Desc A',
            'marks' => 10,
            'assigned_date' => '2026-08-01',
            'submission_date' => '2026-08-15',
            'status' => 'ongoing',
        ]);

        $this->assignmentB = Assignment::create([
            'school_id' => $this->schoolB,
            'academic_year_id' => $this->academicYearB,
            'standardLink_id' => $linkB->id,
            'subject_id' => $subjectB->id,
            'teacher_id' => $peerB->id,
            'title' => 'School B assignment',
            'description' => 'Desc B',
            'marks' => 10,
            'assigned_date' => '2026-08-01',
            'submission_date' => '2026-08-15',
            'status' => 'ongoing',
        ]);

        $this->submissionA = StudentAssignment::create([
            'assignment_id' => $this->assignmentA->id,
            'user_id' => $this->studentOwner->id,
            'assignment_file' => [1 => 'uploads/a.pdf'],
            'submitted_on' => '2026-08-02',
            'status' => 'submitted',
        ]);

        $studentB = $this->makeUser(6, $this->schoolB, 'student.b.leave@test.sch.ug', 'Student B');
        $this->submissionB = StudentAssignment::create([
            'assignment_id' => $this->assignmentB->id,
            'user_id' => $studentB->id,
            'assignment_file' => [1 => 'uploads/b.pdf'],
            'submitted_on' => '2026-08-02',
            'status' => 'submitted',
        ]);
    }

    public function test_teacher_leave_owner_only_ug5(): void
    {
        $this->actingAs($this->teacherOwner);
        $this->assertTrue(Gate::allows('teacher-leave', $this->leaveA));
        $this->assertTrue(Gate::denies('teacher-leave', $this->leaveB));

        $this->actingAs($this->teacherPeer);
        $this->assertTrue(Gate::denies('teacher-leave', $this->leaveA));

        $this->actingAs($this->adminA);
        $this->assertTrue(Gate::denies('teacher-leave', $this->leaveA));

        $this->actingAs($this->siteAdmin);
        $this->assertTrue(Gate::denies('teacher-leave', $this->leaveA));
    }

    public function test_teacher_leave_manage_ug1_and_ug3_school_scoped(): void
    {
        $this->actingAs($this->adminA);
        $this->assertTrue(Gate::allows('teacher-leave-manage', $this->leaveA));
        $this->assertTrue(Gate::denies('teacher-leave-manage', $this->leaveB));

        $this->actingAs($this->adminB);
        $this->assertTrue(Gate::allows('teacher-leave-manage', $this->leaveB));
        $this->assertTrue(Gate::denies('teacher-leave-manage', $this->leaveA));

        $this->actingAs($this->siteAdmin);
        $this->assertTrue(Gate::allows('teacher-leave-manage', $this->leaveA));
        $this->assertTrue(Gate::allows('teacher-leave-manage', $this->leaveB));

        $this->actingAs($this->teacherOwner);
        $this->assertTrue(Gate::denies('teacher-leave-manage', $this->leaveA));
    }

    public function test_peer_teacher_cannot_show_or_destroy_leave(): void
    {
        $this->actingAs($this->teacherPeer);

        $this->get('/teacher/leave/edit/list/'.$this->leaveA->id)
            ->assertForbidden();

        $this->get('/teacher/leave/delete/'.$this->leaveA->id)
            ->assertForbidden();

        $this->assertDatabaseHas('teacher_leave_applications', [
            'id' => $this->leaveA->id,
            'status' => 'pending',
            'deleted_at' => null,
        ]);
    }

    public function test_owner_teacher_can_show_leave(): void
    {
        $this->actingAs($this->teacherOwner);

        $this->get('/teacher/leave/edit/list/'.$this->leaveA->id)
            ->assertOk();
    }

    public function test_ug3_cannot_approve_other_school_leave_approval(): void
    {
        $this->actingAs($this->adminB);

        $this->post('/admin/approvals/'.$this->leaveApprovalA->id.'/approve', [
            'comments' => 'Cross school',
        ])->assertForbidden();

        $this->leaveApprovalA->refresh();
        $this->assertSame(Pending::class, $this->leaveApprovalA->state::class);
    }

    public function test_ug3_can_approve_own_school_leave_approval(): void
    {
        $this->actingAs($this->adminA);

        $this->from('/admin/approvals')
            ->post('/admin/approvals/'.$this->leaveApprovalA->id.'/approve', [
                'comments' => 'Approved',
            ])
            ->assertRedirect();

        $this->leaveApprovalA->refresh();
        $this->assertNotSame(Pending::class, $this->leaveApprovalA->state::class);
    }

    public function test_student_assignment_review_ug3_school_scoped(): void
    {
        $this->actingAs($this->adminA);
        $this->assertTrue(Gate::allows('studentAssignment-review', $this->submissionA));
        $this->assertTrue(Gate::denies('studentAssignment-review', $this->submissionB));
        $this->assertTrue(Gate::allows('studentAssignment-review', $this->assignmentA));
        $this->assertTrue(Gate::denies('studentAssignment-review', $this->assignmentB));
    }

    public function test_ug5_review_allows_assigned_and_denies_unassigned(): void
    {
        $this->actingAs($this->teacherOwner);
        $this->assertTrue(Gate::allows('studentAssignment-review', $this->submissionA));
        $this->assertTrue(Gate::allows('studentAssignment-review', $this->assignmentA));

        $this->actingAs($this->teacherUnassigned);
        $this->assertTrue(Gate::denies('studentAssignment-review', $this->submissionA));
        $this->assertTrue(Gate::denies('studentAssignment-review', $this->assignmentA));
    }

    public function test_ug5_review_denies_other_school(): void
    {
        $this->actingAs($this->teacherOwner);
        $this->assertTrue(Gate::denies('studentAssignment-review', $this->submissionB));
        $this->assertTrue(Gate::denies('studentAssignment-review', $this->assignmentB));
    }

    public function test_class_teacher_can_review_assignment(): void
    {
        $link = StandardLink::find($this->standardLinkA);
        $link->class_teacher_id = $this->teacherPeer->id;
        $link->save();

        $this->assignmentA->unsetRelation('standardLink');

        $this->actingAs($this->teacherPeer);
        $this->assertTrue(Gate::allows('studentAssignment-review', $this->assignmentA));
        $this->assertTrue(Gate::allows('studentAssignment-review', $this->submissionA));
    }

    public function test_ug5_unassigned_cannot_mark_student_assignment(): void
    {
        $this->actingAs($this->teacherUnassigned);

        $this->post('/teacher/assignment/addMarks/'.$this->submissionA->id, [
            'assignment_id' => $this->assignmentA->id,
            'obtained_marks' => 8,
            'comments' => 'Good work',
        ])->assertForbidden();

        $this->assertDatabaseHas('student_assignments', [
            'id' => $this->submissionA->id,
            'status' => 'submitted',
        ]);
    }

    public function test_ug5_assigned_can_mark_student_assignment(): void
    {
        $this->actingAs($this->teacherOwner);

        $this->post('/teacher/assignment/addMarks/'.$this->submissionA->id, [
            'assignment_id' => $this->assignmentA->id,
            'obtained_marks' => 8,
            'comments' => 'Good work',
        ])->assertOk();

        $this->assertDatabaseHas('student_assignments', [
            'id' => $this->submissionA->id,
            'status' => 'completed',
            'obtained_marks' => 8,
        ]);
    }

    public function test_studentassignment_owner_gate_untouched(): void
    {
        $this->actingAs($this->studentOwner);
        $this->assertTrue(Gate::allows('studentassignment', $this->submissionA));
        $this->assertTrue(Gate::denies('studentAssignment-review', $this->submissionA));

        $this->actingAs($this->teacherOwner);
        $this->assertTrue(Gate::denies('studentassignment', $this->submissionA));
        $this->assertTrue(Gate::allows('studentAssignment-review', $this->submissionA));

        $this->actingAs($this->teacherPeer);
        $this->assertTrue(Gate::denies('studentassignment', $this->submissionA));
    }

    public function test_assignment_school_gate_untouched(): void
    {
        $this->actingAs($this->teacherPeer);
        $this->assertTrue(Gate::allows('assignment', $this->assignmentA));
        $this->assertTrue(Gate::denies('assignment', $this->assignmentB));
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
}
