<?php

namespace Tests\Feature\Marks;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Marks\ExamMarkWriter;
use App\Services\Reports\MarkCellText;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A missing mark is a dash. ABS belongs to attendance, never to marks.
 * The stored row for every no-mark token is the old absent shape: marks NULL,
 * grade AB, absent 1 — so totals, aggregate and division do not change.
 */
class MarksDashNotAbsTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;

    protected AcademicYear $year;

    protected AcademicTerm $term;

    protected Section $section;

    protected Standard $standard;

    protected Subject $subject;

    protected Exam $exam;

    protected User $teacher;

    protected User $admin;

    protected User $amina;

    protected User $brian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Dash School', 'slug' => 'dash-'.uniqid(), 'email' => uniqid().'@t.ug',
            'phone' => '0700000100', 'status' => 1,
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term 1', 'status' => 'current', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
        ]);
        $this->standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $this->section = Section::create(['school_id' => $this->school->id, 'name' => 'Primary Five', 'status' => 1]);
        $link = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'status' => 1,
        ]);
        $this->subject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id,
            'name' => 'Mathematics', 'code' => 'MAT', 'type' => 'core', 'status' => 1,
        ]);
        $type = ExamType::create(['name' => 'End of Term', 'code' => 'EOT', 'contributes_to_report_total' => true]);
        $this->teacher = $this->person(5, 'Maths Teacher');
        $this->admin = $this->person(3, 'School Admin');
        $this->amina = $this->person(6, 'Amina Dash', 'Amina', 'Dash');
        $this->brian = $this->person(6, 'Brian Dash', 'Brian', 'Dash');
        foreach ([$this->amina, $this->brian] as $student) {
            StudentAcademic::create([
                'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
                'user_id' => $student->id, 'standardLink_id' => $link->id,
            ]);
        }
        $this->exam = Exam::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'academic_term_id' => $this->term->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id, 'exam_type_id' => $type->id, 'status' => 'undone',
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id, 'standard_id' => $this->standard->id,
            'grade' => 'A', 'points' => 1, 'min_score' => 0, 'max_score' => 100, 'remark' => 'Good',
        ]);
    }

    private function person(int $group, string $name, ?string $first = null, ?string $last = null): User
    {
        $user = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => $group, 'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@t.ug',
            'password' => bcrypt('secret'),
        ]);
        Userprofile::create([
            'school_id' => $this->school->id, 'user_id' => $user->id, 'usergroup_id' => $group,
            'firstname' => $first ?? $name, 'lastname' => $last ?? 'User',
        ]);

        return $user;
    }

    /** @return array<int, string> */
    public static function noMarkTokens(): array
    {
        return [
            'hyphen' => ['-'],
            'en dash' => ['–'],
            'em dash' => ['—'],
            'blank' => [''],
            'legacy abs' => ['ABS'],
            'legacy ab' => ['AB'],
        ];
    }

    #[DataProvider('noMarkTokens')]
    public function test_every_no_mark_token_writes_the_legacy_absent_row(string $token): void
    {
        $writer = app(ExamMarkWriter::class);
        $writer->save($this->teacher, $this->exam, $this->brian->id, $token);

        $row = Marks::query()->where('student_id', $this->brian->id)->firstOrFail();
        $this->assertNull($row->marks, 'a no-mark token must not store a number');
        $this->assertSame('AB', $row->grade);
        $this->assertTrue($row->isAbsent());
        $this->assertSame('–', $row->displayMark());
    }

    public function test_a_number_is_untouched_by_the_no_mark_tokens(): void
    {
        app(ExamMarkWriter::class)->save($this->teacher, $this->exam, $this->amina->id, 80);

        $row = Marks::query()->where('student_id', $this->amina->id)->firstOrFail();
        $this->assertSame('80', (string) $row->marks);
        $this->assertFalse($row->isAbsent());
        $this->assertSame('80', $row->displayMark());
    }

    public function test_no_mark_tokens_do_not_change_the_total_aggregate_or_division(): void
    {
        $writer = app(ExamMarkWriter::class);
        $writer->save($this->teacher, $this->exam, $this->amina->id, 80);
        $writer->save($this->teacher, $this->exam, $this->brian->id, '–');

        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $this->school->id,
            sectionId: (int) $this->section->id,
            academicTermId: (int) $this->term->id,
            examTypeId: (int) $this->exam->exam_type_id,
        ));
        $rows = collect($sheet->students)->keyBy(fn ($student) => $student->studentId);

        $this->assertEquals(80.0, $rows[$this->amina->id]->total);
        $this->assertEquals(0.0, $rows[$this->brian->id]->total, 'a dash counts as 0, exactly as ABS did');
        $this->assertSame(ResultCell::ABSENT, $rows[$this->brian->id]->cell((int) $this->subject->id)->status);
        $this->assertSame('–', $rows[$this->brian->id]->cell((int) $this->subject->id)->sheetLabel());
        $this->assertSame('–', ResultCell::printedMark($rows[$this->brian->id]->cell((int) $this->subject->id)));
    }

    public function test_the_words_a_card_prints_are_a_dash(): void
    {
        $missing = new ResultCell((int) $this->subject->id, null, null, null, ResultCell::MISSING);
        $absent = new ResultCell((int) $this->subject->id, null, 'AB', null, ResultCell::ABSENT);

        $this->assertSame('–', $missing->sheetLabel());
        $this->assertSame('–', $absent->sheetLabel());
        $this->assertSame('–', MarkCellText::word($missing));
        $this->assertSame('–', MarkCellText::word($absent));
        $this->assertSame('–', MarkCellText::word(null));
    }

    public function test_the_entry_screen_offers_a_dash_and_never_the_word_abs(): void
    {
        app(ExamMarkWriter::class)->save($this->teacher, $this->exam, $this->brian->id, '–');

        $html = $this->actingAs($this->teacher)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-testid="no-mark-toggle"', $html);
        $this->assertStringNotContainsString('data-testid="abs-toggle"', $html);
        $this->assertStringContainsString('Type a whole number, or – when there is no mark.', $html);
        $this->assertStringContainsString('value="–"', $html);
        $this->assertStringContainsString('data-saved="–"', $html);
        // Visible text only: the JS token list keeps legacy ABS/AB for old rows.
        $this->assertStringNotContainsString('>ABS<', $html, 'no marks screen may print ABS');
        $this->assertStringNotContainsString('Type ABS', $html);
        $this->assertStringNotContainsString('A mark or ABS', $html);
        $this->assertStringNotContainsString('>Missing<', $html, 'no marks screen may print Missing');
    }

    public function test_the_admin_sheet_prints_a_dash_for_a_learner_with_no_mark(): void
    {
        app(ExamMarkWriter::class)->save($this->teacher, $this->exam, $this->brian->id, '–');

        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $this->school->id,
            sectionId: (int) $this->section->id,
            academicTermId: (int) $this->term->id,
            examTypeId: (int) $this->exam->exam_type_id,
        ));
        $row = collect($sheet->students)->firstWhere('studentId', (int) $this->brian->id);
        $cell = $row->cell((int) $this->subject->id);

        $html = view('admin.marks.sheet-cell', ['cell' => $cell, 'setReleased' => false])->render();
        $this->assertStringContainsString('–', $html);
        $this->assertStringNotContainsString('ABS', $html);
        $this->assertStringNotContainsString('Missing', $html);

        $this->actingAs($this->admin)->get(route('admin.marks.filter', [
            'class' => $this->section->id,
            'term' => $this->term->id,
            'examType' => $this->exam->exam_type_id,
        ]))->assertOk();
    }
}
