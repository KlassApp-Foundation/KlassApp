<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * Security invariants for the marks-import template route: ExamAuthorization
 * applies identically to downloads, and the roster is keyed by KLS number (registration_number).
 */
class MarksTemplateDownloadTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private User $ownerTeacher;

    private User $otherTeacher;

    private User $crossSchoolTeacher;

    private User $studentOne;

    private User $studentTwo;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Section $section;

    private Standard $standard;

    private StandardLink $stream;

    private Subject $subject;

    private ExamType $examType;

    private Exam $ownedExam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Template Test School',
            'slug' => 'template-test-' . uniqid(),
            'email' => 'template-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->otherSchool = School::create([
            'name' => 'Template Other School',
            'slug' => 'template-other-' . uniqid(),
            'email' => 'template-other-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->ownerTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Owner Teacher',
            'email' => 'owner.template@t.sch.ug',
        ]);

        $this->otherTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Other Teacher',
            'email' => 'other.template@t.sch.ug',
        ]);

        $this->crossSchoolTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->otherSchool->id,
            'name' => 'Cross School Teacher',
            'email' => 'cross.template@t.sch.ug',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-05-01',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'status' => 1,
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->stream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $this->ownerTeacher->id,
            'status' => '1',
        ]);

        $this->studentOne = $this->makeStudent('KLS0000001', 'Amina', 'Nakato', 'amina.template@t.sch.ug');
        $this->studentTwo = $this->makeStudent('KLS0000002', 'Brian', 'Ssekandi', 'brian.template@t.sch.ug');

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Mathematics',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->examType = ExamType::create([
            'name' => 'Mid Term',
            'code' => 'MID',
            'contributes_to_report_total' => true,
        ]);

        $this->ownedExam = Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->ownerTeacher->id,
            'exam_type_id' => $this->examType->id,
            'status' => 'undone',
        ]);
    }

    private function makeStudent(string $registrationNumber, string $firstname, string $lastname, string $email): User
    {
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => $firstname . ' ' . $lastname,
            'email' => $email,
            'registration_number' => $registrationNumber,
        ]);

        Userprofile::create([
            'user_id' => $student->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $student->id,
            'standardLink_id' => $this->stream->id,
        ]);

        return $student;
    }

    /** Excel::download returns StreamedResponse or BinaryFileResponse depending on writer — normalise to bytes. */
    private function downloadedBytes(TestResponse $response): string
    {
        $base = $response->baseResponse;

        if ($base instanceof StreamedResponse) {
            return $response->streamedContent();
        }

        if ($base instanceof BinaryFileResponse) {
            return file_get_contents($base->getFile()->getPathname());
        }

        $this->fail('Unexpected template download response type: ' . get_class($base));
    }

    private function templateSpreadsheet(TestResponse $response): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $bytes = $this->downloadedBytes($response);

        $tmp = tempnam(sys_get_temp_dir(), 'marks-template-') . '.xlsx';
        file_put_contents($tmp, $bytes);

        try {
            return IOFactory::load($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    private function templateRows(TestResponse $response): array
    {
        return $this->templateSpreadsheet($response)->getSheetByName('Marks')->toArray(null, true, true, false);
    }

    public function test_owner_teacher_downloads_template_with_roster_and_kls_number_column(): void
    {
        $response = $this->actingAs($this->ownerTeacher)->get(
            route('teacher.exam.marks.template', ['exam' => $this->ownedExam])
        );

        $response->assertOk();

        $rows = $this->templateRows($response);
        $headings = $rows[0];

        $this->assertContains('KLS number', $headings);
        $this->assertContains('student_name', $headings);
        $this->assertContains('Mark (out of 100)', $headings);

        $regIndex = array_search('KLS number', $headings, true);
        $dataRows = array_slice($rows, 1);

        $this->assertCount(2, $dataRows);
        $registrationNumbers = array_column($dataRows, $regIndex);
        sort($registrationNumbers);
        $this->assertSame(['KLS0000001', 'KLS0000002'], $registrationNumbers);

        $markIndex = array_search('Mark (out of 100)', $headings, true);
        foreach ($dataRows as $row) {
            $this->assertTrue($row[$markIndex] === null || $row[$markIndex] === '', 'mark cell should be empty');
        }
    }

    public function test_template_download_works_as_csv_format(): void
    {
        $response = $this->actingAs($this->ownerTeacher)->get(
            route('teacher.exam.marks.template', ['exam' => $this->ownedExam, 'format' => 'csv'])
        );

        $response->assertOk();

        $content = $this->downloadedBytes($response);
        $this->assertStringContainsString('KLS number', $content);
        $this->assertStringContainsString('KLS0000001', $content);
        $this->assertStringContainsString('KLS0000002', $content);
    }

    public function test_other_same_school_teacher_is_forbidden(): void
    {
        $this->actingAs($this->otherTeacher)
            ->get(route('teacher.exam.marks.template', ['exam' => $this->ownedExam]))
            ->assertForbidden();
    }

    public function test_cross_school_teacher_is_forbidden(): void
    {
        $this->actingAs($this->crossSchoolTeacher)
            ->get(route('teacher.exam.marks.template', ['exam' => $this->ownedExam]))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('teacher.exam.marks.template', ['exam' => $this->ownedExam]))
            ->assertRedirect();
    }

    public function test_template_carries_the_exam_info_sheet_with_a_verifiable_key(): void
    {
        $response = $this->actingAs($this->ownerTeacher)->get(
            route('teacher.exam.marks.template', ['exam' => $this->ownedExam])
        );
        $response->assertOk();

        $ss = $this->templateSpreadsheet($response);
        $this->assertSame(['Marks', 'Exam info'], $ss->getSheetNames());

        $info = [];
        foreach ($ss->getSheetByName('Exam info')->toArray() as $row) {
            if (($row[0] ?? '') !== '') { $info[$row[0]] = $row[1] ?? null; }
        }

        $this->assertSame($this->school->name, $info['School']);
        $this->assertSame('P1', $info['Class and stream']);
        $this->assertSame('MATHEMATICS', $info['Subject']);
        $this->assertSame('100', (string) $info['Maximum marks']);
        $this->assertSame('marks-import-v1', $info['Template version']);
        $this->assertMatchesRegularExpression('/^'.$this->ownedExam->id.'-[0-9a-f]{10}$/', (string) $info['Template key']);
    }
}
