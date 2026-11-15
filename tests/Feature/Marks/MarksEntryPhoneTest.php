<?php

namespace Tests\Feature\Marks;

use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarksEntryPhoneTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $subjectTeacher;

    private User $peerTeacher;

    private User $student;

    private User $secondStudent;

    private Exam $exam;

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
            'name' => 'Northwind Phone '.uniqid(),
            'slug' => 'northwind-phone-'.uniqid(),
            'email' => uniqid().'@northwind-phone.test',
            'phone' => '555'.random_int(1000000, 9999999),
            'status' => 1,
        ]);

        $this->subjectTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Subject Teacher',
        ]);
        $classTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Class Teacher',
        ]);
        $this->peerTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Peer Teacher',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'name' => 'Term 1', 'status' => 'current',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'Lower', 'order' => 1, 'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id, 'name' => 'Blue', 'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id,
            'class_teacher_id' => $classTeacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $this->student = $this->learner($link, $year, 'Nia Cole', 'Nia', 'Cole', 'KLS0091111');
        $this->secondStudent = $this->learner($link, $year, 'Owen Blake', 'Owen', 'Blake', 'KLS0091112');

        $subject = Subject::create([
            'school_id' => $this->school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
            'academic_year_id' => $year->id, 'name' => 'Mathematics', 'type' => 'core', 'status' => 1,
        ]);
        $type = ExamType::create(['name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 1]);
        SchoolGradingSystem::create([
            'school_id' => $this->school->id, 'standard_id' => $standard->id,
            'grade' => 'A', 'points' => 1, 'min_score' => 80, 'max_score' => 100, 'remark' => 'Excellent',
        ]);
        SchoolGradingSystem::create([
            'school_id' => $this->school->id, 'standard_id' => $standard->id,
            'grade' => 'C', 'points' => 3, 'min_score' => 0, 'max_score' => 79, 'remark' => 'Fair',
        ]);

        $this->exam = Exam::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id, 'academic_term_id' => $term->id,
            'standard_id' => $standard->id, 'section_id' => $section->id, 'subject_id' => $subject->id,
            'teacher_id' => $this->subjectTeacher->id, 'exam_type_id' => $type->id, 'status' => 'undone',
        ]);
    }

    public function test_enter_page_has_phone_mark_cards(): void
    {
        $html = $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-testid="mark-input-', $html);
        $this->assertStringContainsString('data-testid="abs-toggle"', $html);
        $this->assertStringContainsString('data-testid="marks-progress"', $html);
        $this->assertStringContainsString('data-testid="marks-next"', $html);
        $this->assertStringContainsString('data-testid="marks-previous"', $html);
        $this->assertStringContainsString('Save draft', $html);
        $this->assertStringContainsString('KLS0091111', $html);
        $this->assertStringContainsString('Not entered', $html);
        $this->assertMatchesRegularExpression('/0 of 2<\/strong>\s*entered · 0 to fix · 0 not saved yet/', $html);
        $this->assertStringContainsString('Submit is ready when every learner has a mark or ABS, and every mark is saved.', $html);
        $this->assertStringContainsString("You're offline.", $html);
        $this->assertStringContainsString('enterkeyhint="next"', $html);
        $this->assertStringContainsString('inputmode="numeric"', $html);
        $this->assertMatchesRegularExpression(
            '/class="[^"]*ka-marks-sheet[^"]*"[^>]*data-testid="marks-card-list"/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/data-testid="marks-card-list"[^>]*overflow-x/',
            $html
        );
        preg_match('/class="([^"]*)"[^>]*data-testid="marks-card-list"/', $html, $class);
        $this->assertArrayHasKey(1, $class);
        $this->assertStringNotContainsString('overflow-x', $class[1]);
        $this->assertMatchesRegularExpression('/data-testid="marks-submit-button"[^>]*disabled/', $html);
    }

    public function test_abs_typed_in_any_case_stores_the_absent_flag_and_grade_ab(): void
    {
        $this->actingAs($this->subjectTeacher)
            ->post(route('teacher.exam.marks.save', $this->exam), [
                'marks' => [$this->student->id => 'abs'],
            ])
            ->assertRedirect(route('teacher.exam.marks.enter', $this->exam));

        $mark = Marks::query()->where('exam_id', $this->exam->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($mark);
        $this->assertTrue($mark->absent);
        $this->assertNull($mark->marks);
        $this->assertSame('AB', $mark->grade);
        $this->assertSame((int) $this->subjectTeacher->id, (int) $mark->teacher_id);

        $html = $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('value="ABS"', $html);
        $this->assertStringContainsString('Saved · absent', $html);
        $this->assertStringContainsString('1 saved', $html);
    }

    public function test_a_mark_above_100_is_shown_as_above_100(): void
    {
        $this->actingAs($this->subjectTeacher)
            ->post(route('teacher.exam.marks.save', $this->exam), [
                'marks' => [$this->student->id => '140'],
            ])
            ->assertRedirect(route('teacher.exam.marks.enter', $this->exam));

        $html = $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="140"', $html);
        $this->assertStringContainsString('Above 100', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/data-testid="mark-above-maximum-'.$this->student->id.'"[^>]*\shidden/',
            $html
        );
    }

    public function test_submit_is_refused_until_every_learner_has_a_mark_or_abs(): void
    {
        $this->actingAs($this->subjectTeacher)
            ->post(route('teacher.exam.marks.save', $this->exam), [
                'marks' => [$this->student->id => 70],
            ])
            ->assertRedirect();

        $refused = $this->actingAs($this->subjectTeacher)
            ->post(route('teacher.exam.marks.submit', $this->exam));
        $refused->assertRedirect(route('teacher.exam.marks.enter', $this->exam));
        $refused->assertSessionHas('failmessage');
        $message = (string) session('failmessage');
        $this->assertStringContainsString('mark or ABS', $message);
        $this->assertStringContainsString('missing', $message);
        $this->assertSame(0, ExamMarksSubmission::query()->count());

        $this->actingAs($this->subjectTeacher)
            ->post(route('teacher.exam.marks.save', $this->exam), [
                'marks' => [$this->secondStudent->id => 'ABS'],
            ])
            ->assertRedirect();

        $accepted = $this->actingAs($this->subjectTeacher)
            ->post(route('teacher.exam.marks.submit', $this->exam));
        $accepted->assertRedirect(route('teacher.exam.marks.view', $this->exam));
        $this->assertStringNotContainsString('A mark or ABS is missing', (string) session('failmessage'));
        $this->assertSame(
            'submitted',
            ExamMarksSubmission::query()->where('exam_id', $this->exam->id)->value('status')
        );
    }

    public function test_a_subject_teacher_who_does_not_own_the_exam_cannot_save(): void
    {
        $this->actingAs($this->peerTeacher)
            ->post(route('teacher.exam.marks.save', $this->exam), [
                'marks' => [$this->student->id => 50],
            ])
            ->assertForbidden();

        $this->assertSame(0, Marks::query()->count());
    }

    private function learner(StandardLink $link, AcademicYear $year, string $name, string $first, string $last, string $kls): User
    {
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => $name,
            'status' => 'active',
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
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        return $student;
    }
}
