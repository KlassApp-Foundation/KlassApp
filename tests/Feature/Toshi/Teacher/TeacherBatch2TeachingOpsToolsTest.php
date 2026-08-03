<?php

namespace Tests\Feature\Toshi\Teacher;

use App\AiAgents\Skills\TeacherTeachingOpsSkill;
use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\RouteToTeacherTeachingOpsSkillTool;
use App\AiAgents\Tools\Teacher\CancelMyLeaveTool;
use App\AiAgents\Tools\Teacher\ListMyLeavesTool;
use App\AiAgents\Tools\Teacher\ListStudentAssignmentsTool;
use App\AiAgents\Tools\Teacher\ListStudentHomeworkTool;
use App\AiAgents\Tools\Teacher\MarkStudentAssignmentTool;
use App\AiAgents\Tools\Teacher\ShowMyLeaveTool;
use App\AiAgents\Tools\Teacher\ShowStudentAssignmentTool;
use App\AiAgents\Tools\Teacher\ShowStudentHomeworkTool;
use App\AiAgents\Tools\Teacher\UpdateStudentHomeworkTool;
use App\Models\Assignment;
use App\Models\Homework;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAssignment;
use App\Models\StudentHomework;
use App\Models\Subject;
use App\Models\TeacherLeaveApplication;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class TeacherBatch2TeachingOpsToolsTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private int $otherSchoolId;

    private int $academicYearId;

    private int $otherAcademicYearId;

    private int $standardLinkId;

    private int $subjectId;

    private User $teacher;

    private User $peerTeacher;

    private User $student;

    private User $schoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__.'/../../../Support/activity_stub.php';

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Teacher Batch2 School',
            'slug' => 'teacher-batch2',
            'email' => 'teacher-batch2@test.sch.ug',
            'phone' => '+256700000066',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->otherSchoolId = DB::table('schools')->insertGetId([
            'name' => 'Other Batch2 School',
            'slug' => 'other-batch2',
            'email' => 'other-batch2@test.sch.ug',
            'phone' => '+256700000077',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->otherAcademicYearId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->otherSchoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $standard = Standard::create(['school_id' => $this->schoolId, 'name' => 'P1', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $this->schoolId, 'name' => 'A', 'status' => 1]);
        $link = StandardLink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'no_of_students' => 10,
            'status' => 1,
        ]);
        $this->standardLinkId = $link->id;

        $subject = Subject::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Math',
            'type' => 'core',
            'status' => 1,
        ]);
        $this->subjectId = $subject->id;

        $this->teacher = $this->makeUser(5, $this->schoolId, 'teacher.batch2@test.sch.ug', 'Batch Teacher');
        $this->peerTeacher = $this->makeUser(5, $this->schoolId, 'peer.batch2@test.sch.ug', 'Peer Teacher');
        $this->student = $this->makeUser(6, $this->schoolId, 'student.batch2@test.sch.ug', 'Batch Student');
        $this->schoolAdmin = $this->makeUser(3, $this->schoolId, 'admin.batch2@test.sch.ug', 'Batch Admin');

        Teacherlink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_happy_path_homework_assignment_and_own_leave(): void
    {
        $this->actingAs($this->teacher);
        ToshiActionService::$bypassConfirm = true;

        $homework = Homework::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id,
            'description' => 'Batch2 homework',
            'date' => '2026-08-01',
            'created_by' => $this->teacher->id,
        ]);
        $submission = StudentHomework::create([
            'homework_id' => $homework->id,
            'user_id' => $this->student->id,
            'attachment' => [1 => 'uploads/hw.pdf'],
            'submitted_on' => '2026-08-02',
            'status' => 'unchecked',
        ]);

        $listHw = (new ListStudentHomeworkTool)->handle(new Request(['homework_id' => $homework->id]));
        $this->assertStringContainsString('#'.$submission->id, $listHw);

        $showHw = (new ShowStudentHomeworkTool)->handle(new Request(['student_homework_id' => $submission->id]));
        $this->assertStringContainsString('student_id: '.$this->student->id, $showHw);

        $check = (new UpdateStudentHomeworkTool)->handle(new Request([
            'student_homework_id' => $submission->id,
            'comments' => 'Nice',
        ]));
        $this->assertStringStartsWith('✅', $check);
        $this->assertSame('checked', $submission->fresh()->status);

        $assignment = Assignment::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id,
            'title' => 'Batch2 assignment',
            'description' => 'Desc',
            'marks' => 10,
            'assigned_date' => '2026-08-01',
            'submission_date' => '2026-08-15',
            'status' => 'ongoing',
        ]);
        $sa = StudentAssignment::create([
            'assignment_id' => $assignment->id,
            'user_id' => $this->student->id,
            'assignment_file' => [1 => 'uploads/a.pdf'],
            'submitted_on' => '2026-08-02',
            'status' => 'submitted',
        ]);

        $listSa = (new ListStudentAssignmentsTool)->handle(new Request([
            'assignment_id' => $assignment->id,
            'status' => 'submitted',
        ]));
        $this->assertStringContainsString('#'.$sa->id, $listSa);

        $showSa = (new ShowStudentAssignmentTool)->handle(new Request([
            'student_assignment_id' => $sa->id,
        ]));
        $this->assertStringContainsString('student_id: '.$this->student->id, $showSa);

        $mark = (new MarkStudentAssignmentTool)->handle(new Request([
            'student_assignment_id' => $sa->id,
            'obtained_marks' => 8,
            'comments' => 'Good',
        ]));
        $this->assertStringStartsWith('✅', $mark);
        $this->assertSame('completed', $sa->fresh()->status);

        $leave = TeacherLeaveApplication::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'user_id' => $this->teacher->id,
            'from_date' => '2026-08-20 08:00:00',
            'to_date' => '2026-08-20 17:00:00',
            'remarks' => 'Personal',
            'status' => 'pending',
        ]);

        $listLeave = (new ListMyLeavesTool)->handle(new Request([]));
        $this->assertStringContainsString('#'.$leave->id, $listLeave);

        $showLeave = (new ShowMyLeaveTool)->handle(new Request(['leave_id' => $leave->id]));
        $this->assertStringContainsString('Leave #'.$leave->id, $showLeave);

        $cancel = (new CancelMyLeaveTool)->handle(new Request(['leave_id' => $leave->id]));
        $this->assertStringStartsWith('✅', $cancel);
        $this->assertSoftDeleted('teacher_leave_applications', ['id' => $leave->id]);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_validation_rejects_over_marks_and_missing_leave(): void
    {
        $this->actingAs($this->teacher);
        ToshiActionService::$bypassConfirm = true;

        $assignment = Assignment::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id,
            'title' => 'Marks cap',
            'description' => 'Desc',
            'marks' => 10,
            'assigned_date' => '2026-08-01',
            'submission_date' => '2026-08-15',
            'status' => 'ongoing',
        ]);
        $sa = StudentAssignment::create([
            'assignment_id' => $assignment->id,
            'user_id' => $this->student->id,
            'assignment_file' => [1 => 'uploads/a.pdf'],
            'submitted_on' => '2026-08-02',
            'status' => 'submitted',
        ]);

        $over = (new MarkStudentAssignmentTool)->handle(new Request([
            'student_assignment_id' => $sa->id,
            'obtained_marks' => 99,
        ]));
        $this->assertStringStartsWith('❌', $over);
        $this->assertSame('submitted', $sa->fresh()->status);

        $missing = (new ShowMyLeaveTool)->handle(new Request(['leave_id' => 999999]));
        $this->assertStringStartsWith('❌', $missing);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_peer_teacher_denied_by_gates_and_tools(): void
    {
        $homework = Homework::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id,
            'description' => 'Peer deny hw',
            'date' => '2026-08-01',
            'created_by' => $this->teacher->id,
        ]);
        $hwSubmission = StudentHomework::create([
            'homework_id' => $homework->id,
            'user_id' => $this->student->id,
            'attachment' => [1 => 'uploads/hw.pdf'],
            'submitted_on' => '2026-08-02',
            'status' => 'unchecked',
        ]);

        $assignment = Assignment::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id,
            'title' => 'Peer deny asg',
            'description' => 'Desc',
            'marks' => 10,
            'assigned_date' => '2026-08-01',
            'submission_date' => '2026-08-15',
            'status' => 'ongoing',
        ]);
        $sa = StudentAssignment::create([
            'assignment_id' => $assignment->id,
            'user_id' => $this->student->id,
            'assignment_file' => [1 => 'uploads/a.pdf'],
            'submitted_on' => '2026-08-02',
            'status' => 'submitted',
        ]);

        $leave = TeacherLeaveApplication::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'user_id' => $this->teacher->id,
            'from_date' => '2026-08-21 08:00:00',
            'to_date' => '2026-08-21 17:00:00',
            'status' => 'pending',
        ]);

        $this->actingAs($this->peerTeacher);
        $this->assertTrue(Gate::denies('studentHomework-review', $homework));
        $this->assertTrue(Gate::denies('studentAssignment-review', $assignment));
        $this->assertTrue(Gate::denies('teacher-leave', $leave));

        ToshiActionService::$bypassConfirm = true;
        $this->assertStringStartsWith('❌', (new ListStudentHomeworkTool)->handle(new Request(['homework_id' => $homework->id])));
        $this->assertStringStartsWith('❌', (new UpdateStudentHomeworkTool)->handle(new Request([
            'student_homework_id' => $hwSubmission->id,
            'comments' => 'Nope',
        ])));
        $this->assertStringStartsWith('❌', (new MarkStudentAssignmentTool)->handle(new Request([
            'student_assignment_id' => $sa->id,
            'obtained_marks' => 5,
        ])));
        $this->assertStringStartsWith('❌', (new ShowMyLeaveTool)->handle(new Request(['leave_id' => $leave->id])));
        $this->assertStringStartsWith('❌', (new CancelMyLeaveTool)->handle(new Request(['leave_id' => $leave->id])));
        ToshiActionService::$bypassConfirm = false;

        $this->assertSame('unchecked', $hwSubmission->fresh()->status);
        $this->assertSame('submitted', $sa->fresh()->status);
        $this->assertDatabaseHas('teacher_leave_applications', [
            'id' => $leave->id,
            'status' => 'pending',
            'deleted_at' => null,
        ]);
    }

    public function test_school_admin_cannot_call_teacher_batch2_tools(): void
    {
        $this->actingAs($this->schoolAdmin);

        $result = (new ListMyLeavesTool)->handle(new Request([]));
        $this->assertStringStartsWith('❌', $result);

        $router = (new RouteToTeacherTeachingOpsSkillTool)->handle(new Request([
            'query' => 'list my leave',
        ]));
        $this->assertStringStartsWith('❌', $router);
    }

    public function test_agent_registers_router_and_skill_has_nine_tools(): void
    {
        $agentTools = collect((new TeacherOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertContains(RouteToTeacherTeachingOpsSkillTool::class, $agentTools);
        $this->assertNotContains(AddCoAdminTool::class, $agentTools);
        $this->assertCount(13, $agentTools);

        $skillTools = collect((new TeacherTeachingOpsSkill)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertCount(9, $skillTools);
        $this->assertContains(ListStudentHomeworkTool::class, $skillTools);
        $this->assertContains(MarkStudentAssignmentTool::class, $skillTools);
        $this->assertContains(CancelMyLeaveTool::class, $skillTools);
    }

    public function test_studentassignment_owner_gate_still_not_widened(): void
    {
        $assignment = Assignment::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id,
            'title' => 'Owner gate',
            'description' => 'Desc',
            'marks' => 10,
            'assigned_date' => '2026-08-01',
            'submission_date' => '2026-08-15',
            'status' => 'ongoing',
        ]);
        $sa = StudentAssignment::create([
            'assignment_id' => $assignment->id,
            'user_id' => $this->student->id,
            'assignment_file' => [1 => 'uploads/a.pdf'],
            'submitted_on' => '2026-08-02',
            'status' => 'submitted',
        ]);

        $this->actingAs($this->student);
        $this->assertTrue(Gate::allows('studentassignment', $sa));
        $this->assertTrue(Gate::denies('studentAssignment-review', $sa));

        $this->actingAs($this->teacher);
        $this->assertTrue(Gate::denies('studentassignment', $sa));
        $this->assertTrue(Gate::allows('studentAssignment-review', $sa));
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
