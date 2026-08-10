<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Requests\CreateExamRequest;
use App\Http\Requests\UpdateExamsRequest;
use App\Http\Requests\UpdateUgSubjectRequest;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateExamSchoolIdValidationTest extends TestCase
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

        // Advance school ids so school_id !== standard_id (the real-world failure mode).
        School::create([
            'name' => 'Padding School',
            'slug' => 'padding-' . uniqid(),
            'email' => 'padding-' . uniqid() . '@t.sch.ug',
            'phone' => '071' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->school = School::create([
            'name' => 'Exam Create School',
            'slug' => 'exam-create-' . uniqid(),
            'email' => 'exam-create-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'email' => 'admin.examcreate@t.sch.ug',
        ]);

        $this->teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'email' => 'teacher.examcreate@t.sch.ug',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-05-01',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'status' => 1,
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Mathematics',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->examType = ExamType::create([
            'name' => 'End Of Term',
            'code' => 'EOT',
            'contributes_to_report_total' => true,
        ]);
    }

    public function test_create_exam_request_validates_school_id_against_schools_table(): void
    {
        $rules = (new CreateExamRequest)->rules();

        $this->assertSame('required|exists:schools,id', $rules['school_id']);
        $this->assertSame('required|exists:standards,id', $rules['standard_id']);
    }

    public function test_update_exam_and_subject_requests_do_not_confuse_school_with_standard(): void
    {
        $examRules = (new UpdateExamsRequest)->rules();
        $subjectRules = (new UpdateUgSubjectRequest)->rules();

        $this->assertStringContainsString('exists:schools,id', $examRules['school_id']);
        $this->assertStringContainsString('exists:schools,id', $subjectRules['school_id']);
        $this->assertStringNotContainsString('exists:standards,id', $examRules['school_id']);
        $this->assertStringNotContainsString('exists:standards,id', $subjectRules['school_id']);
    }

    public function test_admin_can_create_exam_via_store_route_when_school_id_differs_from_standard_id(): void
    {
        // Regression for the copy-paste bug: school_id was validated against standards.
        // School ids and standard ids diverge in real data — create must still succeed.
        $this->assertNotSame($this->school->id, $this->standard->id);

        $response = $this->actingAs($this->admin)->post(route('admin.exams.store'), [
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => $this->examType->id,
            // Omit scheduled_at: calendar sync writes events.batch='' which SQLite rejects
            // (MySQL accepts). Validation + exam persist are what this regression covers.
        ]);

        $response->assertRedirect(route('admin.exams'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('successmessage');

        $this->assertDatabaseHas('exams', [
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'exam_type_id' => $this->examType->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $this->assertSame(1, Exam::where('school_id', $this->school->id)->count());
    }

    public function test_create_exam_page_reloads_subjects_for_selected_class(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.exams.create', [
            'section' => $this->section->id,
        ]));

        $response->assertOk();
        $response->assertSee('MATHEMATICS');
        $response->assertSee('End Of Term');
        $response->assertSee('Term I');
    }
}
