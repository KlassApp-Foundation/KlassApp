<?php

namespace Tests\Feature;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\MustBeTeacher;
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
use App\Models\Userprofile;
use App\Services\ExamMarksheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamMarksheetEnrolledStudentsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $teacher;

    private User $student;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Standard $standard;

    private Section $section;

    private StandardLink $stdLink;

    private Subject $subject;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            [
                'id' => 2,
                'name' => 'End Of Term',
                'code' => 'EOT',
                'contributes_to_report_total' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], 'id');

        $this->school = School::create([
            'name' => 'Marksheet Enroll School',
            'email' => 'marksheet.enroll@t.sch.ug',
            'phone' => '0700000099',
            'slug' => 'marksheet-enroll-'.uniqid(),
            'status' => 1,
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

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary Seven',
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'email' => 'admin.marksheet.enroll@t.sch.ug',
        ]);

        $this->teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'James Okello',
            'email' => 'teacher.marksheet.enroll@t.sch.ug',
        ]);

        $this->stdLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'status' => 1,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics',
            'code' => '007',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Grace Nakamya',
            'email' => 'grace.marksheet.enroll@t.sch.ug',
            'status' => 'active',
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->student->id,
            'usergroup_id' => 6,
            'firstname' => 'Grace Nakamya',
            'lastname' => '',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $this->stdLink->id,
        ]);

        $this->exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => 2,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => '2026-09-15 10:00:00',
            'status' => 'undone',
        ]));
    }

    public function test_service_includes_enrolled_student_and_exam_subject_before_marks_exist(): void
    {
        $this->assertSame(0, Marks::where('exam_id', $this->exam->id)->count());

        $sheet = app(ExamMarksheetService::class)->build($this->exam, $this->school->id);

        $this->assertSame(['STUDENT NAME', 'MATHEMATICS'], $sheet['headings']);
        $this->assertCount(1, $sheet['rows']);
        $this->assertSame('Grace Nakamya', $sheet['rows'][0][0]);
        $this->assertSame('', $sheet['rows'][0][1]);
    }

    public function test_service_fills_existing_marks_for_enrolled_students(): void
    {
        Marks::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'section_id' => $this->section->id,
            'marks' => 78,
            'grade' => 'D1',
        ]);

        $sheet = app(ExamMarksheetService::class)->build($this->exam, $this->school->id);

        $this->assertSame(['STUDENT NAME', 'MATHEMATICS'], $sheet['headings']);
        $this->assertSame('Grace Nakamya', $sheet['rows'][0][0]);
        $this->assertSame(78.0, $sheet['rows'][0][1]);
    }

    public function test_admin_marksheet_download_includes_enrolled_student_before_marks(): void
    {
        \Maatwebsite\Excel\Facades\Excel::fake();

        $this->actingAs($this->admin)
            ->get(route('admin.exams.marksheet', $this->exam))
            ->assertOk();

        \Maatwebsite\Excel\Facades\Excel::assertDownloaded(
            'Primary_Seven_EOT_marksheet.xlsx',
            function ($export) {
                $this->assertSame(['STUDENT NAME', 'MATHEMATICS'], $export->headings());
                $rows = $export->array();
                $this->assertCount(1, $rows);
                $this->assertSame('Grace Nakamya', $rows[0][0]);

                return true;
            }
        );
    }

    public function test_exams_index_shows_exam_subject_when_no_marks_exist(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.exams'));

        $response->assertOk();
        $response->assertSee('MATHEMATICS', false);
        $response->assertSee('James Okello', false);
    }
}
