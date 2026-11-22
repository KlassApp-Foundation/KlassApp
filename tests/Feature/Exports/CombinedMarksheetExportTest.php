<?php

namespace Tests\Feature\Exports;

use App\Exports\CombinedMarksheetExport;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class CombinedMarksheetExportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private AcademicTerm $term;
    private Standard $standard;
    private Section $section;
    private StandardLink $stdLink;
    private User $admin;
    private User $classTeacher;
    private User $subjectTeacher;
    private User $otherTeacher;
    private Subject $english;
    private Subject $math;
    private Exam $juneMid;
    private Exam $julyMid;
    private Exam $eot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Combined Marksheet School', 'email' => 'combined@test.sch.ug',
            'phone' => '0700000002', 'slug' => 'combined-marksheet-school', 'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term 2', 'status' => 'current',
            'starts_on' => '2026-05-01', 'ends_on' => '2026-08-31',
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary_lower', 'order' => 1, 'status' => '1',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id, 'name' => 'P.2', 'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3, 'school_id' => $this->school->id, 'email' => 'admin.combined@t.sch.ug',
        ]);

        $this->classTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'class.combined@t.sch.ug',
        ]);

        $this->subjectTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'subject.combined@t.sch.ug',
        ]);

        $this->otherTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'other.combined@t.sch.ug',
        ]);

        $this->stdLink = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id,
            'class_teacher_id' => $this->classTeacher->id, 'status' => '1',
        ]);

        $this->english = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id,
            'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);

        $this->math = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id,
            'name' => 'Mathematics', 'code' => 'MTC', 'type' => 'core', 'status' => 1,
        ]);

        $this->juneMid = $this->createExam(1, '2026-06-15', $this->subjectTeacher->id);
        $this->julyMid = $this->createExam(1, '2026-07-15', $this->classTeacher->id);
        $this->eot = $this->createExam(2, '2026-08-01', $this->classTeacher->id);

        $this->seedStudentsAndMarks();
    }

    private function createExam(int $examTypeId, string $scheduledAt, int $teacherId): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->english->id,
            'teacher_id' => $teacherId,
            'exam_type_id' => $examTypeId,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => $scheduledAt,
            'status' => 'submitted',
        ]));
    }

    private function seedStudentsAndMarks(): void
    {
        $alice = $this->student('Alice A', 'alice.combined@t.sch.ug', 'active');
        $bob = $this->student('Bob B', 'bob.combined@t.sch.ug', 'active');
        $charlie = $this->student('Charlie C', 'charlie.combined@t.sch.ug', 'active');
        $dave = $this->student('Dave D', 'dave.combined@t.sch.ug', 'inactive');

        // Alice: full marks except July Math (blank) — and a Dave mark to prove exclusion.
        $this->mark($alice, $this->juneMid, $this->english, 45);
        $this->mark($alice, $this->juneMid, $this->math, 50);
        $this->mark($alice, $this->julyMid, $this->english, 48);
        $this->mark($alice, $this->eot, $this->english, 60);
        $this->mark($alice, $this->eot, $this->math, 65);

        // Bob: full marks across all three exams.
        $this->mark($bob, $this->juneMid, $this->english, 40);
        $this->mark($bob, $this->juneMid, $this->math, 42);
        $this->mark($bob, $this->julyMid, $this->english, 44);
        $this->mark($bob, $this->julyMid, $this->math, 46);
        $this->mark($bob, $this->eot, $this->english, 58);
        $this->mark($bob, $this->eot, $this->math, 61);

        // Charlie: absent for June English. July and the counting set stay blank.
        Marks::create([
            'student_id' => $charlie->id, 'exam_id' => $this->juneMid->id,
            'school_id' => $this->school->id, 'subject_id' => $this->english->id,
            'teacher_id' => $this->juneMid->teacher_id, 'section_id' => $this->section->id,
            'marks' => null, 'grade' => 'AB', 'absent' => true,
        ]);
        // Dave: inactive — has a mark but must be excluded from the roster.
        $this->mark($dave, $this->juneMid, $this->english, 99);
    }

    private function student(string $name, string $email, string $status): User
    {
        $student = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $this->school->id,
            'name' => $name, 'email' => $email, 'status' => $status,
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'user_id' => $student->id, 'standardLink_id' => $this->stdLink->id,
        ]);

        return $student;
    }

    private function mark(User $student, Exam $exam, Subject $subject, int $marks): void
    {
        Marks::create([
            'student_id' => $student->id, 'exam_id' => $exam->id,
            'school_id' => $this->school->id, 'subject_id' => $subject->id,
            'teacher_id' => $exam->teacher_id, 'section_id' => $this->section->id,
            'marks' => $marks, 'grade' => 'D1',
        ]);
    }

    private function sheetsFromBinary(string $binary): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cm') . '.xlsx';
        file_put_contents($tmp, $binary);
        $reader = IOFactory::load($tmp);
        @unlink($tmp);

        return [
            $reader->getSheetByName('Mid Term JUNE'),
            $reader->getSheetByName('Mid Term JULY'),
            $reader->getSheetByName('End Of Term'),
        ];
    }

    public function test_workbook_has_both_sheets_with_correct_values(): void
    {
        $binary = Excel::raw(new CombinedMarksheetExport($this->stdLink, $this->term), \Maatwebsite\Excel\Excel::XLSX);
        $tmp = tempnam(sys_get_temp_dir(), 'cm').'.xlsx';
        file_put_contents($tmp, $binary);
        $book = IOFactory::load($tmp);
        @unlink($tmp);

        $this->assertNull($book->getSheetByName('MONTHLY RESULTS'));
        $this->assertNull($book->getSheetByName('END OF TERM'));
        $this->assertSame(['Mid Term JUNE', 'Mid Term JULY', 'End Of Term'], $book->getSheetNames());

        [$june, $july, $eot] = $this->sheetsFromBinary($binary);

        $this->assertNotNull($june, 'June set sheet missing');
        $this->assertNotNull($july, 'July set sheet missing');
        $this->assertNotNull($eot, 'Counting set sheet missing');

        $juneRows = $june->toArray();
        $julyRows = $july->toArray();
        $eotRows = $eot->toArray();

        $this->assertSame(['STUDENT NAME', 'ENGLISH', 'MATHEMATICS'], $juneRows[0]);
        $this->assertSame(['STUDENT NAME', 'ENGLISH', 'MATHEMATICS'], $julyRows[0]);
        $this->assertSame(['STUDENT NAME', 'ENGLISH', 'MATHEMATICS', 'DIVISION'], $eotRows[0]);

        $this->assertCount(4, $juneRows);
        $this->assertSame('Alice A', $juneRows[1][0]);
        $this->assertEquals(45, $juneRows[1][1]);
        $this->assertEquals(50, $juneRows[1][2]);

        $this->assertSame('Bob B', $juneRows[2][0]);
        $this->assertEquals(40, $juneRows[2][1]);
        $this->assertEquals(42, $juneRows[2][2]);

        $this->assertSame('Charlie C', $juneRows[3][0]);
        $this->assertSame('–', $juneRows[3][1]);
        $this->assertSame('–', $juneRows[3][2]);

        $this->assertCount(4, $julyRows);
        $this->assertSame('Alice A', $julyRows[1][0]);
        $this->assertEquals(48, $julyRows[1][1]);
        $this->assertSame('–', $julyRows[1][2]);

        $this->assertSame('Bob B', $julyRows[2][0]);
        $this->assertEquals(44, $julyRows[2][1]);
        $this->assertEquals(46, $julyRows[2][2]);

        $this->assertSame('Charlie C', $julyRows[3][0]);
        $this->assertSame('–', $julyRows[3][1]);
        $this->assertSame('–', $julyRows[3][2]);

        $this->assertCount(4, $eotRows);
        $this->assertEquals(['Alice A', 60, 65], array_slice($eotRows[1], 0, 3));
        $this->assertEquals(['Bob B', 58, 61], array_slice($eotRows[2], 0, 3));
        $this->assertSame('Charlie C', $eotRows[3][0]);
        $this->assertSame('–', $eotRows[3][1]);
        $this->assertSame('–', $eotRows[3][2]);

        $allNames = array_column(array_slice($eotRows, 1), 0);
        $this->assertNotContains('Dave D', $allNames);
    }

    public function test_admin_route_returns_combined_download(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.cards.combinedMarksheet', $this->stdLink))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_class_teacher_and_subject_teacher_can_download_but_unassigned_cannot(): void
    {
        $this->actingAs($this->classTeacher)
            ->get(route('teacher.exam.combinedMarksheet', $this->stdLink))
            ->assertOk();

        $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.exam.combinedMarksheet', $this->stdLink))
            ->assertOk();

        $this->actingAs($this->otherTeacher)
            ->get(route('teacher.exam.combinedMarksheet', $this->stdLink))
            ->assertForbidden();
    }

    public function test_subject_ordering_matches_report_card_order(): void
    {
        $subjects = collect([
            new Subject(['name' => 'Social Studies']),
            new Subject(['name' => 'Reading and Response']),
            new Subject(['name' => 'Literacy I']),
            new Subject(['name' => 'Science']),
            new Subject(['name' => 'Mathematics']),
            new Subject(['name' => 'English']),
            new Subject(['name' => 'Literacy II']),
        ]);

        $ordered = Subject::sortByReportOrder($subjects)->map(fn ($s) => $s->name)->all();

        $this->assertSame([
            'ENGLISH',
            'MATHEMATICS',
            'LITERACY I',
            'LITERACY II',
            'READING AND RESPONSE',
            'SCIENCE',
            'SOCIAL STUDIES',
        ], $ordered);
    }

}
