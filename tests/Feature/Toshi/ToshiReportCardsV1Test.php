<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\DeputyAdminOperationsAgent;
use App\AiAgents\Skills\SchoolAcademicsOpsSkill;
use App\AiAgents\Skills\TeacherTeachingOpsSkill;
use App\AiAgents\Tools\GenerateStudentReportCardTool as AdminGenerateStudentReportCardTool;
use App\AiAgents\Tools\Teacher\GenerateStudentReportCardTool as TeacherGenerateStudentReportCardTool;
use App\Helpers\GradingHelper;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\StudentReportCardService;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class ToshiReportCardsV1Test extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private int $otherSchoolId;

    private int $academicYearId;

    private int $termId;

    private User $schoolAdmin;

    private User $otherAdmin;

    private User $deputy;

    private User $teacher;

    private User $peerTeacher;

    private ExamType $examType;

    protected function setUp(): void
    {
        parent::setUp();
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Report Cards School',
            'slug' => 'report-cards-v1',
            'email' => 'rcards@test.sch.ug',
            'phone' => '+256700009901',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->otherSchoolId = DB::table('schools')->insertGetId([
            'name' => 'Other Report School',
            'slug' => 'other-rcards',
            'email' => 'other-rcards@test.sch.ug',
            'phone' => '+256700009902',
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

        $this->termId = DB::table('academic_terms')->insertGetId([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'name' => 'First Term',
            'status' => 'current',
            'starts_on' => '2026-01-15',
            'ends_on' => '2026-04-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->examType = ExamType::create(['name' => 'End of Term', 'code' => 'EOT']);

        $this->schoolAdmin = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'email' => 'admin.rcards@test.sch.ug',
            'name' => 'RC Admin',
        ]);

        $this->otherAdmin = User::factory()->create([
            'school_id' => $this->otherSchoolId,
            'usergroup_id' => 3,
            'email' => 'admin.other.rcards@test.sch.ug',
            'name' => 'Other RC Admin',
        ]);

        $this->deputy = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 4,
            'email' => 'deputy.rcards@test.sch.ug',
            'name' => 'RC Deputy',
        ]);

        $this->teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'teacher.rcards@test.sch.ug',
            'name' => 'RC Teacher',
        ]);

        $this->peerTeacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'peer.rcards@test.sch.ug',
            'name' => 'Peer Teacher',
        ]);
    }

    /**
     * @return array{student: User, exam: Exam, section_id: int, standard_link_id: int, subject_id: int}
     */
    private function seedLevelStudent(string $standardName): array
    {
        $standard = Standard::create([
            'school_id' => $this->schoolId,
            'name' => $standardName,
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->schoolId,
            'name' => 'A-'.substr(md5($standardName), 0, 6),
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $this->teacher->id,
            'no_of_students' => 10,
            'status' => 1,
        ]);
        $subject = Subject::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'English',
            'type' => 'core',
            'status' => 1,
        ]);

        $student = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'stu.'.uniqid().'@test.sch.ug',
            'name' => 'Student '.$standardName,
        ]);
        Userprofile::create([
            'user_id' => $student->id,
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'firstname' => 'Ada',
            'lastname' => str_replace(' ', '', $standardName),
            'status' => 'active',
        ]);
        DB::table('student_academics')->insert([
            'school_id' => $this->schoolId,
            'user_id' => $student->id,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $link->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exam = Exam::create([
            'school_id' => $this->schoolId,
            'subject_id' => $subject->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->academicYearId,
            'academic_term_id' => $this->termId,
            'exam_type_id' => $this->examType->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'done',
        ]);

        Marks::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacher->id,
            'school_id' => $this->schoolId,
            'subject_id' => $subject->id,
            'exam_id' => $exam->id,
            'section_id' => $section->id,
            'marks' => 75,
            'grade' => 'B',
        ]);

        return [
            'student' => $student->fresh(),
            'exam' => $exam->fresh(['examType', 'marks']),
            'section_id' => $section->id,
            'standard_link_id' => $link->id,
            'subject_id' => $subject->id,
        ];
    }

    public function test_primary_nursery_o_a_pdfs_are_valid_and_include_academic_year(): void
    {
        $cases = [
            'Primary One' => 'primary',
            'Nursery' => 'nursery',
            'Senior Three' => 'o-level',
            'Senior Five' => 'a-level',
        ];

        $service = app(StudentReportCardService::class);

        foreach ($cases as $standardName => $level) {
            $seed = $this->seedLevelStudent($standardName);
            $this->assertSame($level, GradingHelper::levelTypeForStandard(
                Standard::where('name', $standardName)->where('school_id', $this->schoolId)->first()
            ));

            $built = $service->buildViewData(
                $this->schoolId,
                $seed['student'],
                $seed['section_id'],
                $seed['exam'],
                School::find($this->schoolId)
            );
            $this->assertTrue($built['success'], $standardName.': '.($built['message'] ?? ''));
            $this->assertSame('2026', $built['data']['academicYearName']);
            $this->assertSame($level === 'nursery', (bool) $built['data']['isNursery']);

            $html = view('admin.marks.student-report', $built['data'])->render();
            $this->assertStringContainsString('2026', $html);
            $this->assertStringNotContainsString('academicYear', $html);

            $saved = $service->saveToPublic(
                $this->schoolId,
                $seed['student'],
                $seed['section_id'],
                $seed['exam'],
                School::find($this->schoolId)
            );
            $this->assertTrue($saved['success'], $standardName.': '.($saved['message'] ?? ''));
            $this->assertFileExists($saved['path']);
            $bytes = file_get_contents($saved['path']);
            $this->assertStringStartsWith('%PDF', $bytes);
            $this->assertSame('2026', $saved['academic_year']);
        }
    }

    public function test_teacher_isolation_assigned_class_only(): void
    {
        $seed = $this->seedLevelStudent('Primary Two');

        $this->actingAs($this->teacher);
        $ok = (new TeacherGenerateStudentReportCardTool)->handle(new Request([
            'student_id' => $seed['student']->id,
            'exam_id' => $seed['exam']->id,
        ]));
        $this->assertStringNotContainsString('❌', $ok);
        $this->assertStringContainsString('Download:', $ok);

        $this->actingAs($this->peerTeacher);
        $denied = (new TeacherGenerateStudentReportCardTool)->handle(new Request([
            'student_id' => $seed['student']->id,
            'exam_id' => $seed['exam']->id,
        ]));
        $this->assertStringStartsWith('❌', $denied);
        $this->assertStringContainsString('assigned class', strtolower($denied));
    }

    public function test_school_admin_cross_school_deny(): void
    {
        $seed = $this->seedLevelStudent('Primary Three');

        $this->actingAs($this->otherAdmin);
        $result = (new AdminGenerateStudentReportCardTool)->handle(new Request([
            'student_id' => $seed['student']->id,
            'exam_id' => $seed['exam']->id,
        ]));
        $this->assertStringStartsWith('❌', $result);
    }

    public function test_deputy_inherits_generate_tool_not_settings(): void
    {
        $classes = collect((new DeputyAdminOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertContains(AdminGenerateStudentReportCardTool::class, $classes);
        $this->assertNotContains(\App\AiAgents\Tools\SetCurriculumTool::class, $classes);

        $seed = $this->seedLevelStudent('Primary Four');
        $this->actingAs($this->deputy);
        $result = (new AdminGenerateStudentReportCardTool)->handle(new Request([
            'student_id' => $seed['student']->id,
            'exam_id' => $seed['exam']->id,
        ]));
        $this->assertStringNotContainsString('❌', $result);
        $this->assertStringContainsString('Download:', $result);
    }

    public function test_skills_register_report_card_tools(): void
    {
        $sa = collect((new SchoolAcademicsOpsSkill)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertContains(AdminGenerateStudentReportCardTool::class, $sa);

        $teacher = collect((new TeacherTeachingOpsSkill)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertContains(TeacherGenerateStudentReportCardTool::class, $teacher);
    }

    public function test_whatsapp_and_admin_share_student_report_card_service(): void
    {
        $wa = file_get_contents(app_path('Http/Controllers/Api/WhatsAppController.php'));
        $admin = file_get_contents(app_path('Http/Controllers/Admin/DownloadStudentReport.php'));
        $this->assertStringContainsString('StudentReportCardService', $wa);
        $this->assertStringContainsString('StudentReportCardService', $admin);
        $this->assertStringContainsString('generateForWhatsAppParent', $wa);
        $this->assertStringNotContainsString("loadView('whatsapp.report-card'", $wa);
    }

    public function test_teacherlink_also_grants_access(): void
    {
        $seed = $this->seedLevelStudent('Primary Five');
        StandardLink::where('id', $seed['standard_link_id'])->update(['class_teacher_id' => $this->peerTeacher->id]);

        Teacherlink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $seed['standard_link_id'],
            'teacher_id' => $this->teacher->id,
            'subject_id' => $seed['subject_id'],
            'status' => 1,
        ]);

        $this->actingAs($this->teacher);
        $ok = (new TeacherGenerateStudentReportCardTool)->handle(new Request([
            'student_id' => $seed['student']->id,
            'exam_id' => $seed['exam']->id,
        ]));
        $this->assertStringNotContainsString('❌', $ok);
    }
}
