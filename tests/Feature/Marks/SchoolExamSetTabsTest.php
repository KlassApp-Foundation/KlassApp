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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolExamSetTabsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Section $section;

    private User $admin;

    private User $ada;

    private Subject $english;

    /** @var array<string, ExamType> */
    private array $sets = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Cedar Quay School',
            'slug' => 'cedar-quay',
            'email' => 'office@cedar-quay.test',
            'phone' => '+256700009191',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Cedar year',
            'start_date' => '2026-02-02',
            'end_date' => '2026-12-04',
            'status' => 1,
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'starts_on' => '2026-02-02',
            'ends_on' => '2026-04-30',
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
            'name' => 'P.4',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'stream' => 'West',
            'status' => 1,
        ]);
        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Cedar Admin',
            'email' => 'admin@cedar-quay.test',
            'registration_number' => 'KLS0020099',
        ]);
        $this->ada = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'Ada Quay',
            'email' => 'ada.quay@cedar-quay.test',
            'registration_number' => 'KLS0020001',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->ada->id,
            'standardLink_id' => $link->id,
        ]);
        $this->english = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);

        $plan = [
            ['North Paper', 'NP', '2026-02-04'],
            ['South Paper', 'SP', '2026-03-03'],
            ['Quiz Hour', 'QH', '2026-03-16'],
            ['Month Check', 'MC', '2026-04-06'],
            ['Late Paper', 'LP', '2026-05-11'],
        ];
        foreach ($plan as [$name, $code, $when]) {
            $type = ExamType::create([
                'school_id' => $this->school->id,
                'name' => $name,
                'code' => $code,
                'contributes_to_report_total' => 0,
            ]);
            $this->sets[$name] = $type;
            $this->sitting($type, $when);
        }

        MarksRelease::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->sittingId('North Paper'),
            'scope' => 'school',
            'scheduled_at' => now()->subHour(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'timezone' => 'UTC',
        ]);
        Marks::create([
            'student_id' => $this->ada->id,
            'exam_id' => $this->sittingId('Quiz Hour'),
            'school_id' => $this->school->id,
            'subject_id' => $this->english->id,
            'teacher_id' => $this->admin->id,
            'section_id' => $this->section->id,
            'marks' => null,
            'grade' => 'AB',
            'absent' => true,
        ]);
        Marks::create([
            'student_id' => $this->ada->id,
            'exam_id' => $this->sittingId('Month Check'),
            'school_id' => $this->school->id,
            'subject_id' => $this->english->id,
            'teacher_id' => $this->admin->id,
            'section_id' => $this->section->id,
            'marks' => 70,
            'grade' => '',
            'absent' => false,
        ]);
        ExamMarksSubmission::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->sittingId('Quiz Hour'),
            'class_id' => $this->section->id,
            'subject_id' => $this->english->id,
            'teacher_id' => $this->admin->id,
            'status' => 'submitted',
            'approval_status' => 'approved',
        ]);
    }

    public function test_tabs_are_the_schools_sets_in_date_order_with_their_own_short_names(): void
    {
        $html = $this->page((int) $this->sets['South Paper']->id);

        preg_match_all('/data-exam-set="([^"]+)"/', $html, $sets);
        $this->assertSame(
            ['north paper', 'south paper', 'quiz hour', 'month check', 'late paper'],
            $sets[1],
        );
        preg_match_all('/class="ka-exam-short">([^<]*)</', $html, $shorts);
        $this->assertSame(['North', 'South', 'Quiz', 'Month', 'Late'], $shorts[1]);
        $this->assertStringNotContainsString('data-testid="marks-other-exam"', $html);

        $css = file_get_contents(public_path('css/ka-screen.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString(
            '.ka-exam-tabs{display:flex;flex-wrap:nowrap;gap:6px;overflow-x:auto;',
            $css,
        );
        $this->assertStringNotContainsString('grid-template-columns:repeat(3,minmax(0,1fr))', $css);
        $this->assertStringContainsString('[data-testid="exams-marks"]{overflow-x:hidden}', $css);
        $this->assertStringNotContainsString('incomplete', strtolower($html));
        $this->assertStringNotContainsString('re-weight', strtolower($html));
        $this->assertStringNotContainsString('workaround', strtolower($html));
    }

    public function test_a_released_set_shows_missing_and_an_open_set_keeps_a_dash(): void
    {
        $released = $this->cells($this->page((int) $this->sets['North Paper']->id));
        $this->assertNotEmpty($released);
        foreach ($released as $cell) {
            $this->assertStringContainsString('data-testid="marks-cell-missing"', $cell);
            $this->assertStringContainsString('Missing', $cell);
            $this->assertStringNotContainsString('—', $cell);
        }

        $open = $this->cells($this->page((int) $this->sets['South Paper']->id));
        $this->assertNotEmpty($open);
        foreach ($open as $cell) {
            $this->assertStringContainsString('—', $cell);
            $this->assertStringNotContainsString('marks-cell-missing', $cell);
            $this->assertStringNotContainsString('Missing', $cell);
        }

        $quiz = $this->cells($this->page((int) $this->sets['Quiz Hour']->id));
        $this->assertNotEmpty($quiz);
        foreach ($quiz as $cell) {
            $this->assertStringContainsString('data-testid="marks-cell-absent">ABS<', $cell);
            $this->assertStringNotContainsString('Missing', $cell);
        }
    }

    private function page(int $examTypeId): string
    {
        return $this->actingAs($this->admin)->get(route('admin.marks.filter', [
            'year' => $this->year->id,
            'term' => $this->term->id,
            'class' => $this->section->id,
            'examType' => $examTypeId,
        ]))->assertOk()->getContent();
    }

    private function sitting(ExamType $type, string $when): void
    {
        Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->english->standard_id,
            'section_id' => $this->section->id,
            'subject_id' => $this->english->id,
            'teacher_id' => $this->admin->id,
            'exam_type_id' => $type->id,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => $when,
            'status' => 'undone',
        ]));
    }

    private function sittingId(string $name): int
    {
        return (int) Exam::query()
            ->where('school_id', $this->school->id)
            ->where('exam_type_id', $this->sets[$name]->id)
            ->value('id');
    }

    /**
     * @return list<string>
     */
    private function cells(string $html): array
    {
        preg_match_all('/data-subject="'.$this->english->id.'"[^>]*>(.*?)<\/(td|dd)>/s', $html, $matches);

        return $matches[1] ?? [];
    }
}
