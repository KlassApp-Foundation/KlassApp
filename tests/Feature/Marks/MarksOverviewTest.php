<?php

namespace Tests\Feature\Marks;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarksOverviewTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicYear $olderYear;

    private AcademicTerm $term;

    private AcademicTerm $earlierTerm;

    private AcademicTerm $oldTerm;

    private Section $section;

    private Section $otherSection;

    private User $admin;

    private User $ada;

    private User $bea;

    private Subject $english;

    private Subject $reading;

    private Subject $science;

    private ExamType $beginning;

    private ExamType $mid;

    private ExamType $end;

    private Exam $englishMid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Harbour Marks Overview School',
            'slug' => 'harbour-marks-overview',
            'email' => 'harbour-marks@overview.test',
            'phone' => '+256700001212',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
        ]);
        $other = School::create([
            'name' => 'Other Harbour Marks School',
            'slug' => 'other-harbour-marks',
            'email' => 'other-harbour@overview.test',
            'phone' => '+256700001213',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
        ]);

        $this->olderYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2024',
            'description' => 'Older year',
            'start_date' => '2024-01-06',
            'end_date' => '2024-12-20',
            'status' => 0,
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Newer year',
            'start_date' => '2026-02-02',
            'end_date' => '2026-12-04',
            'status' => 1,
        ]);
        $this->oldTerm = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->olderYear->id,
            'name' => 'Old term',
            'starts_on' => '2024-02-01',
            'ends_on' => '2024-04-30',
            'status' => 'last',
        ]);
        $this->earlierTerm = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'starts_on' => '2026-02-02',
            'ends_on' => '2026-04-30',
            'status' => 'last',
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 3',
            'starts_on' => '2026-09-07',
            'ends_on' => '2026-12-04',
            'status' => 'current',
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.5',
            'status' => 1,
        ]);
        $this->otherSection = Section::create([
            'school_id' => $other->id,
            'name' => 'P.5',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'stream' => 'East',
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Harbour Admin',
            'email' => 'admin@harbour-marks.test',
            'registration_number' => 'KLS0010099',
        ]);
        $this->ada = $this->learner('Ada Harbour', 'KLS0010001', $link);
        $this->bea = $this->learner('Bea Harbour', 'KLS0010002', $link);

        $this->english = $this->subject($standard, 'English', 'ENG');
        $this->reading = $this->subject($standard, 'Reading', 'READ');
        $this->science = $this->subject($standard, 'Science', 'SCI');

        $this->beginning = ExamType::create([
            'name' => ' Beginning of Term ',
            'code' => 'BOT',
            'contributes_to_report_total' => 0,
        ]);
        $this->mid = ExamType::create([
            'name' => 'MID TERM',
            'code' => 'MID',
            'contributes_to_report_total' => 0,
        ]);
        $this->end = ExamType::create([
            'name' => 'End of Term',
            'code' => 'EOT',
            'contributes_to_report_total' => 1,
        ]);
        ExamType::create([
            'name' => 'Quiz',
            'code' => 'QUIZ',
            'contributes_to_report_total' => 0,
        ]);

        $englishBot = $this->exam($standard, $this->english, $this->beginning, '2026-09-08');
        MarksRelease::create([
            'school_id' => $this->school->id,
            'exam_id' => $englishBot->id,
            'scope' => 'school',
            'scheduled_at' => now()->subHour(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'timezone' => 'UTC',
        ]);
        $this->englishMid = $this->exam($standard, $this->english, $this->mid, '2026-10-08');
        $this->exam($standard, $this->reading, $this->mid, '2026-10-09');
        $scienceMid = $this->exam($standard, $this->science, $this->mid, '2026-10-10');
        $this->exam($standard, $this->english, $this->end, '2026-11-12');

        $this->mark($englishBot, $this->english, $this->ada, 61, '');
        $this->mark($this->englishMid, $this->english, $this->ada, 50, '');
        $this->mark($this->englishMid, $this->english, $this->bea, null, 'AB', true);
        $readingMid = Exam::query()->where('subject_id', $this->reading->id)->first();
        $this->mark($readingMid, $this->reading, $this->ada, 77, '');
        $this->mark($scienceMid, $this->science, $this->ada, 91, '');

        $this->submission($englishBot, $this->english, 'submitted', 'approved');
        $this->submission($this->englishMid, $this->english, 'submitted', 'approved');
        $this->submission($readingMid, $this->reading, 'submitted', 'pending');
        $this->submission($scienceMid, $this->science, 'draft', 'pending');

        $this->assertSame('active', $this->ada->status);
        $this->assertSame('active', $this->admin->status);
    }

    public function test_tabs_run_beginning_mid_end_and_switch_the_sheet(): void
    {
        $school = $this->school->fresh();
        $this->assertSame('preview', $school->toshi_mode->value);
        $this->assertSame(0, (int) $school->toshi_enabled);

        $html = $this->page();

        preg_match('/<select name="year" id="year".*?<\/select>/s', $html, $yearSelect);
        preg_match('/<select name="term" id="term".*?<\/select>/s', $html, $termSelect);
        $this->assertLessThan(strpos($yearSelect[0], '2024'), strpos($yearSelect[0], '2026'));
        $this->assertLessThan(strpos($termSelect[0], 'Term 1'), strpos($termSelect[0], 'Term 3'));
        $this->assertStringNotContainsString('Old term', $termSelect[0]);

        preg_match_all('/data-exam-set="([^"]+)"/', $html, $sets);
        $this->assertSame(['beginning of term', 'mid term', 'end of term'], $sets[1]);
        $this->assertMatchesRegularExpression(
            '/data-exam-set="mid term"[^>]*aria-current="page"/',
            $html
        );
        $this->assertStringNotContainsString('data-exam-set="quiz"', $html);
        $this->assertMatchesRegularExpression('/data-exam-set="beginning of term"[^>]*data-tab-status="released"/', $html);
        $this->assertMatchesRegularExpression('/data-exam-set="mid term"[^>]*data-tab-status="approving"/', $html);
        $this->assertMatchesRegularExpression('/data-exam-set="end of term"[^>]*data-tab-status="coming"/', $html);
        $this->assertStringContainsString('>Released<', $html);
        $this->assertStringContainsString('>Approving<', $html);
        $this->assertStringContainsString('>Marks coming in<', $html);
        $this->assertStringContainsString('>Coming in<', $html);
        $this->assertStringContainsString('aria-label="Locked"', $html);
        $this->assertStringContainsString('Term 3 · current', $html);
        $this->assertStringContainsString('P.5 · all streams', $html);
        $this->assertStringContainsString('KLS number', $html);
        $this->assertStringContainsString('KLS0010001', $html);
        $this->assertStringContainsString('ABS and Missing count as 0', $html);
        $this->assertStringContainsString($this->science->name.' is saved but not submitted.', $html);
        $this->assertStringNotContainsString('Social Studies', $html);
        $this->assertStringNotContainsString('Further down', $html);
        $this->assertStringContainsString('/admin/marks/history?', $html);
        $this->assertStringContainsString('section='.$this->section->id, $html);
        $this->assertStringContainsString('ka-tile-abs', $html);
        $this->assertStringContainsString('>ABS<', $html);
        $this->assertStringNotContainsString('data-testid="marks-other-exam"', $html);
        $this->assertStringNotContainsString('>Other exam<', $html);
        $this->assertMatchesRegularExpression(
            '/href="[^"]*examType='.$this->beginning->id.'[^"]*"[^>]*data-exam-set="beginning of term"/',
            $html
        );

        $beginning = $this->page(['examType' => $this->beginning->id]);
        $beginningCells = $this->innerCells($beginning, $this->english->id);
        $this->assertTrue($this->cellHas($beginningCells, '61'));
        $this->assertFalse($this->cellHas($beginningCells, '50'));
        $this->assertTrue($this->cellHas($beginningCells, 'Missing'));
        $this->assertStringContainsString('data-testid="marks-cell-missing"', $beginning);
        $this->assertMatchesRegularExpression(
            '/data-exam-set="beginning of term"[^>]*aria-current="page"/',
            $beginning
        );
    }

    public function test_unsubmitted_marks_are_dashes_and_only_approved_subjects_count(): void
    {
        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $this->school->id,
            sectionId: (int) $this->section->id,
            academicTermId: (int) $this->term->id,
            examTypeId: (int) $this->mid->id,
            countApprovedSubjects: true,
        ));
        $ada = $sheet->student($this->ada->id);
        $bea = $sheet->student($this->bea->id);
        $this->assertSame(50.0, $ada->displayTotal);
        $this->assertSame(1, $ada->classPosition);
        $this->assertSame(1, $ada->classOutOf);
        $this->assertSame(ResultCell::MISSING, $ada->cell($this->science->id)->status);
        $this->assertSame(ResultCell::MARKED, $ada->cell($this->reading->id)->status);
        $this->assertSame(77.0, $ada->cell($this->reading->id)->mark);
        $this->assertNull($bea->displayTotal);
        $this->assertNull($bea->classPosition);
        $this->assertSame(ResultCell::ABSENT, $bea->cell($this->english->id)->status);
        $this->assertSame('AB', $bea->cell($this->english->id)->grade);

        $html = $this->page();
        $science = $this->innerCells($html, $this->science->id);
        $this->assertNotEmpty($science);
        foreach ($science as $cell) {
            $this->assertStringContainsString('—', $cell);
            $this->assertStringNotContainsString('91', $cell);
            $this->assertStringNotContainsString('Missing', $cell);
        }
        $this->assertStringNotContainsString('marks-cell-missing', $html);
        $this->assertTrue($this->cellHas($this->innerCells($html, $this->reading->id), '77'));
        $this->assertMatchesRegularExpression('/data-testid="marks-phone-total">\s*50\s*</', $html);
        $this->assertDoesNotMatchRegularExpression('/data-testid="marks-phone-total">\s*Total/', $html);
        $this->assertStringNotContainsString('Total 127', $html);
        $this->assertStringNotContainsString('Total 218', $html);
        $this->assertStringContainsString('>ABS<', $html);
        $this->assertStringNotContainsString('marks-cell-absent">AB<', $html);
        $this->assertSame(
            'AB',
            Marks::query()->where('student_id', $this->bea->id)->where('subject_id', $this->english->id)->value('grade')
        );
    }

    public function test_the_phone_card_includes_the_kls_number_and_subjects(): void
    {
        $html = $this->page();
        $start = strpos($html, 'data-testid="marks-phone-card"');
        $end = strpos($html, '</article>', $start);
        $card = substr($html, $start, $end - $start);

        $this->assertStringContainsString('KLS0010001', $card);
        $this->assertStringContainsString('KLS0010001 · East', $card);
        $this->assertStringContainsString('ka-learner-pos', $card);
        $this->assertMatchesRegularExpression('/data-testid="marks-phone-position">\s*of 1\s*</', $card);
        $this->assertStringContainsString($this->english->name, $card);
        $this->assertStringContainsString($this->reading->name, $card);
        $this->assertStringContainsString($this->science->name, $card);
        $this->assertStringContainsString('data-testid="marks-kls"', $card);
        $this->assertStringContainsString('data-testid="marks-phone-card"', $card);
        $this->assertStringNotContainsString('overflow-x:auto', $card);
    }

    public function test_another_schools_class_is_refused(): void
    {
        $this->actingAs($this->admin)->get(route('admin.marks.filter', [
            'year' => $this->year->id,
            'term' => $this->term->id,
            'class' => $this->otherSection->id,
            'examType' => $this->mid->id,
        ]))->assertNotFound();
    }

    public function test_a_missing_selection_stays_empty_and_a_mismatched_year_is_an_error(): void
    {
        $empty = $this->actingAs($this->admin)->get(route('admin.marks'))->assertOk()->getContent();
        $this->assertStringContainsString('Ready when you are', $empty);
        $this->assertStringNotContainsString('data-testid="marks-sheet-error"', $empty);
        $this->assertStringContainsString('data-ka-filters', $empty);
        $scripts = strpos($empty, '<!-- Scripts -->');
        $listener = strpos($empty, "field.form.classList.contains('ka-sheet-filters')");
        $this->assertNotFalse($scripts);
        $this->assertNotFalse($listener);
        $this->assertGreaterThan($scripts, $listener);

        $this->actingAs($this->admin)->get(route('admin.marks.filter', [
            'year' => 999999,
            'term' => $this->term->id,
            'class' => $this->section->id,
            'examType' => $this->mid->id,
        ]))->assertNotFound();

        $error = $this->actingAs($this->admin)->get(route('admin.marks.filter', [
            'year' => $this->year->id,
            'term' => $this->oldTerm->id,
            'class' => $this->section->id,
            'examType' => $this->mid->id,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('data-testid="marks-sheet-error"', $error);
        $this->assertStringContainsString('That term is not in the chosen year.', $error);
        $this->assertStringNotContainsString('Ada Harbour', $error);
        $this->assertStringNotContainsString('Ready when you are', $error);
    }

    public function test_show_20_more_uses_the_paginator(): void
    {
        $linkId = StudentAcademic::query()->where('user_id', $this->ada->id)->value('standardLink_id');
        $link = StandardLink::query()->find($linkId);
        for ($n = 3; $n <= 20; $n++) {
            $this->learner('Harbour Learner '.$n, sprintf('KLS001%04d', $n), $link);
        }
        $this->learner('Zed Pageedge', 'KLS0010021', $link);

        $html = $this->page();
        $this->assertStringContainsString('Show 20 more', $html);
        $this->assertStringNotContainsString('Zed Pageedge', $html);

        $next = $this->page(['page' => 2]);
        $this->assertStringContainsString('Zed Pageedge', $next);
        $this->assertStringNotContainsString('Show 20 more', $next);
    }

    /**
     * @param array<string, int> $extra
     */
    private function page(array $extra = []): string
    {
        return $this->actingAs($this->admin)->get(route('admin.marks.filter', array_merge([
            'year' => $this->year->id,
            'term' => $this->term->id,
            'class' => $this->section->id,
            'examType' => $this->mid->id,
        ], $extra)))->assertOk()->getContent();
    }

    private function learner(string $name, string $kls, StandardLink $link): User
    {
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@harbour-marks.test',
            'registration_number' => $kls,
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $user->id,
            'standardLink_id' => $link->id,
        ]);

        return $user;
    }

    private function subject(Standard $standard, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'name' => $name,
            'code' => $code,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function exam(Standard $standard, Subject $subject, ExamType $type, string $when): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->admin->id,
            'exam_type_id' => $type->id,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => $when,
            'status' => 'undone',
        ]));
    }

    private function mark(Exam $exam, Subject $subject, User $student, ?int $score, string $grade, bool $absent = false): void
    {
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->admin->id,
            'section_id' => $this->section->id,
            'marks' => $score,
            'grade' => $grade,
            'absent' => $absent,
        ]);
    }

    private function submission(Exam $exam, Subject $subject, string $status, ?string $approval): void
    {
        ExamMarksSubmission::create([
            'school_id' => $this->school->id,
            'exam_id' => $exam->id,
            'class_id' => $this->section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->admin->id,
            'status' => $status,
            'approval_status' => $approval,
        ]);
    }

    /**
     * @return list<string>
     */
    private function innerCells(string $html, int $subjectId): array
    {
        preg_match_all('/data-subject="'.$subjectId.'"[^>]*>(.*?)<\/(td|dd)>/s', $html, $matches);

        return $matches[1] ?? [];
    }

    /**
     * @param list<string> $cells
     */
    private function cellHas(array $cells, string $needle): bool
    {
        foreach ($cells as $cell) {
            if (str_contains($cell, $needle)) {
                return true;
            }
        }

        return false;
    }
}
