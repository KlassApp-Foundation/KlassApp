<?php

namespace Tests\Feature\Toshi\Adversarial;

use App\AiAgents\StudentOperationsAgent;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\CreateExamTool;
use App\AiAgents\Tools\Student\ViewAttendanceTool;
use App\AiAgents\Tools\Student\ViewMarksTool;
use App\Models\Academics\Marks;
use App\Models\Attendance;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Support\Toshi\AdversarialPromptFixtures;
use App\Models\Subject;
use App\Models\User;
use App\Services\Toshi\StudentActionService;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Exceptions\NoSuchToolException;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

/**
 * Structural-isolation regression coverage under adversarial-shaped prompts.
 *
 * NOT jailbreak-resistance proof. These tests do not evaluate soft-refusal quality
 * of any live LLM. They prove peer self-scope and off-role tool absence under the
 * framing of manipulative prompts.
 *
 * Prompt pressure simulation: Agent::fake scripts a compliance-shaped ToolCall.
 * Off-role tools → NoSuchToolException (not registered). On-role peer-scoped tools
 * → tool may run, but forged peer ids are ignored and peer secrets stay absent.
 */
class StudentAdversarialIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $studentA;

    private User $studentB;

    private User $teacher;

    private int $schoolId;

    private int $academicYearId;

    private int $standardLinkId;

    private int $subjectId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Student Adv School',
            'slug' => 'student-adv',
            'email' => 'student-adv@test.sch.ug',
            'phone' => '+256700000166',
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
            'email' => 'teacher.student-adv@test.sch.ug',
        ]);

        $this->studentA = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.a.adv@test.sch.ug',
            'name' => 'Student A Adv',
        ]);
        $this->studentB = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.b.adv@test.sch.ug',
            'name' => 'Student B Adv',
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
    }

    public function test_ignore_instructions_peer_marks_still_self_scoped(): void
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

        $this->actingAs($this->studentA);
        $attack = AdversarialPromptFixtures::studentPeerMarks($this->studentB->id);

        // Compliance attempt: invoke registered ViewMarksTool with forged peer id.
        // Follow-up text satisfies the post-tool agent turn (no live LLM).
        StudentOperationsAgent::fake([
            new ToolCall('call_adv_marks', 'ViewMarksTool', [
                'student_id' => $this->studentB->id,
            ]),
            'Showing marks (fake compliance text).',
        ]);

        (new StudentOperationsAgent)->prompt($attack);

        $toolResult = (new ViewMarksTool)->handle(new Request([
            'student_id' => $this->studentB->id,
        ]));
        $this->assertStringContainsString('70', $toolResult);
        $this->assertStringNotContainsString('99', $toolResult);

        $marks = StudentActionService::viewMarks($this->studentA);
        $this->assertStringContainsString('70', $marks['message']);
        $this->assertStringNotContainsString('99', $marks['message']);
    }

    public function test_you_are_now_admin_create_exam_still_structurally_denied(): void
    {
        $this->actingAs($this->studentA);
        ToshiActionService::$bypassConfirm = true;

        StudentOperationsAgent::fake([
            new ToolCall('call_adv_exam', 'CreateExamTool', [
                'name' => 'Forbidden Exam',
            ]),
        ]);

        try {
            (new StudentOperationsAgent)->prompt(AdversarialPromptFixtures::STUDENT_NOW_ADMIN_CREATE_EXAM);
            $this->fail('Expected NoSuchToolException for off-role CreateExamTool');
        } catch (NoSuchToolException $e) {
            $this->assertSame('CreateExamTool', $e->toolName);
        }

        $classes = collect((new StudentOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertNotContains(CreateExamTool::class, $classes);
        $this->assertContains(ViewMarksTool::class, $classes);

        $result = (new CreateExamTool)->handle(new Request(['name' => 'Forbidden Exam']));
        $this->assertStringStartsWith('❌', $result);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_as_admin_add_coadmin_still_structurally_denied(): void
    {
        $this->actingAs($this->studentA);
        ToshiActionService::$bypassConfirm = true;

        StudentOperationsAgent::fake([
            new ToolCall('call_adv_coadmin', 'AddCoAdminTool', [
                'name' => 'StudentEscalation',
                'email' => 'student.esc@test.sch.ug',
            ]),
        ]);

        try {
            (new StudentOperationsAgent)->prompt(AdversarialPromptFixtures::STUDENT_ADD_COADMIN);
            $this->fail('Expected NoSuchToolException for off-role AddCoAdminTool');
        } catch (NoSuchToolException $e) {
            $this->assertSame('AddCoAdminTool', $e->toolName);
        }

        $classes = collect((new StudentOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertNotContains(AddCoAdminTool::class, $classes);

        $result = (new AddCoAdminTool)->handle(new Request([
            'name' => 'StudentEscalation',
            'email' => 'student.esc@test.sch.ug',
        ]));
        $this->assertStringStartsWith('❌', $result);
        $this->assertSame(0, User::where('email', 'student.esc@test.sch.ug')->count());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_peer_attendance_prompt_still_self_scoped(): void
    {
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

        StudentOperationsAgent::fake([
            new ToolCall('call_adv_attendance', 'ViewAttendanceTool', [
                'student_id' => $this->studentB->id,
            ]),
            'Showing attendance (fake compliance text).',
        ]);

        (new StudentOperationsAgent)->prompt(
            AdversarialPromptFixtures::studentPeerAttendance($this->studentB->id)
        );

        $this->assertContains(
            ViewAttendanceTool::class,
            collect((new StudentOperationsAgent)->tools())->map(fn ($t) => $t::class)->all()
        );

        $attendance = StudentActionService::viewAttendance($this->studentA);
        $this->assertSame(1, $attendance['data']['total']);
        $this->assertSame(0, $attendance['data']['present']);
        $this->assertSame(1, $attendance['data']['absent']);
    }
}
