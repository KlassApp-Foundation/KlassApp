<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
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
use Tests\TestCase;

/**
 * The per-student marks page reads ResultsService, including one column
 * per mid-term month. The old report URL redirects there.
 */
class StudentResultsPageTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicTerm $term;

    private Section $section;

    private User $admin;

    private User $student;

    private Subject $english;

    private Exam $june;

    private Exam $july;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);

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
            'name' => 'Student Page School',
            'email' => 'student-page@test.sch.ug',
            'phone' => '0700000091',
            'slug' => 'student-page-school',
            'status' => 1,
        ]);
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'status' => 'current',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-08-31',
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => '1',
        ]);
        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.3',
            'status' => 1,
        ]);
        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'email' => 'admin.student-page@t.sch.ug',
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'email' => 'teacher.student-page@t.sch.ug',
        ]);
        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $teacher->id,
            'status' => '1',
        ]);
        $this->english = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);
        $math = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics',
            'code' => 'MTC',
            'type' => 'core',
            'status' => 1,
        ]);
        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Alice A',
            'email' => 'alice.student-page@t.sch.ug',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $link->id,
        ]);

        $this->june = $this->exam(1, '2026-06-15', $teacher->id, $year->id, $standard->id);
        $this->july = $this->exam(1, '2026-07-15', $teacher->id, $year->id, $standard->id);
        $end = $this->exam(2, '2026-08-01', $teacher->id, $year->id, $standard->id);
        $this->mark($this->june, $this->english, 45);
        $this->mark($this->june, $math, 50);
        $this->mark($this->july, $this->english, 48);
        $this->mark($end, $this->english, 60);
        $this->mark($end, $math, 65);
    }

    public function test_class_grid_links_to_the_student_page_and_the_old_report_url_redirects(): void
    {
        $grid = file_get_contents(resource_path('views/admin/marks/results-table2.blade.php'));
        $this->assertStringNotContainsString('admin.report.student.class', $grid);
        $this->assertStringContainsString('admin.marks.student.class', $grid);

        $this->actingAs($this->admin)
            ->get(route('admin.report.student.class', [$this->student, $this->section->id, $this->june->id]))
            ->assertStatus(301)
            ->assertRedirect(route('admin.marks.student.class', [$this->student, $this->section->id, $this->june->id]));
    }

    public function test_student_page_shows_the_name_and_each_mid_term_month(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.marks.student.class', [$this->student, $this->section->id, $this->june->id]))
            ->assertOk()
            ->assertSee('data-testid="student-result-name"', false)
            ->assertSee('Alice A')
            ->assertSee('data-testid="student-result-class"', false)
            ->assertSee('P.3')
            ->assertSee('data-testid="student-result-term"', false)
            ->assertSee('Term 2')
            ->assertSee('JUNE')
            ->assertSee('JULY')
            ->assertSee('45')
            ->assertSee('48')
            ->assertSee('data-testid="student-result-total"', false)
            ->assertSee('125')
            ->assertSee('data-testid="student-result-position"', false);
    }

    public function test_another_schools_student_or_exam_is_not_found(): void
    {
        $other = School::create([
            'name' => 'Other Student Page School',
            'email' => 'other-student-page@test.sch.ug',
            'phone' => '0700000092',
            'slug' => 'other-student-page-school',
            'status' => 1,
        ]);
        $year = AcademicYear::create([
            'school_id' => $other->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $other->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'status' => 'current',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-08-31',
        ]);
        $standard = Standard::create([
            'school_id' => $other->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => '1',
        ]);
        $section = Section::create([
            'school_id' => $other->id,
            'name' => 'P.4',
            'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $other->id,
            'email' => 'other.teacher.student-page@t.sch.ug',
        ]);
        $otherStudent = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $other->id,
            'name' => 'Other Student',
            'email' => 'other.student-page@t.sch.ug',
            'status' => 'active',
        ]);
        $otherExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $other->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'subject_id' => $this->english->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => 1,
            'academic_term_id' => $term->id,
            'academic_year_id' => $year->id,
            'scheduled_at' => '2026-06-15',
            'status' => 'submitted',
        ]));

        $this->actingAs($this->admin)
            ->get(route('admin.marks.student.class', [$otherStudent, $this->section->id, $this->june->id]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->get(route('admin.marks.student.class', [$this->student, $this->section->id, $otherExam->id]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->get(route('admin.marks.student.class', [$this->student, $section->id, $this->june->id]))
            ->assertNotFound();
    }

    private function exam(int $typeId, string $when, int $teacherId, int $yearId, int $standardId): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $standardId,
            'section_id' => $this->section->id,
            'subject_id' => $this->english->id,
            'teacher_id' => $teacherId,
            'exam_type_id' => $typeId,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $yearId,
            'scheduled_at' => $when,
            'status' => 'submitted',
        ]));
    }

    private function mark(Exam $exam, Subject $subject, int $score): void
    {
        Marks::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'teacher_id' => $exam->teacher_id,
            'section_id' => $this->section->id,
            'marks' => $score,
            'grade' => 'D1',
        ]);
    }
}
