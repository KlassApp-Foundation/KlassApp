<?php

namespace Tests\Feature\Results;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\DashboardV2DataService;
use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The release sheet, the class chart and the report-card tile read the
 * results service. The overall grade comes from the school scale.
 */
class ReleaseAndDashboardReadResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_sheet_grade_uses_the_school_scale_not_a_fixed_band(): void
    {
        $ctx = $this->school();
        SchoolGradingSystem::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'grade' => 'B',
            'points' => 2,
            'min_score' => 0,
            'max_score' => 100,
            'remark' => 'B',
        ]);
        $student = $this->student($ctx, 'Scale Student');
        $exam = $this->exam($ctx, $ctx['english'], '2026-08-01');
        $this->mark($ctx, $exam, $ctx['english'], $student, 80, 'C3');

        $sheet = app(MarksReleaseService::class)->combinedSheet($ctx['admin'], $exam->id, $ctx['link']->id);
        $row = $sheet['groups'][0]['students'][0];

        $this->assertSame('B', $row['cells']['english']['grade']);
        $this->assertSame('B', $row['grade']);
        $this->assertSame('80', $row['cells']['english']['mark']);
    }

    public function test_performance_tile_averages_the_sitting_not_only_the_latest_exam_row(): void
    {
        $ctx = $this->school();
        $student = $this->student($ctx, 'Chart Student');
        $english = $this->exam($ctx, $ctx['english'], '2026-08-01');
        $maths = $this->exam($ctx, $ctx['maths'], '2026-08-15');
        $this->mark($ctx, $english, $ctx['english'], $student, 50, 'C6');
        $this->mark($ctx, $maths, $ctx['maths'], $student, 90, 'D2');

        $data = app(DashboardV2DataService::class)->build($ctx['school'], $ctx['admin']);

        $this->assertSame('P.5', $data['charts']['per_class'][0]['label']);
        $this->assertSame(70.0, $data['charts']['per_class'][0]['value']);
        $this->assertSame(1, $data['kpis']['report_cards']['ready']);
    }

    /**
     * @return array{school: School, year: AcademicYear, term: AcademicTerm, standard: Standard, section: Section, link: StandardLink, admin: User, teacher: User, english: Subject, maths: Subject}
     */
    private function school(): array
    {
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        DB::table('exam_types')->upsert([
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Release Read School',
            'email' => 'release-read-'.uniqid().'@t.sch.ug',
            'phone' => '0700000041',
            'slug' => 'release-read-'.uniqid(),
            'status' => 1,
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'status' => 'current',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-08-31',
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.5',
            'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'email' => 'teacher.'.uniqid().'@t.sch.ug',
        ]);
        $admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
            'email' => 'admin.'.uniqid().'@t.sch.ug',
            'name' => 'Release Admin',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'stream' => 'A',
            'status' => 1,
        ]);
        $english = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);
        $maths = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'code' => 'MTC',
            'type' => 'core',
            'status' => 1,
        ]);

        return compact('school', 'year', 'term', 'standard', 'section', 'link', 'admin', 'teacher', 'english', 'maths');
    }

    private function student(array $ctx, string $name): User
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $ctx['school']->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@t.sch.ug',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'user_id' => $user->id,
            'standardLink_id' => $ctx['link']->id,
        ]);

        return $user;
    }

    private function exam(array $ctx, Subject $subject, string $when): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'exam_type_id' => 2,
            'academic_term_id' => $ctx['term']->id,
            'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => $when,
            'status' => 'submitted',
        ]));
    }

    private function mark(array $ctx, Exam $exam, Subject $subject, User $student, float $score, string $grade): void
    {
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $ctx['school']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'section_id' => $exam->section_id,
            'marks' => $score,
            'grade' => $grade,
        ]);
    }

}
