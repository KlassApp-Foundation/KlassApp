<?php

namespace Tests\Feature\Toshi\Teacher;

use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\Teacher\ApplyLeaveTool;
use App\AiAgents\Tools\Teacher\CreateTaskTool;
use App\AiAgents\Tools\Teacher\MarkAttendanceTool;
use App\AiAgents\Tools\Teacher\ViewEventsTool;
use App\AiAgents\Tools\Teacher\ViewNoticeboardTool;
use App\AiAgents\Tools\Teacher\ViewStudentsTool;
use App\AiAgents\Tools\Teacher\ViewTimetableTool;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Task;
use App\Models\TeacherLeaveApplication;
use App\Models\User;
use App\Services\ToshiActionService;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherOperationsToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Teacher Toshi School',
            'slug' => 'teacher-toshi',
            'email' => 'teacher-toshi@test.sch.ug',
            'phone' => '+256700000055',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ayId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'teacher.toshi@test.sch.ug',
            'name' => 'Toshi Teacher',
        ]);

        $this->student = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.toshi@test.sch.ug',
            'name' => 'Toshi Student',
        ]);

        unset($ayId);
    }

    public function test_teacher_agent_tools_exclude_school_admin_tools(): void
    {
        $agent = new TeacherOperationsAgent;
        $classes = collect($agent->tools())->map(fn ($t) => $t::class)->all();

        $this->assertNotContains(AddCoAdminTool::class, $classes);
        $this->assertContains(MarkAttendanceTool::class, $classes);
        $this->assertCount(12, $classes);
    }

    public function test_toshi_school_action_gate_denies_teacher(): void
    {
        $this->actingAs($this->teacher);

        $this->assertTrue(Gate::inspect('toshi-school-action', $this->teacher)->denied());
        $this->assertTrue(Gate::inspect('toshi-teacher-action', $this->teacher)->allowed());
    }

    public function test_school_admin_tool_denies_teacher_via_authorize(): void
    {
        $this->actingAs($this->teacher);
        ToshiActionService::$bypassConfirm = true;

        $result = (new AddCoAdminTool)->handle(new Request([
            'name' => 'Should Fail',
            'email' => 'coadmin.fail@test.sch.ug',
        ]));

        $this->assertStringStartsWith('❌', $result);
        ToshiActionService::$bypassConfirm = false;
    }

    public function test_mark_attendance_happy_path_and_validation(): void
    {
        $this->actingAs($this->teacher);
        ToshiActionService::$bypassConfirm = true;

        $ok = (new MarkAttendanceTool)->handle(new Request([
            'student' => $this->student->email,
            'date' => '2026-08-01',
            'status' => 'present',
        ]));
        $this->assertStringStartsWith('✅', $ok);
        $this->assertTrue(Attendance::where('user_id', $this->student->id)->whereDate('date', '2026-08-01')->exists());

        $bad = (new MarkAttendanceTool)->handle(new Request([
            'student' => $this->student->email,
            'date' => 'not-a-date',
            'status' => 'present',
        ]));
        $this->assertStringStartsWith('❌', $bad);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_mark_attendance_requires_confirmation_without_bypass(): void
    {
        $this->actingAs($this->teacher);
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        $result = (new MarkAttendanceTool)->handle(new Request([
            'student' => $this->student->email,
            'date' => '2026-08-02',
            'status' => 'absent',
        ]));

        $decoded = json_decode($result, true);
        $this->assertTrue($decoded['__tier2_confirm'] ?? false);
        $this->assertSame('toolTeacherMarkAttendance', $decoded['tool']);
        $this->assertFalse(Attendance::where('user_id', $this->student->id)->whereDate('date', '2026-08-02')->exists());
    }

    public function test_create_task_happy_path(): void
    {
        $this->actingAs($this->teacher);
        ToshiActionService::$bypassConfirm = true;

        $result = (new CreateTaskTool)->handle(new Request([
            'title' => 'Prepare lesson',
            'task_date' => '2026-08-10',
        ]));

        $this->assertStringStartsWith('✅', $result);
        $this->assertTrue(Task::where('user_id', $this->teacher->id)->where('title', 'Prepare lesson')->exists());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_apply_leave_happy_path(): void
    {
        $this->actingAs($this->teacher);
        ToshiActionService::$bypassConfirm = true;

        // Minimal leave type / reason rows
        $reasonId = DB::table('absent_reasons')->insertGetId([
            'title' => 'Personal',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $typeId = DB::table('leave_types')->insertGetId([
            'school_id' => $this->schoolId,
            'academic_year_id' => DB::table('academic_years')->where('school_id', $this->schoolId)->value('id'),
            'name' => 'Casual',
            'max_no_of_days' => '5',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = (new ApplyLeaveTool)->handle(new Request([
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-02',
            'reason_id' => $reasonId,
            'leave_type_id' => $typeId,
        ]));

        $this->assertStringStartsWith('✅', $result);
        $this->assertTrue(TeacherLeaveApplication::where('user_id', $this->teacher->id)->exists());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_view_tools_authorized_for_teacher(): void
    {
        $this->actingAs($this->teacher);

        foreach ([
            new ViewStudentsTool,
            new ViewTimetableTool,
            new ViewEventsTool,
            new ViewNoticeboardTool,
        ] as $tool) {
            $result = $tool->handle(new Request([]));
            $this->assertStringStartsWith('✅', $result, $tool::class);
        }
    }

    public function test_unconfirmed_or_read_only_audit_leaves_approver_id_null(): void
    {
        $this->actingAs($this->teacher);

        $log = ToshiAuditService::logExecution(
            user: $this->teacher,
            school: $this->teacher->school,
            toolName: 'ViewTimetableTool',
            arguments: [],
            result: '✅ ok',
            actingUser: $this->teacher,
            approver: null,
        );

        $this->assertSame($this->teacher->id, $log->properties['acting_user_id']);
        $this->assertNull($log->properties['approver_id']);
        $this->assertSame($this->teacher->id, $log->causer_id);
        $this->assertTrue(ActivityLog::where('log_name', 'toshi')->where('causer_id', $this->teacher->id)->exists());
    }

    public function test_tier2_confirm_yes_sets_approver_id_to_confirming_teacher(): void
    {
        $this->actingAs($this->teacher);

        Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('schoolId', $this->schoolId)
            ->set('pendingToolConfirm', [
                'tool' => 'toolTeacherMarkAttendance',
                'args' => [
                    'student' => $this->student->email,
                    'date' => '2026-08-03',
                    'status' => 'present',
                ],
            ])
            ->call('confirmYes');

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $this->schoolId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'Toshi audit log should exist after Tier-2 confirmYes');
        $this->assertSame('toolTeacherMarkAttendance', $log->properties['tool']);
        $this->assertSame($this->teacher->id, $log->properties['acting_user_id']);
        $this->assertSame($this->teacher->id, $log->properties['approver_id']);
        $this->assertTrue(Attendance::where('user_id', $this->student->id)->whereDate('date', '2026-08-03')->exists());
    }
}
