<?php

namespace Tests\Feature\Marks;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamSet;
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

class AdminMarksEntryTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private User $schoolAdmin;

    private User $headTeacher;

    private User $siteAdmin;

    private User $otherAdmin;

    private User $otherHead;

    private User $subjectTeacher;

    private User $classTeacher;

    private User $peerTeacher;

    private User $student;

    private User $parent;

    private Exam $exam;

    private ExamSet $set;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'North Marks School',
            'slug' => 'north-marks-'.uniqid(),
            'email' => uniqid().'@north-marks.test',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
        ]);
        $this->otherSchool = School::create([
            'name' => 'South Marks School',
            'slug' => 'south-marks-'.uniqid(),
            'email' => uniqid().'@south-marks.test',
            'phone' => '071'.random_int(1000000, 9999999),
            'status' => 1,
        ]);
        AcademicYear::create([
            'school_id' => $this->otherSchool->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        Standard::create([
            'school_id' => $this->otherSchool->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->schoolAdmin = $this->person(3, $this->school, 'North Admin');
        $this->headTeacher = $this->person(4, $this->school, 'North Head');
        $this->siteAdmin = $this->person(1, $this->school, 'North Site');
        $this->otherAdmin = $this->person(3, $this->otherSchool, 'South Admin');
        $this->otherHead = $this->person(4, $this->otherSchool, 'South Head');
        $this->subjectTeacher = $this->person(5, $this->school, 'Subject Teacher');
        $this->classTeacher = $this->person(5, $this->school, 'Class Teacher');
        $this->peerTeacher = $this->person(5, $this->school, 'Peer Teacher');
        $this->parent = $this->person(7, $this->school, 'A Parent');
        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Amina North',
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $this->student->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => 'Amina',
            'lastname' => 'North',
            'status' => 'active',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Year 5',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $this->classTeacher->id,
            'stream' => 'A',
            'status' => 1,
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $link->id,
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'name' => 'Mathematics',
            'type' => 'core',
            'status' => 1,
        ]);
        $type = ExamType::create([
            'name' => 'Mid Term',
            'code' => 'MID',
            'contributes_to_report_total' => 1,
        ]);
        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'grade' => 'A',
            'points' => 1,
            'min_score' => 80,
            'max_score' => 100,
            'remark' => 'Excellent',
        ]);
        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'grade' => 'C',
            'points' => 3,
            'min_score' => 0,
            'max_score' => 79,
            'remark' => 'Fair',
        ]);

        $this->set = ExamSet::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'created_by' => $this->schoolAdmin->id,
        ]);
        $this->exam = Exam::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'exam_type_id' => $type->id,
            'exam_set_id' => $this->set->id,
            'status' => 'undone',
        ]);
    }

    public function test_school_admin_enters_saves_and_submits_on_the_teacher_screen(): void
    {
        $this->saveAndSubmit($this->schoolAdmin, 88);
    }

    public function test_head_teacher_enters_saves_and_submits_for_any_class_in_the_school(): void
    {
        $this->saveAndSubmit($this->headTeacher, 76);
    }

    public function test_site_admin_of_the_school_can_enter_marks(): void
    {
        $this->actingAs($this->siteAdmin)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->assertSee('AMINA')
            ->assertDontSee('data-testid="marks-submit-button"', false);
    }

    public function test_exam_set_page_links_into_the_same_marks_screen(): void
    {
        $href = route('teacher.exam.marks.enter', $this->exam);

        foreach ([$this->schoolAdmin, $this->headTeacher] as $actor) {
            $this->actingAs($actor)
                ->get(route('admin.exam-sets.show', ['batch' => $this->set->id]))
                ->assertOk()
                ->assertSee('data-testid="exam-set-enter-'.$this->exam->id.'"', false)
                ->assertSee($href, false)
                ->assertSee('min-height:44px', false);
        }
    }

    public function test_another_school_cannot_enter_or_open_the_set(): void
    {
        foreach ([$this->otherAdmin, $this->otherHead] as $actor) {
            $this->actingAs($actor)
                ->get(route('teacher.exam.marks.enter', $this->exam))
                ->assertForbidden();

            $this->actingAs($actor)
                ->post(route('teacher.exam.marks.save', $this->exam), [
                    'marks' => [$this->student->id => 50],
                ])
                ->assertForbidden();

            $this->actingAs($actor)
                ->get(route('admin.exam-sets.show', ['batch' => $this->set->id]))
                ->assertNotFound();
        }

        $this->assertSame(0, Marks::count());
    }

    public function test_a_subject_teacher_who_is_not_the_owner_cannot_enter_or_submit(): void
    {
        $this->actingAs($this->peerTeacher)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertForbidden();

        $this->actingAs($this->peerTeacher)
            ->post(route('teacher.exam.marks.save', $this->exam), [
                'marks' => [$this->student->id => 40],
            ])
            ->assertForbidden();

        $this->actingAs($this->peerTeacher)
            ->post(route('teacher.exam.marks.submit', $this->exam))
            ->assertForbidden();

        $this->actingAs($this->peerTeacher)
            ->get(route('teacher.exam.marks'))
            ->assertOk()
            ->assertDontSee(route('teacher.exam.marks.enter', $this->exam), false);

        $this->assertSame(0, Marks::count());
        $this->assertSame(0, ExamMarksSubmission::count());
    }

    public function test_the_class_teacher_and_subject_teacher_can_still_open_entry(): void
    {
        $this->actingAs($this->classTeacher)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->assertSee('AMINA');

        $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk();

        $this->actingAs($this->schoolAdmin)
            ->get(route('teacher.exam.marks'))
            ->assertOk()
            ->assertSee(route('teacher.exam.marks.enter', $this->exam), false);
    }

    public function test_admins_stay_out_of_the_rest_of_the_teacher_area(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->get(route('teacher.dashboard'))
            ->assertRedirect('/admin/dashboard');

        $this->actingAs($this->schoolAdmin)
            ->get(route('teacher.exams.create'))
            ->assertRedirect('/admin/dashboard');

        $this->actingAs($this->headTeacher)
            ->get(route('teacher.dashboard'))
            ->assertNotFound();

        $this->actingAs($this->headTeacher)
            ->get(route('teacher.exams.create'))
            ->assertNotFound();

        $this->actingAs($this->siteAdmin)
            ->get(route('teacher.dashboard'))
            ->assertRedirect('/superadmin/dashboard');
    }

    public function test_a_learner_and_a_parent_cannot_open_marks_entry(): void
    {
        $this->actingAs($this->student)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertRedirect('/student/dashboard');

        $this->actingAs($this->parent)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertNotFound();
    }

    private function saveAndSubmit(User $actor, int $mark): void
    {
        $this->actingAs($actor)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->assertSee('AMINA')
            ->assertDontSee('data-testid="marks-submit-button"', false);

        $this->actingAs($actor)
            ->post(route('teacher.exam.marks.save', $this->exam), [
                'marks' => [$this->student->id => $mark],
            ])
            ->assertRedirect(route('teacher.exam.marks.enter', $this->exam));

        $row = Marks::query()->where('exam_id', $this->exam->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($row);
        $this->assertSame($mark, (int) $row->marks);
        $this->assertSame((int) $actor->id, (int) $row->teacher_id);
        $this->assertNotSame((int) $this->subjectTeacher->id, (int) $row->teacher_id);

        $this->actingAs($actor)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->assertSee('data-testid="marks-submit-button"', false);

        $this->actingAs($actor)
            ->post(route('teacher.exam.marks.submit', $this->exam))
            ->assertRedirect(route('teacher.exam.marks.view', $this->exam));

        $submission = ExamMarksSubmission::query()->where('exam_id', $this->exam->id)->first();
        $this->assertNotNull($submission);
        $this->assertSame('submitted', $submission->status);
        $this->assertSame((int) $actor->id, (int) $submission->teacher_id);

        $this->actingAs($actor)
            ->get(route('teacher.exam.marks.view', $this->exam))
            ->assertOk();
    }

    private function person(int $group, School $school, string $name): User
    {
        return User::factory()->create([
            'usergroup_id' => $group,
            'school_id' => $school->id,
            'name' => $name,
            'status' => 'active',
        ]);
    }
}
