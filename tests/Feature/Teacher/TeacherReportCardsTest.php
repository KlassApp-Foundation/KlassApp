<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherReportCardsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $classTeacher;

    private User $peerClassTeacher;

    private User $subjectTeacher;

    private User $student;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Standard $standard;

    private Section $section;

    private Section $otherSection;

    private StandardLink $stream;

    private StandardLink $otherStream;

    private Subject $subject;

    private Exam $eot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'CT Reports School',
            'slug' => 'ct-reports-' . uniqid(),
            'email' => 'ct-reports-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'report_template' => 'warm',
            'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => '1',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.3 CT',
            'status' => 1,
        ]);

        $this->otherSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.4 Peer',
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'email' => 'admin.reports@t.sch.ug',
        ]);

        $this->classTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Class Teacher Reports',
            'email' => 'ct.reports@t.sch.ug',
        ]);

        $this->peerClassTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Peer CT Reports',
            'email' => 'peer.ct.reports@t.sch.ug',
        ]);

        $this->subjectTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Subject Teacher Reports',
            'email' => 'subj.reports@t.sch.ug',
        ]);

        $this->stream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $this->classTeacher->id,
            'status' => '1',
        ]);

        $this->otherStream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->otherSection->id,
            'class_teacher_id' => $this->peerClassTeacher->id,
            'status' => '1',
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);

        Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics',
            'code' => 'MTC',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->eot = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'exam_type_id' => 2,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => '2026-04-01',
            'status' => 'submitted',
        ]));

        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Report Student',
            'email' => 'student.reports@t.sch.ug',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $this->stream->id,
        ]);

        Marks::create([
            'student_id' => $this->student->id,
            'exam_id' => $this->eot->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'section_id' => $this->section->id,
            'marks' => 68,
            'grade' => 'D2',
        ]);
    }

    public function test_ct_sees_only_own_class_on_index(): void
    {
        $response = $this->actingAs($this->classTeacher)->get(route('teacher.reports.cards.index'));

        $response->assertOk();
        $response->assertSee('P.3 CT');
        $response->assertDontSee('P.4 Peer');
        $response->assertSee('1 / 2');
    }

    public function test_ct_can_preview_and_download_own_student_pdf(): void
    {
        $preview = $this->actingAs($this->classTeacher)->get(route('teacher.reports.cards.student.preview', [
            'stdLink' => $this->stream->id,
            'learner' => $this->student->id,
        ]));

        $preview->assertOk();
        $this->assertSame('application/pdf', $preview->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $preview->getContent());
        $this->assertStringContainsString('inline', (string) $preview->headers->get('Content-Disposition'));

        $download = $this->actingAs($this->classTeacher)->get(route('teacher.reports.cards.student.download', [
            'stdLink' => $this->stream->id,
            'learner' => $this->student->id,
        ]));

        $download->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('Content-Disposition'));
    }

    public function test_subject_teacher_and_peer_ct_forbidden_on_class(): void
    {
        $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.reports.cards.show', $this->stream))
            ->assertForbidden();

        $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.reports.cards.student.preview', [
                'stdLink' => $this->stream->id,
                'learner' => $this->student->id,
            ]))
            ->assertForbidden();

        $this->actingAs($this->peerClassTeacher)
            ->get(route('teacher.reports.cards.show', $this->stream))
            ->assertForbidden();

        $this->actingAs($this->peerClassTeacher)
            ->get(route('teacher.reports.cards.student.download', [
                'stdLink' => $this->stream->id,
                'learner' => $this->student->id,
            ]))
            ->assertForbidden();
    }

    public function test_admin_path_unaffected_and_matches_teacher_pdf_payload(): void
    {
        $service = app(StudentReportCardService::class);
        $expected = $service->pdfForStudent($this->school->id, $this->stream, $this->student, null);

        $admin = $this->actingAs($this->admin)->get(route('admin.reports.cards.student.preview', [
            'stdLink' => $this->stream->id,
            'learner' => $this->student->id,
        ]));

        $admin->assertOk();
        $this->assertSame('application/pdf', $admin->headers->get('Content-Type'));
        $this->assertPdfPayloadEqual($expected, $admin->getContent());

        $teacher = $this->actingAs($this->classTeacher)->get(route('teacher.reports.cards.student.preview', [
            'stdLink' => $this->stream->id,
            'learner' => $this->student->id,
        ]));

        $teacher->assertOk();
        $this->assertPdfPayloadEqual($expected, $teacher->getContent());
    }

    public function test_sidebar_report_cards_link_only_for_class_teachers(): void
    {
        $ctHtml = view('layouts.teacher.menu')->with([])->render();
        // Menu reads auth(); actingAs before render.
        $this->actingAs($this->classTeacher);
        $ctHtml = view('layouts.teacher.menu')->render();
        $this->assertStringContainsString(route('teacher.reports.cards.index'), $ctHtml);

        $this->actingAs($this->subjectTeacher);
        $subjHtml = view('layouts.teacher.menu')->render();
        $this->assertStringNotContainsString(route('teacher.reports.cards.index'), $subjHtml);
    }

    public function test_empty_state_when_no_contributing_exam(): void
    {
        $this->eot->delete();

        $response = $this->actingAs($this->classTeacher)->get(route('teacher.reports.cards.show', $this->stream));

        $response->assertOk();
        $response->assertSee('No EOT exam found for this class yet.');
    }

    private function assertPdfPayloadEqual(string $a, string $b): void
    {
        $this->assertSame($this->normalizePdfPayload($a), $this->normalizePdfPayload($b));
    }

    private function normalizePdfPayload(string $pdf): string
    {
        $pdf = preg_replace('/\/CreationDate \(D:[^)]+\)/', '/CreationDate (D:FIXED)', $pdf) ?? $pdf;
        $pdf = preg_replace('/\/ModDate \(D:[^)]+\)/', '/ModDate (D:FIXED)', $pdf) ?? $pdf;
        $pdf = preg_replace('/\/ID\[[^\]]+\]/', '/ID[<FIXED><FIXED>]', $pdf) ?? $pdf;

        return $pdf;
    }
}
