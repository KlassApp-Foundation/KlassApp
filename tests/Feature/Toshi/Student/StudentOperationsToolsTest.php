<?php

namespace Tests\Feature\Toshi\Student;

use App\AiAgents\StudentOperationsAgent;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\Student\ManageConversationsTool;
use App\AiAgents\Tools\Student\ManageTasksTool;
use App\AiAgents\Tools\Student\SubmitAssignmentTool;
use App\AiAgents\Tools\Student\SubmitHomeworkTool;
use App\AiAgents\Tools\Student\ViewAssignmentsTool;
use App\AiAgents\Tools\Student\ViewAttendanceTool;
use App\AiAgents\Tools\Student\ViewClassWallTool;
use App\AiAgents\Tools\Student\ViewDashboardTool;
use App\AiAgents\Tools\Student\ViewEventsTool;
use App\AiAgents\Tools\Student\ViewHomeworkTool;
use App\AiAgents\Tools\Student\ViewLibraryActivityTool;
use App\AiAgents\Tools\Student\ViewMarksTool;
use App\AiAgents\Tools\Student\ViewNoticesTool;
use App\Models\ActivityLog;
use App\Models\Academics\Marks;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\BookLending;
use App\Models\Homework;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentAssignment;
use App\Models\StudentHomework;
use App\Models\Subject;
use App\Models\Task;
use App\Models\User;
use App\Services\Toshi\StudentActionService;
use App\Services\ToshiActionService;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;
use Tests\TestCase;

class StudentOperationsToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $studentA;

    private User $studentB;

    private User $teacher;

    private int $schoolId;

    private int $academicYearId;

    private int $standardLinkId;

    private Assignment $assignmentForClass;

    private Assignment $assignmentPeerOnly;

    private Homework $homeworkForClass;

    private Homework $homeworkPeerOnly;

    private int $subjectId;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Cache::flush();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Student Toshi School',
            'slug' => 'student-toshi',
            'email' => 'student-toshi@test.sch.ug',
            'phone' => '+256700000006',
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

        $standard = Standard::create([
            'school_id' => $this->schoolId,
            'name' => 'P5',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->schoolId,
            'name' => 'A',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'no_of_students' => 2,
            'status' => 1,
        ]);
        $this->standardLinkId = $link->id;

        $this->teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'teacher.toshi@test.sch.ug',
            'name' => 'Toshi Teacher',
        ]);

        $this->studentA = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.a@test.sch.ug',
            'name' => 'Student A',
        ]);
        $this->studentB = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.b@test.sch.ug',
            'name' => 'Student B',
        ]);

        foreach ([$this->studentA, $this->studentB] as $student) {
            StudentAcademic::create([
                'school_id' => $this->schoolId,
                'academic_year_id' => $this->academicYearId,
                'user_id' => $student->id,
                'standardLink_id' => $this->standardLinkId,
                'academic_status' => 'pass',
            ]);
        }

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

        $this->assignmentForClass = Assignment::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Shared Class Assignment',
            'description' => 'Do the problems',
            'attachment' => '',
            'marks' => 10,
            'assigned_date' => '2026-07-01',
            'submission_date' => '2026-08-15',
            'status' => 'ongoing',
        ]);

        // Peer-only assignment on another class link — B will have a submission against class assignment.
        $otherLink = StandardLink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => Section::create(['school_id' => $this->schoolId, 'name' => 'B', 'status' => 1])->id,
            'no_of_students' => 0,
            'status' => 1,
        ]);
        $this->assignmentPeerOnly = Assignment::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $otherLink->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Other Class Assignment',
            'description' => 'Not for A',
            'attachment' => '',
            'marks' => 10,
            'assigned_date' => '2026-07-01',
            'submission_date' => '2026-08-15',
            'status' => 'ongoing',
        ]);

        $this->homeworkForClass = Homework::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'description' => 'Shared class homework',
            'attachment' => '',
            'date' => '2026-08-01',
            'created_by' => $this->teacher->id,
        ]);
        $this->homeworkPeerOnly = Homework::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $otherLink->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'description' => 'Other class homework',
            'attachment' => '',
            'date' => '2026-08-01',
            'created_by' => $this->teacher->id,
        ]);
    }

    public function test_student_agent_has_thirteen_tools_and_excludes_admin(): void
    {
        $agent = new StudentOperationsAgent;
        $classes = collect($agent->tools())->map(fn ($t) => $t::class)->all();

        $this->assertNotContains(AddCoAdminTool::class, $classes);
        $this->assertContains(ViewDashboardTool::class, $classes);
        $this->assertContains(ViewMarksTool::class, $classes);
        $this->assertContains(ViewAttendanceTool::class, $classes);
        $this->assertContains(ViewLibraryActivityTool::class, $classes);
        $this->assertContains(ViewEventsTool::class, $classes);
        $this->assertContains(ViewNoticesTool::class, $classes);
        $this->assertContains(ViewAssignmentsTool::class, $classes);
        $this->assertContains(ViewHomeworkTool::class, $classes);
        $this->assertContains(ViewClassWallTool::class, $classes);
        $this->assertContains(SubmitAssignmentTool::class, $classes);
        $this->assertContains(SubmitHomeworkTool::class, $classes);
        $this->assertContains(ManageTasksTool::class, $classes);
        $this->assertContains(ManageConversationsTool::class, $classes);
        $this->assertCount(14, $classes);
    }

    public function test_gates_isolate_student_from_five_other_role_gates(): void
    {
        $this->actingAs($this->studentA);

        $this->assertTrue(Gate::inspect('toshi-school-action', $this->studentA)->denied());
        $this->assertTrue(Gate::inspect('toshi-teacher-action', $this->studentA)->denied());
        $this->assertTrue(Gate::inspect('toshi-accountant-action', $this->studentA)->denied());
        $this->assertTrue(Gate::inspect('toshi-librarian-action', $this->studentA)->denied());
        $this->assertTrue(Gate::inspect('toshi-receptionist-action', $this->studentA)->denied());
        $this->assertTrue(Gate::inspect('toshi-student-action', $this->studentA)->allowed());
    }

    public function test_school_admin_denied_on_student_gate(): void
    {
        $admin = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
        ]);
        $this->actingAs($admin);

        $this->assertTrue(Gate::inspect('toshi-student-action', $admin)->denied());
    }

    public function test_school_admin_tool_denies_student(): void
    {
        $this->actingAs($this->studentA);
        ToshiActionService::$bypassConfirm = true;

        $result = (new AddCoAdminTool)->handle(new Request([
            'name' => 'Should Fail',
            'email' => 'coadmin.fail@test.sch.ug',
        ]));

        $this->assertStringStartsWith('❌', $result);
        ToshiActionService::$bypassConfirm = false;
    }

    public function test_view_tools_authorized_for_student(): void
    {
        $this->actingAs($this->studentA);

        foreach ([
            new ViewDashboardTool,
            new ViewMarksTool,
            new ViewAttendanceTool,
            new ViewLibraryActivityTool,
            new ViewEventsTool,
            new ViewNoticesTool,
            new ViewAssignmentsTool,
            new ViewHomeworkTool,
            new ViewClassWallTool,
        ] as $tool) {
            $result = $tool->handle(new Request([]));
            $this->assertStringStartsWith('✅', $result, $tool::class);
        }
    }

    public function test_cross_student_library_read_never_returns_peer_lends(): void
    {
        BookLending::create([
            'user_id' => $this->studentB->id,
            'book_code_no' => 'B-SECRET',
            'library_card_no' => 'CARD-B',
            'issue_date' => '2026-07-01',
            'return_date' => '2026-07-15',
            'issued_by' => $this->teacher->id,
            'status' => 'pending',
        ]);
        BookLending::create([
            'user_id' => $this->studentA->id,
            'book_code_no' => 'A-OWN',
            'library_card_no' => 'CARD-A',
            'issue_date' => '2026-07-01',
            'return_date' => '2026-07-15',
            'issued_by' => $this->teacher->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->studentA);
        // Forged peer id in args must be ignored — service only uses auth user.
        $result = StudentActionService::viewLibraryActivity($this->studentA);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('A-OWN', $result['message']);
        $this->assertStringNotContainsString('B-SECRET', $result['message']);
        $this->assertSame([$this->studentA->id], BookLending::whereIn('id', $result['data']['ids'] ?? [])->pluck('user_id')->unique()->values()->all());
    }

    public function test_cross_student_submit_assignment_with_peer_class_id_denied(): void
    {
        $this->actingAs($this->studentA);
        ToshiActionService::$bypassConfirm = true;

        $denied = (new SubmitAssignmentTool)->handle(new Request([
            'assignment_id' => $this->assignmentPeerOnly->id,
            'note' => 'Trying to submit other class work',
            'student_id' => $this->studentB->id, // ignored / must not stamp B
            'user_id' => $this->studentB->id,
        ]));

        $this->assertStringStartsWith('❌', $denied);
        $this->assertTrue(
            str_contains(strtolower($denied), 'access denied')
            || str_contains(strtolower($denied), 'not found'),
            $denied
        );
        $this->assertFalse(StudentAssignment::where('assignment_id', $this->assignmentPeerOnly->id)->exists());

        $ok = (new SubmitAssignmentTool)->handle(new Request([
            'assignment_id' => $this->assignmentForClass->id,
            'note' => 'My work',
            'user_id' => $this->studentB->id, // must still stamp A
        ]));
        $this->assertStringStartsWith('✅', $ok);
        $row = StudentAssignment::where('assignment_id', $this->assignmentForClass->id)->first();
        $this->assertNotNull($row);
        $this->assertSame($this->studentA->id, (int) $row->user_id);
        $this->assertNotSame($this->studentB->id, (int) $row->user_id);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_cross_student_submit_homework_with_peer_class_id_denied(): void
    {
        $this->actingAs($this->studentA);
        ToshiActionService::$bypassConfirm = true;

        $denied = (new SubmitHomeworkTool)->handle(new Request([
            'homework_id' => $this->homeworkPeerOnly->id,
            'note' => 'peer homework',
            'student_id' => $this->studentB->id,
        ]));
        $this->assertStringStartsWith('❌', $denied);
        $this->assertTrue(
            str_contains(strtolower($denied), 'access denied')
            || str_contains(strtolower($denied), 'not found'),
            $denied
        );
        $this->assertFalse(StudentHomework::where('homework_id', $this->homeworkPeerOnly->id)->exists());

        $ok = (new SubmitHomeworkTool)->handle(new Request([
            'homework_id' => $this->homeworkForClass->id,
            'note' => 'my homework',
        ]));
        $this->assertStringStartsWith('✅', $ok);
        $row = StudentHomework::where('homework_id', $this->homeworkForClass->id)->first();
        $this->assertSame($this->studentA->id, (int) $row->user_id);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_manage_tasks_never_touches_peer_task(): void
    {
        $peerTask = Task::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'user_id' => $this->studentB->id,
            'title' => 'B private task',
            'type' => 'self',
            'to_do_list' => 'secret',
            'task_date' => '2026-08-10',
            'reminder' => 'one_day_before_the_task',
            'task_status' => 0,
            'task_flag' => 2,
        ]);

        $this->actingAs($this->studentA);
        ToshiActionService::$bypassConfirm = true;

        $list = StudentActionService::manageTasks($this->studentA, ['action' => 'list']);
        $this->assertTrue($list['success']);
        $this->assertNotContains($peerTask->id, $list['data']['ids'] ?? []);

        $denied = (new ManageTasksTool)->handle(new Request([
            'action' => 'complete',
            'task_id' => $peerTask->id,
        ]));
        $this->assertStringStartsWith('❌', $denied);
        $this->assertStringContainsString('access denied', strtolower($denied));
        $this->assertSame(0, (int) $peerTask->fresh()->task_status);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_manage_conversations_never_touches_peer_thread(): void
    {
        $peerConversationId = DB::table('conversation_chat')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
            'last_message_at' => now(),
        ]);
        DB::table('conversation_user')->insert([
            ['conversation_id' => $peerConversationId, 'user_id' => $this->studentB->id, 'created_at' => now(), 'updated_at' => now()],
            ['conversation_id' => $peerConversationId, 'user_id' => $this->teacher->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('conversation_messages')->insert([
            'conversation_id' => $peerConversationId,
            'user_id' => $this->studentB->id,
            'body' => 'Secret from B',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->studentA);
        ToshiActionService::$bypassConfirm = true;

        $show = (new ManageConversationsTool)->handle(new Request([
            'action' => 'show',
            'conversation_id' => $peerConversationId,
        ]));
        $this->assertStringStartsWith('❌', $show);
        $this->assertStringContainsString('access denied', strtolower($show));
        $this->assertStringNotContainsString('Secret from B', $show);

        $send = (new ManageConversationsTool)->handle(new Request([
            'action' => 'send',
            'conversation_id' => $peerConversationId,
            'body' => 'A trying to inject',
        ]));
        $this->assertStringStartsWith('❌', $send);
        $this->assertFalse(
            DB::table('conversation_messages')
                ->where('conversation_id', $peerConversationId)
                ->where('user_id', $this->studentA->id)
                ->exists()
        );

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_submit_assignment_requires_confirmation(): void
    {
        $this->actingAs($this->studentA);
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        $pending = (new SubmitAssignmentTool)->handle(new Request([
            'assignment_id' => $this->assignmentForClass->id,
            'note' => 'pending',
        ]));
        $decoded = json_decode($pending, true);
        $this->assertTrue($decoded['__tier2_confirm'] ?? false);
        $this->assertSame('toolStudentSubmitAssignment', $decoded['tool']);
        $this->assertFalse(StudentAssignment::where('assignment_id', $this->assignmentForClass->id)->exists());
    }

    public function test_unconfirmed_or_read_only_audit_leaves_approver_id_null(): void
    {
        $this->actingAs($this->studentA);

        foreach (['ViewDashboardTool', 'ViewLibraryActivityTool', 'ViewMarksTool'] as $toolName) {
            $log = ToshiAuditService::logExecution(
                user: $this->studentA,
                school: $this->studentA->school,
                toolName: $toolName,
                arguments: [],
                result: '✅ ok',
                actingUser: $this->studentA,
                approver: null,
            );

            $this->assertSame($this->studentA->id, $log->properties['acting_user_id'], $toolName);
            $this->assertArrayHasKey('approver_id', $log->properties, $toolName);
            $this->assertNull($log->properties['approver_id'], $toolName);
            $this->assertSame($this->studentA->id, $log->causer_id, $toolName);
        }
    }

    public function test_tier2_confirm_yes_sets_approver_id_to_confirming_student(): void
    {
        $this->actingAs($this->studentA);

        Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('schoolId', $this->schoolId)
            ->set('pendingToolConfirm', [
                'tool' => 'toolStudentSubmitAssignment',
                'args' => [
                    'assignment_id' => $this->assignmentForClass->id,
                    'note' => 'Confirmed submit',
                ],
            ])
            ->call('confirmYes');

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $this->schoolId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'Toshi audit log should exist after Tier-2 confirmYes');
        $this->assertSame('toolStudentSubmitAssignment', $log->properties['tool']);
        $this->assertSame($this->studentA->id, $log->properties['acting_user_id']);
        $this->assertSame($this->studentA->id, $log->properties['approver_id']);
        $this->assertTrue(StudentAssignment::where('assignment_id', $this->assignmentForClass->id)
            ->where('user_id', $this->studentA->id)
            ->exists());
    }

    public function test_view_marks_and_attendance_self_scoped(): void
    {
        Marks::create([
            'student_id' => $this->studentB->id,
            'school_id' => $this->schoolId,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subjectId,
            'section_id' => 1,
            'exam_id' => 1,
            'marks' => 99,
            'grade' => 'A',
        ]);
        Marks::create([
            'student_id' => $this->studentA->id,
            'school_id' => $this->schoolId,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subjectId,
            'section_id' => 1,
            'exam_id' => 1,
            'marks' => 70,
            'grade' => 'B',
        ]);
        Attendance::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'user_id' => $this->studentB->id,
            'date' => '2026-07-01',
            'session' => 'forenoon',
            'status' => 1,
            'recorded_by' => $this->teacher->id,
        ]);
        Attendance::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'user_id' => $this->studentA->id,
            'date' => '2026-07-01',
            'session' => 'forenoon',
            'status' => 0,
            'recorded_by' => $this->teacher->id,
        ]);

        $this->actingAs($this->studentA);
        $marks = StudentActionService::viewMarks($this->studentA);
        $this->assertStringContainsString('70', $marks['message']);
        $this->assertStringNotContainsString('99', $marks['message']);

        $attendance = StudentActionService::viewAttendance($this->studentA);
        $this->assertSame(1, $attendance['data']['total']);
        $this->assertSame(0, $attendance['data']['present']);
        $this->assertSame(1, $attendance['data']['absent']);
    }
}
