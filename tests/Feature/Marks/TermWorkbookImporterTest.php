<?php

namespace Tests\Feature\Marks;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Marks\TermWorkbookImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TermWorkbookImporterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $head;

    private User $classTeacher;

    private User $subjectTeacher;

    private Section $primaryFour;

    private Section $primaryFive;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = $this->makeSchool('Harbour Workbook School');
        $year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'name' => 'Term 1', 'status' => 'current',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);

        $this->head = User::factory()->create([
            'usergroup_id' => 4, 'school_id' => $this->school->id, 'name' => 'Head Harbour',
        ]);
        $this->classTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Class Harbour',
        ]);
        $this->subjectTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Subject Harbour',
        ]);

        $this->primaryFour = $this->makeClass($year, $standard, 'Primary Four');
        $this->primaryFive = $this->makeClass($year, $standard, 'Primary Five');
        $this->makeSubject($year, $standard, $this->primaryFour, 'English');
        $this->makeSubject($year, $standard, $this->primaryFour, 'Mathematics');
        $this->makeSubject($year, $standard, $this->primaryFive, 'English');

        foreach (['Beginning of Term' => 'BOT', 'Mid Term' => 'MID', 'End of Term' => 'EOT'] as $name => $code) {
            ExamType::create([
                'school_id' => $this->school->id,
                'name' => $name,
                'code' => $code.'-'.$this->school->id,
                'contributes_to_report_total' => $code === 'EOT' ? 1 : 0,
            ]);
        }

        $this->enrol($year, $standard, $this->primaryFour, 'Amina', 'North', 'KLS0010001');
        $this->enrol($year, $standard, $this->primaryFour, 'Bea', 'Stone', 'KLS0010002');
        $this->enrol($year, $standard, $this->primaryFour, 'Sam', 'Lee', 'KLS0010003');
        $this->enrol($year, $standard, $this->primaryFour, 'Sam', 'Lee', 'KLS0010004');
        $this->enrol($year, $standard, $this->primaryFour, 'Ada', 'Lovelace', 'KLS0010006');
        $this->enrol($year, $standard, $this->primaryFive, 'Cara', 'West', 'KLS0010005');
    }

    public function test_a_clean_workbook_creates_drafts_and_a_second_run_changes_nothing(): void
    {
        $path = $this->workbook($this->school->name, [
            'Primary Four' => [
                ['KLS0010001', 'Amina North', '70', '', '81'],
                ['', 'Bea Stone', 'ABS', '', ''],
            ],
        ]);

        $first = app(TermWorkbookImporter::class)->import($this->head, $path);

        $this->assertSame(0, $first['submitted']);
        $this->assertFalse($first['composition_applied']);
        $this->assertSame(2, $first['counts']['exams_created']);
        $this->assertSame(3, $first['counts']['written']);
        $this->assertSame(1, $first['counts']['blank']);
        $this->assertSame(0, ExamMarksSubmission::query()->count());
        $this->assertSame(0, Exam::query()->where('status', 'submitted')->count());

        $amina = User::query()->where('registration_number', 'KLS0010001')->firstOrFail();
        $bea = User::query()->where('registration_number', 'KLS0010002')->firstOrFail();
        $english = $this->examId('English', 'Beginning of Term');
        $mid = $this->examId('English', 'Mid Term');

        $this->assertSame(70, (int) Marks::query()->where('exam_id', $english)->where('student_id', $amina->id)->value('marks'));
        $this->assertTrue((bool) Marks::query()->where('exam_id', $english)->where('student_id', $bea->id)->value('absent'));
        $this->assertNull(Marks::query()->where('exam_id', $english)->where('student_id', $bea->id)->value('marks'));
        $this->assertSame('AB', Marks::query()->where('exam_id', $english)->where('student_id', $bea->id)->value('grade'));
        $this->assertNull(Marks::query()->where('exam_id', $mid)->where('student_id', $bea->id)->first());
        $this->assertSame(81, (int) Marks::query()->where('exam_id', $mid)->where('student_id', $amina->id)->value('marks'));

        $log = DB::table('activity_log')->where('description', 'Term workbook imported')->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('Amina', (string) $log->properties);
        $this->assertStringNotContainsString('KLS0010001', (string) $log->properties);

        $second = app(TermWorkbookImporter::class)->import($this->head, $path);
        $this->assertSame(0, $second['counts']['written']);
        $this->assertSame(0, $second['counts']['updated']);
        $this->assertSame(0, $second['counts']['exams_created']);
        $this->assertGreaterThan(0, $second['counts']['unchanged']);
        $this->assertSame(2, Exam::query()->where('school_id', $this->school->id)->count());

        unlink($path);
    }

    public function test_abs_empty_range_unknowns_and_the_name_fallback_are_reported(): void
    {
        $path = $this->workbook($this->school->name, [
            'Primary Four' => [
                ['KLS0010001', 'Amina North', '150', '', 'hello'],
                ['', 'Bea   Stone', 'abs', '', ''],
                ['KLS0099999', 'Nobody', '10', '', ''],
                ['', 'Sam Lee', '40', '', ''],
                ['', 'Sam Lea', '41', '', ''],
                ['KLS0010006', 'Ada Lovelace', '20', '', ''],
                ['KLS0010006', 'Ada Lovelace', '21', '', ''],
            ],
            'Not A Class' => [
                ['KLS0010001', 'Amina North', '10', '', ''],
            ],
            'Primary Five' => [
                ['KLS0010005', 'Cara West', '55', '', ''],
            ],
        ]);

        $result = app(TermWorkbookImporter::class)->import($this->head, $path);

        $this->assertNotEmpty($this->reasons($result, 'out_of_range'));
        $this->assertNotEmpty($this->reasons($result, 'not_a_number'));
        $this->assertNotEmpty($this->reasons($result, 'unknown_subject'));
        $this->assertNotEmpty($this->reasons($result, 'unknown_class'));
        $this->assertNotEmpty($this->reasons($result, 'unknown_student'));
        $this->assertNotEmpty($this->reasons($result, 'ambiguous'));
        $this->assertNotEmpty($this->reasons($result, 'no_match'));
        $this->assertNotEmpty($this->reasons($result, 'duplicate_in_file'));
        $this->assertSame(0, Subject::query()->where('name', 'Astronomy')->count());
        $this->assertSame(0, Section::query()->where('name', 'Not A Class')->count());

        $bea = User::query()->where('registration_number', 'KLS0010002')->firstOrFail();
        $english = $this->examId('English', 'Beginning of Term');
        $this->assertTrue((bool) Marks::query()->where('exam_id', $english)->where('student_id', $bea->id)->value('absent'));
        $this->assertNull(Marks::query()->where('exam_id', $english)->where('student_id', $bea->id)->value('marks'));
        $this->assertSame(0, Marks::query()->where('student_id', $bea->id)->where('marks', 150)->count());
        $amina = User::query()->where('registration_number', 'KLS0010001')->firstOrFail();
        $this->assertNull(Marks::query()->where('student_id', $amina->id)->first());

        $cara = User::query()->where('registration_number', 'KLS0010005')->firstOrFail();
        $this->assertSame(55, (int) Marks::query()->where('student_id', $cara->id)->value('marks'));
        $this->assertSame(0, ExamMarksSubmission::query()->count());

        unlink($path);
    }

    public function test_a_changed_mark_updates_and_a_removed_row_stays(): void
    {
        $first = $this->workbook($this->school->name, [
            'Primary Four' => [
                ['KLS0010001', 'Amina North', '70', '', ''],
            ],
        ]);
        app(TermWorkbookImporter::class)->import($this->head, $first);

        $changed = $this->workbook($this->school->name, [
            'Primary Four' => [
                ['KLS0010001', 'Amina North', '75', '', ''],
            ],
        ]);
        $updated = app(TermWorkbookImporter::class)->import($this->head, $changed);
        $change = $this->reasons($updated, 'updated');
        $this->assertCount(1, $change);
        $this->assertSame('70', $change[0]['old']);
        $this->assertSame('75', $change[0]['new']);

        $amina = User::query()->where('registration_number', 'KLS0010001')->firstOrFail();
        $english = $this->examId('English', 'Beginning of Term');
        $this->assertSame(75, (int) Marks::query()->where('exam_id', $english)->where('student_id', $amina->id)->value('marks'));

        $removed = $this->workbook($this->school->name, [
            'Primary Four' => [
                ['KLS0010002', 'Bea Stone', '60', '', ''],
            ],
        ]);
        $left = app(TermWorkbookImporter::class)->import($this->head, $removed);
        $kept = array_values(array_filter(
            $this->reasons($left, 'left_in_place'),
            fn (array $row) => (int) $row['student_id'] === (int) $amina->id,
        ));
        $this->assertNotEmpty($kept);
        $this->assertSame(75, (int) Marks::query()->where('exam_id', $english)->where('student_id', $amina->id)->value('marks'));

        unlink($first);
        unlink($changed);
        unlink($removed);
    }

    public function test_a_subject_teacher_and_another_school_are_refused(): void
    {
        $path = $this->workbook($this->school->name, [
            'Primary Four' => [
                ['KLS0010001', 'Amina North', '70', '', ''],
            ],
        ]);

        try {
            app(TermWorkbookImporter::class)->import($this->subjectTeacher, $path);
            $this->fail('A subject teacher was allowed to import.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame(0, Exam::query()->count());

        $other = $this->makeSchool('Other Harbour School');
        $otherAdmin = User::factory()->create([
            'usergroup_id' => 3, 'school_id' => $other->id, 'name' => 'Other Admin',
        ]);

        try {
            app(TermWorkbookImporter::class)->import($otherAdmin, $path);
            $this->fail('Another school was allowed to import.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('school', $exception->errors());
        }
        $this->assertSame(0, Exam::query()->count());
        $this->assertSame(0, Marks::query()->count());

        $classPath = $this->workbook($this->school->name, [
            'Primary Four' => [
                ['KLS0010001', 'Amina North', '64', '', ''],
            ],
            'Primary Five' => [
                ['KLS0010005', 'Cara West', '90', '', ''],
            ],
        ]);
        $scoped = app(TermWorkbookImporter::class)->import($this->classTeacher, $classPath);
        $this->assertNotEmpty($this->reasons($scoped, 'not_your_class'));
        $amina = User::query()->where('registration_number', 'KLS0010001')->firstOrFail();
        $cara = User::query()->where('registration_number', 'KLS0010005')->firstOrFail();
        $this->assertSame(64, (int) Marks::query()->where('student_id', $amina->id)->value('marks'));
        $this->assertNull(Marks::query()->where('student_id', $cara->id)->first());

        unlink($path);
        unlink($classPath);
    }

    private function examId(string $subject, string $type): int
    {
        return (int) Exam::query()
            ->where('school_id', $this->school->id)
            ->where('section_id', $this->primaryFour->id)
            ->where('subject_id', Subject::query()->where('section_id', $this->primaryFour->id)->where('name', $subject)->value('id'))
            ->where('exam_type_id', ExamType::query()->where('school_id', $this->school->id)->where('name', $type)->value('id'))
            ->value('id');
    }

    /**
     * @param  array<string, list<list<string>>>  $sheets
     */
    private function workbook(string $school, array $sheets): string
    {
        $spreadsheet = new Spreadsheet;
        $setup = $spreadsheet->getActiveSheet();
        $setup->setTitle('Setup');
        $setup->setCellValue('A3', 'School');
        $setup->setCellValue('B3', $school);
        $setup->setCellValue('A4', 'Academic year');
        $setup->setCellValue('B4', '2026');
        $setup->setCellValue('A5', 'Term');
        $setup->setCellValue('B5', 'Term 1');
        $setup->setCellValue('B7', 'Name (as in KlassApp)');
        $setup->setCellValue('C7', 'Out of (marks)');
        $setup->setCellValue('D7', 'On the report card?');
        $setup->setCellValue('E7', 'Weight on the report (%)');
        $setup->setCellValue('B8', 'Beginning of Term');
        $setup->setCellValue('C8', 100);
        $setup->setCellValue('D8', 'Yes');
        $setup->setCellValue('E8', 100);
        $setup->setCellValue('B9', 'Mid Term');
        $setup->setCellValue('C9', 100);
        $setup->setCellValue('D9', 'No');
        $setup->setCellValue('B10', 'End of Term');
        $setup->setCellValue('C10', 100);
        $setup->setCellValue('D10', 'No');

        foreach ($sheets as $title => $rows) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($title);
            $sheet->setCellValue('A1', $title);
            $sheet->setCellValue('C1', 'Beginning of Term (out of 100)');
            $sheet->setCellValue('E1', 'Mid Term (out of 100)');
            $sheet->setCellValue('A2', 'KLS number *');
            $sheet->setCellValue('B2', 'Learner name (to check)');
            $sheet->setCellValue('C2', 'English');
            $sheet->setCellValue('D2', 'Total');
            $sheet->setCellValue('E2', 'English');
            $sheet->setCellValue('F2', 'Astronomy');
            foreach ($rows as $index => $row) {
                foreach ($row as $column => $value) {
                    $sheet->setCellValue([$column + 1, $index + 3], $value);
                }
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'term-workbook').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return list<array<string, mixed>>
     */
    private function reasons(array $result, string $reason): array
    {
        return array_values(array_filter(
            $result['rows'],
            fn (array $row) => $row['reason'] === $reason,
        ));
    }

    private function makeSchool(string $name): School
    {
        return School::create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'email' => uniqid().'@workbook.test',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
        ]);
    }

    private function makeClass(AcademicYear $year, Standard $standard, string $name): Section
    {
        $classTeacherId = $name === 'Primary Four' ? $this->classTeacher->id : null;
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'status' => 1,
            'class_teacher_id' => $classTeacherId,
        ]);
        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $classTeacherId,
            'stream' => 'A',
            'status' => 1,
        ]);

        return $section;
    }

    private function makeSubject(AcademicYear $year, Standard $standard, Section $section, string $name): void
    {
        Subject::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function enrol(AcademicYear $year, Standard $standard, Section $section, string $first, string $last, string $kls): void
    {
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => $first.' '.$last,
            'registration_number' => $kls,
        ]);
        Userprofile::create([
            'user_id' => $student->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => $first,
            'lastname' => $last,
            'status' => 'active',
        ]);
        $linkId = StandardLink::query()->where('section_id', $section->id)->value('id');
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $linkId,
        ]);
    }
}
