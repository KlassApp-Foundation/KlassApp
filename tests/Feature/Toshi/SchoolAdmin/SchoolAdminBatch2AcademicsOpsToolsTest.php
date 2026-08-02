<?php

namespace Tests\Feature\Toshi\SchoolAdmin;

use App\AiAgents\AccountantOperationsAgent;
use App\AiAgents\DeputyAdminOperationsAgent;
use App\AiAgents\LibrarianOperationsAgent;
use App\AiAgents\ParentOperationsAgent;
use App\AiAgents\ReceptionistOperationsAgent;
use App\AiAgents\Skills\SchoolAcademicsOpsSkill;
use App\AiAgents\StudentOperationsAgent;
use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\Tools\ApproveHomeworkTool;
use App\AiAgents\Tools\CreateHomeworkTool;
use App\AiAgents\Tools\CreateTimetableSlotTool;
use App\AiAgents\Tools\ListHomeworkTool;
use App\AiAgents\Tools\ListStudentHomeworkTool;
use App\AiAgents\Tools\ListTimetableSlotsTool;
use App\AiAgents\Tools\RejectHomeworkTool;
use App\AiAgents\Tools\RouteToSchoolAcademicsOpsSkillTool;
use App\AiAgents\Tools\ShowStudentHomeworkTool;
use App\AiAgents\Tools\UpdateHomeworkTool;
use App\AiAgents\Tools\UpdateStudentHomeworkTool;
use App\AiAgents\Tools\UpdateTimetableSlotTool;
use App\AiAgents\ToshiLlm;
use App\AiAgents\ToshiOrchestrator;
use App\AiAgents\WhatsApp\SchoolAdminWhatsAppReadAgent;
use App\AiAgents\WhatsApp\WhatsAppWriteExclusion;
use App\Models\Academics\TimetableSlot;
use App\Models\Homework;
use App\Models\HomeworkApproval;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentHomework;
use App\Models\Subject;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class SchoolAdminBatch2AcademicsOpsToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $schoolAdmin;

    private User $otherAdmin;

    private User $deputy;

    private int $schoolId;

    private int $otherSchoolId;

    private int $academicYearId;

    private int $otherAcademicYearId;

    private int $sectionId;

    private int $subjectId;

    private int $standardLinkId;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Batch2 Ops School',
            'slug' => 'batch2-ops',
            'email' => 'batch2-ops@test.sch.ug',
            'phone' => '+256700000401',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->otherSchoolId = DB::table('schools')->insertGetId([
            'name' => 'Other Ops School',
            'slug' => 'other-ops',
            'email' => 'other-ops@test.sch.ug',
            'phone' => '+256700000402',
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
        $this->sectionId = $section->id;
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

        $this->schoolAdmin = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'email' => 'admin.batch2@test.sch.ug',
            'name' => 'Batch2 Admin',
        ]);

        $this->otherAdmin = User::factory()->create([
            'school_id' => $this->otherSchoolId,
            'usergroup_id' => 3,
            'email' => 'admin.other.batch2@test.sch.ug',
            'name' => 'Other Admin',
        ]);

        $this->deputy = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 4,
            'email' => 'deputy.batch2@test.sch.ug',
            'name' => 'Batch2 Deputy',
        ]);

        $this->teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'teacher.batch2@test.sch.ug',
            'name' => 'Batch2 Teacher',
        ]);
    }

    public function test_happy_path_timetable_homework_and_review(): void
    {
        $this->actingAs($this->schoolAdmin);
        ToshiActionService::$bypassConfirm = true;

        $createSlot = (new CreateTimetableSlotTool)->handle(new Request([
            'section_id' => $this->sectionId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'room' => 'R1',
        ]));
        $this->assertStringStartsWith('✅', $createSlot);

        $slot = TimetableSlot::where('school_id', $this->schoolId)->firstOrFail();
        $listSlots = (new ListTimetableSlotsTool)->handle(new Request([]));
        $this->assertStringContainsString('#'.$slot->id, $listSlots);

        $updateSlot = (new UpdateTimetableSlotTool)->handle(new Request([
            'slot_id' => $slot->id,
            'room' => 'R2',
        ]));
        $this->assertStringStartsWith('✅', $updateSlot);
        $this->assertSame('R2', $slot->fresh()->room);

        $createHw = (new CreateHomeworkTool)->handle(new Request([
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $this->subjectId,
            'description' => 'Read chapter one',
            'date' => '2026-08-10',
            'submission_date' => '2026-08-15',
            'teacher_id' => $this->teacher->id,
        ]));
        $this->assertStringStartsWith('✅', $createHw);

        $homework = Homework::where('description', 'Read chapter one')->firstOrFail();
        $this->assertTrue(Gate::forUser($this->schoolAdmin)->allows('homework-manage', $homework));
        $this->assertDatabaseHas('homework_approvals', [
            'homework_id' => $homework->id,
            'status' => 'pending',
        ]);

        $listHw = (new ListHomeworkTool)->handle(new Request(['status' => 'pending']));
        $this->assertStringContainsString('#'.$homework->id, $listHw);

        $updateHw = (new UpdateHomeworkTool)->handle(new Request([
            'homework_id' => $homework->id,
            'description' => 'Read chapters one and two',
        ]));
        $this->assertStringStartsWith('✅', $updateHw);

        $approve = (new ApproveHomeworkTool)->handle(new Request([
            'homework_id' => $homework->id,
            'comments' => 'Looks good',
        ]));
        $this->assertStringStartsWith('✅', $approve);
        $this->assertDatabaseHas('homework_approvals', [
            'homework_id' => $homework->id,
            'status' => 'approved',
        ]);

        $student = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.batch2@test.sch.ug',
            'name' => 'Batch2 Student',
        ]);
        $submission = StudentHomework::create([
            'homework_id' => $homework->id,
            'user_id' => $student->id,
            'attachment' => [1 => 'uploads/hw.pdf'],
            'submitted_on' => '2026-08-12',
            'status' => 'unchecked',
        ]);

        $listSh = (new ListStudentHomeworkTool)->handle(new Request([
            'homework_id' => $homework->id,
        ]));
        $this->assertStringContainsString('#'.$submission->id, $listSh);

        $showSh = (new ShowStudentHomeworkTool)->handle(new Request([
            'student_homework_id' => $submission->id,
        ]));
        $this->assertStringContainsString('student_id: '.$student->id, $showSh);

        $check = (new UpdateStudentHomeworkTool)->handle(new Request([
            'student_homework_id' => $submission->id,
            'comments' => 'Well done',
        ]));
        $this->assertStringStartsWith('✅', $check);
        $this->assertSame('checked', $submission->fresh()->status);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_validation_rejects_bad_timetable_and_homework(): void
    {
        $this->actingAs($this->schoolAdmin);
        ToshiActionService::$bypassConfirm = true;

        $badSlot = (new CreateTimetableSlotTool)->handle(new Request([
            'section_id' => $this->sectionId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '09:00',
        ]));
        $this->assertStringStartsWith('❌', $badSlot);

        $badHw = (new CreateHomeworkTool)->handle(new Request([
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $this->subjectId,
            'description' => 'ab',
            'date' => '2026-08-10',
            'submission_date' => '2026-08-09',
        ]));
        $this->assertStringStartsWith('❌', $badHw);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_cross_school_homework_manage_denied_via_gate_and_tool(): void
    {
        $foreign = Homework::create([
            'school_id' => $this->otherSchoolId,
            'academic_year_id' => $this->otherAcademicYearId,
            'standardLink_id' => 1,
            'subject_id' => 1,
            'teacher_id' => $this->otherAdmin->id,
            'description' => 'Foreign homework',
            'date' => '2026-08-01',
            'created_by' => $this->otherAdmin->id,
        ]);
        HomeworkApproval::create(['homework_id' => $foreign->id, 'status' => 'pending']);

        $this->actingAs($this->schoolAdmin);
        $this->assertTrue(Gate::denies('homework-manage', $foreign));

        ToshiActionService::$bypassConfirm = true;
        $result = (new ApproveHomeworkTool)->handle(new Request([
            'homework_id' => $foreign->id,
            'comments' => 'Nope',
        ]));
        ToshiActionService::$bypassConfirm = false;

        $this->assertStringStartsWith('❌', $result);
        $this->assertStringContainsString('not authorized', strtolower($result));
        $this->assertDatabaseHas('homework_approvals', [
            'homework_id' => $foreign->id,
            'status' => 'pending',
        ]);
    }

    public function test_cross_school_timetable_update_denied(): void
    {
        $foreignSlot = TimetableSlot::create([
            'school_id' => $this->otherSchoolId,
            'academic_year_id' => $this->otherAcademicYearId,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => $this->otherAdmin->id,
            'day_of_week' => 2,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'X',
        ]);

        $this->actingAs($this->schoolAdmin);
        ToshiActionService::$bypassConfirm = true;
        $result = (new UpdateTimetableSlotTool)->handle(new Request([
            'slot_id' => $foreignSlot->id,
            'room' => 'Hijacked',
        ]));
        ToshiActionService::$bypassConfirm = false;

        $this->assertStringStartsWith('❌', $result);
        $this->assertSame('X', $foreignSlot->fresh()->room);
    }

    public function test_cross_school_student_homework_review_denied(): void
    {
        $foreignHw = Homework::create([
            'school_id' => $this->otherSchoolId,
            'academic_year_id' => $this->otherAcademicYearId,
            'standardLink_id' => 1,
            'subject_id' => 1,
            'teacher_id' => $this->otherAdmin->id,
            'description' => 'Other HW',
            'date' => '2026-08-01',
            'created_by' => $this->otherAdmin->id,
        ]);
        $foreignStudent = User::factory()->create([
            'school_id' => $this->otherSchoolId,
            'usergroup_id' => 6,
            'email' => 'foreign.student.batch2@test.sch.ug',
        ]);
        $foreignSh = StudentHomework::create([
            'homework_id' => $foreignHw->id,
            'user_id' => $foreignStudent->id,
            'attachment' => [1 => 'x.pdf'],
            'submitted_on' => '2026-08-01',
            'status' => 'unchecked',
        ]);

        $this->actingAs($this->schoolAdmin);
        $this->assertTrue(Gate::denies('studentHomework-review', $foreignSh));

        ToshiActionService::$bypassConfirm = true;
        $result = (new UpdateStudentHomeworkTool)->handle(new Request([
            'student_homework_id' => $foreignSh->id,
            'comments' => 'Hijack',
        ]));
        ToshiActionService::$bypassConfirm = false;

        $this->assertStringStartsWith('❌', $result);
        $this->assertSame('unchecked', $foreignSh->fresh()->status);
    }

    public function test_reject_homework_happy_path(): void
    {
        $this->actingAs($this->schoolAdmin);
        ToshiActionService::$bypassConfirm = true;

        (new CreateHomeworkTool)->handle(new Request([
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $this->subjectId,
            'description' => 'To reject',
            'date' => '2026-08-10',
            'submission_date' => '2026-08-15',
        ]));
        $homework = Homework::where('description', 'To reject')->firstOrFail();

        $reject = (new RejectHomeworkTool)->handle(new Request([
            'homework_id' => $homework->id,
            'comments' => 'Needs work',
        ]));
        ToshiActionService::$bypassConfirm = false;

        $this->assertStringStartsWith('✅', $reject);
        $this->assertDatabaseHas('homework_approvals', [
            'homework_id' => $homework->id,
            'status' => 'rejected',
        ]);
    }

    public function test_deputy_inherits_batch2_tools_not_settings(): void
    {
        $classes = collect((new DeputyAdminOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();

        foreach ([
            ListTimetableSlotsTool::class,
            CreateTimetableSlotTool::class,
            UpdateTimetableSlotTool::class,
            ListHomeworkTool::class,
            CreateHomeworkTool::class,
            UpdateHomeworkTool::class,
            ApproveHomeworkTool::class,
            RejectHomeworkTool::class,
            ListStudentHomeworkTool::class,
            ShowStudentHomeworkTool::class,
            UpdateStudentHomeworkTool::class,
        ] as $tool) {
            $this->assertContains($tool, $classes);
        }

        $this->actingAs($this->deputy);
        $list = (new ListTimetableSlotsTool)->handle(new Request([]));
        $this->assertStringNotContainsString('not authorized', strtolower($list));
    }

    public function test_orchestrator_and_skill_register_academics_ops(): void
    {
        $orch = collect((new ToshiOrchestrator)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertContains(RouteToSchoolAcademicsOpsSkillTool::class, $orch);

        $skill = collect((new SchoolAcademicsOpsSkill)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertCount(11, $skill);
        $this->assertContains(ApproveHomeworkTool::class, $skill);
        $this->assertContains(CreateTimetableSlotTool::class, $skill);
    }

    public function test_batch2_tools_absent_from_non_admin_agents(): void
    {
        $targets = [
            ListTimetableSlotsTool::class,
            CreateTimetableSlotTool::class,
            ApproveHomeworkTool::class,
            UpdateStudentHomeworkTool::class,
            RouteToSchoolAcademicsOpsSkillTool::class,
        ];

        foreach ([
            new TeacherOperationsAgent,
            new AccountantOperationsAgent,
            new LibrarianOperationsAgent,
            new ReceptionistOperationsAgent,
            new StudentOperationsAgent,
            new ParentOperationsAgent,
        ] as $agent) {
            $classes = collect($agent->tools())->map(fn ($t) => $t::class)->all();
            foreach ($targets as $tool) {
                $this->assertNotContains($tool, $classes, $agent::class.' must not expose '.$tool);
            }
        }
    }

    public function test_whatsapp_lists_allowed_and_writes_fail_closed(): void
    {
        $waClasses = collect((new SchoolAdminWhatsAppReadAgent)->tools())->map(fn ($t) => $t::class)->all();

        $this->assertContains(ListTimetableSlotsTool::class, $waClasses);
        $this->assertContains(ListHomeworkTool::class, $waClasses);
        $this->assertContains(ListStudentHomeworkTool::class, $waClasses);
        $this->assertContains(ShowStudentHomeworkTool::class, $waClasses);
        $this->assertNotContains(CreateTimetableSlotTool::class, $waClasses);
        $this->assertNotContains(ApproveHomeworkTool::class, $waClasses);
        $this->assertNotContains(UpdateStudentHomeworkTool::class, $waClasses);

        $this->assertTrue(WhatsAppWriteExclusion::allowsClass(ListTimetableSlotsTool::class));
        $this->assertTrue(WhatsAppWriteExclusion::allowsClass(ListHomeworkTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(CreateHomeworkTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(ApproveHomeworkTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(UpdateTimetableSlotTool::class));
    }

    public function test_role_capabilities_include_batch2_for_admin_and_deputy(): void
    {
        $admin = ToshiActionService::getRoleCapabilities(3);
        $deputy = ToshiActionService::getRoleCapabilities(4);

        foreach ([
            'list_timetable_slots', 'create_timetable_slot', 'update_timetable_slot',
            'list_homework', 'create_homework', 'update_homework',
            'approve_homework', 'reject_homework',
            'list_student_homework', 'show_student_homework', 'update_student_homework',
        ] as $action) {
            $this->assertContains($action, $admin['actions']);
            $this->assertContains($action, $deputy['actions']);
        }

        $this->assertNotContains('settings', $deputy['actions']);
        $this->assertNotContains('add_coadmin', $deputy['actions']);
    }

    public function test_skill_is_custom_route_not_sdk_sub_agent_and_uses_toshi_llm(): void
    {
        $this->assertFalse((new SchoolAcademicsOpsSkill) instanceof CanActAsTool);
        $source = file_get_contents(app_path('AiAgents/Tools/RouteToSchoolAcademicsOpsSkillTool.php'));
        $this->assertStringContainsString('new SchoolAcademicsOpsSkill', $source);
        $this->assertStringContainsString('NOT Laravel AI Sub-Agents / CanActAsTool', $source);

        config(['toshi.model' => 'batch2-academics-ops-model']);
        $skill = new SchoolAcademicsOpsSkill;
        $this->assertSame(ToshiLlm::model(), $skill->model());
        $this->assertSame('openai-compatible', $skill->provider());
        $this->assertSame('batch2-academics-ops-model', $skill->model());
    }
}
