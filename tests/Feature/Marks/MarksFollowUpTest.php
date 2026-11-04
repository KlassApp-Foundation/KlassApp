<?php

namespace Tests\Feature\Marks;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
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
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\Marks\ExamMarkWriter;
use App\Services\Marks\MarksSubmissionService;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarksFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Section $section;

    private Standard $standard;

    private Subject $subject;

    private Exam $exam;

    private User $teacher;

    private User $admin;

    private User $amina;

    private User $brian;

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
            'name' => 'Follow Up School', 'slug' => 'follow-up-'.uniqid(), 'email' => uniqid().'@t.ug',
            'phone' => '0700000099', 'status' => 1,
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
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
        $this->amina = $this->person(6, 'Amina Follow', 'Amina', 'Follow');
        $this->brian = $this->person(6, 'Brian Follow', 'Brian', 'Follow');
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
    }

    public function test_ab_is_entered_and_left_out_of_the_total(): void
    {
        $writer = app(ExamMarkWriter::class);
        $writer->save($this->teacher, $this->exam, $this->amina->id, 80);
        $writer->save($this->teacher, $this->exam, $this->brian->id, 'AB');

        $absent = Marks::query()->where('student_id', $this->brian->id)->first();
        $this->assertTrue($absent->isAbsent());
        $this->assertNull($absent->marks);
        $this->assertSame('AB', $absent->grade);

        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $this->school->id,
            sectionId: (int) $this->section->id,
            academicTermId: (int) $this->term->id,
            examTypeId: (int) $this->exam->exam_type_id,
        ));
        $rows = collect($sheet->students)->keyBy(fn ($student) => $student->studentId);
        $this->assertEquals(80.0, $rows[$this->amina->id]->total);
        $this->assertNull($rows[$this->brian->id]->total);
        $this->assertSame(ResultCell::ABSENT, $rows[$this->brian->id]->cell((int) $this->subject->id)->status);
        $this->assertSame(1, $rows[$this->amina->id]->classOutOf);

        $html = $this->actingAs($this->teacher)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('value="AB"', $html);
        $this->assertStringContainsString('value="80"', $html);

        app(MarksSubmissionService::class)->submit($this->exam, $this->teacher);
        $this->assertSame('submitted', ExamMarksSubmission::query()->where('exam_id', $this->exam->id)->value('status'));
    }

    public function test_a_mark_above_the_maximum_blocks_submit(): void
    {
        app(ExamMarkWriter::class)->save($this->teacher, $this->exam, $this->amina->id, 105);
        app(ExamMarkWriter::class)->save($this->teacher, $this->exam, $this->brian->id, 'AB');

        $html = $this->actingAs($this->teacher)->get(route('teacher.exam.marks.enter', $this->exam))->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="mark-above-maximum-'.$this->amina->id.'"', $html);
        $this->assertStringContainsString('Above 100. Check the mark.', $html);

        try {
            app(MarksSubmissionService::class)->submit($this->exam, $this->teacher);
            $this->fail('A mark above 100 must not be submitted.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('above 100', $e->getMessage());
        }
        $this->assertDatabaseMissing('exam_marks_submissions', ['exam_id' => $this->exam->id]);
    }

    public function test_release_page_shows_the_three_steps_and_the_audience(): void
    {
        app(ExamMarkWriter::class)->save($this->teacher, $this->exam, $this->amina->id, 70);
        $parent = $this->person(7, 'Parent Follow');
        StudentParentLink::create([
            'school_id' => $this->school->id, 'student_id' => $this->amina->id, 'parent_id' => $parent->id, 'status' => 1,
        ]);
        WhatsAppUser::create([
            'school_id' => $this->school->id, 'user_id' => $parent->id, 'phone' => '256700000111', 'opted_in' => true,
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.marks.releases.create', ['exam_id' => $this->exam->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-testid="release-steps"', $html);
        $this->assertStringContainsString('All subjects approved', $html);
        $this->assertStringContainsString('>Preview<', $html);
        $this->assertStringContainsString('>Release<', $html);
        $this->assertStringContainsString('data-testid="release-audience"', $html);
        $this->assertStringContainsString('Who will be told', $html);
        $this->assertStringContainsString('on WhatsApp', $html);
        $this->assertStringContainsString('Release marks', $html);
    }

    public function test_the_mark_sheet_includes_a_phone_card_per_student(): void
    {
        app(ExamMarkWriter::class)->save($this->teacher, $this->exam, $this->amina->id, 70);

        $html = $this->actingAs($this->admin)->get(route('admin.marks.filter', [
            'class' => $this->section->id,
            'term' => $this->term->id,
            'examType' => $this->exam->exam_type_id,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('data-testid="marks-phone-card"', $html);
        $this->assertStringContainsString('data-testid="marks-phone-position"', $html);
        $this->assertStringContainsString('of ', $html);
    }

    public function test_selected_submissions_are_approved_one_by_one(): void
    {
        $first = $this->submission($this->exam);
        $otherSubject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id,
            'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        $secondExam = Exam::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'academic_term_id' => $this->term->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'subject_id' => $otherSubject->id,
            'teacher_id' => $this->teacher->id, 'exam_type_id' => $this->exam->exam_type_id, 'status' => 'undone',
        ]);
        $second = $this->submission($secondExam);
        $otherSchool = School::create([
            'name' => 'Other Follow', 'slug' => 'other-follow-'.uniqid(), 'email' => uniqid().'@t.ug', 'phone' => '0700000088', 'status' => 1,
        ]);
        $otherSection = Section::create(['school_id' => $otherSchool->id, 'name' => 'Other Class', 'status' => 1]);
        $foreign = ExamMarksSubmission::query()->create([
            'school_id' => $otherSchool->id, 'exam_id' => $this->exam->id, 'class_id' => $otherSection->id,
            'subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id, 'status' => 'submitted', 'approval_status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.marks.submissions.approve-selected'), [
                'submission_ids' => [$first->id, $second->id, $foreign->id, 999999],
            ])
            ->assertRedirect()
            ->assertSessionHas('successmessage');

        $first->refresh();
        $second->refresh();
        $foreign->refresh();
        $this->assertSame('approved', $first->approval_status);
        $this->assertSame((int) $this->admin->id, (int) $first->approved_by);
        $this->assertNotNull($first->approved_at);
        $this->assertSame('approved', $second->approval_status);
        $this->assertSame((int) $this->admin->id, (int) $second->approved_by);
        $this->assertSame('pending', $foreign->approval_status);

        try {
            app(MarksSubmissionService::class)->approveSelected($this->teacher, [$first->id]);
            $this->fail('A teacher cannot approve submissions.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    private function submission(Exam $exam): ExamMarksSubmission
    {
        return ExamMarksSubmission::query()->create([
            'school_id' => $this->school->id,
            'exam_id' => $exam->id,
            'class_id' => $exam->section_id,
            'subject_id' => $exam->subject_id,
            'teacher_id' => $this->teacher->id,
            'status' => 'submitted',
            'approval_status' => 'pending',
            'submitted_at' => now(),
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
}
