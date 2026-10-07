<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * exams.teacher_id is NOT NULL, but the create form's "Assigned Teacher" field is
 * optional. Regression: creating an exam with the field empty hit a 500
 * (SQLSTATE 23000). The empty field must fall back to the subject's Teacherlink,
 * else the acting admin — mirroring the class-teacher create path.
 */
class ExamCreationTeacherDefaultTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;
    private User $teacher;
    private AcademicYear $year;
    private AcademicTerm $term;
    private Section $section;
    private Standard $standard;
    private Subject $subject;
    private ExamType $examType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Exam Teacher Default School',
            'slug' => 'exam-teacher-default-' . uniqid(),
            'email' => 'exam-default-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'email' => 'admin.examdefault@t.sch.ug',
        ]);

        $this->teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'email' => 'teacher.examdefault@t.sch.ug',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'status' => 'current',
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => 1,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics',
            'code' => 'MTC-1',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->examType = ExamType::create(['name' => 'End of Term Examination', 'code' => 'EOT']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->examType->id,
        ], $overrides);
    }

    public function test_exam_creates_without_a_teacher_and_defaults_to_the_subject_teacherlink(): void
    {
        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'class_teacher_id' => $this->teacher->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'status' => 1,
        ]);

        Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $link->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/exams/store', $this->payload());

        $response->assertRedirect(route('admin.exams'));
        $exam = Exam::where('school_id', $this->school->id)->latest('id')->first();
        $this->assertNotNull($exam, 'the exam must be created');
        $this->assertSame($this->teacher->id, (int) $exam->teacher_id, 'empty teacher must default to the subject Teacherlink');
    }

    public function test_exam_creates_without_a_teacher_and_no_link_falls_back_to_the_admin(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/exams/store', $this->payload());

        $response->assertRedirect(route('admin.exams'));
        $exam = Exam::where('school_id', $this->school->id)->latest('id')->first();
        $this->assertNotNull($exam);
        $this->assertSame($this->admin->id, (int) $exam->teacher_id, 'without a link the acting admin is the fallback');
    }

    public function test_exam_create_respects_an_explicit_teacher(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/exams/store', $this->payload([
            'teacher_id' => $this->teacher->id,
        ]));

        $response->assertRedirect(route('admin.exams'));
        $exam = Exam::where('school_id', $this->school->id)->latest('id')->first();
        $this->assertSame($this->teacher->id, (int) $exam->teacher_id);
    }
}
