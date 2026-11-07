<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A class teacher with no subject allocation still has a class, its students
 * and its exams. Subject allocations keep counting as well.
 */
class ClassTeacherDashboardStreamsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private Standard $standard;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Class Teacher Dashboard School',
            'email' => 'ct-dash@example.test',
            'phone' => '+256700222000',
            'slug' => 'ct-dash',
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
        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 2,
            'status' => 1,
        ]);
    }

    public function test_class_teacher_stream_counts_on_the_dashboard(): void
    {
        $teacher = $this->teacher('Class Teacher', 'class.teacher@ct-dash.test');
        $other = $this->teacher('Other Teacher', 'other.teacher@ct-dash.test');

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.5',
            'status' => 1,
            'class_teacher_id' => null,
        ]);
        $stream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'stream' => 'A',
            'class_teacher_id' => $teacher->id,
            'status' => 1,
        ]);
        $elsewhere = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => Section::create([
                'school_id' => $this->school->id,
                'name' => 'P.6',
                'status' => 1,
            ])->id,
            'stream' => 'A',
            'class_teacher_id' => $other->id,
            'status' => 1,
        ]);

        $this->student('Carol Stream', $stream);
        $this->student('Other Class', $elsewhere);

        $term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term I',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);
        $type = ExamType::create(['name' => 'Mid', 'code' => 'MID-CT', 'contributes_to_report_total' => false]);
        $subject = \App\Models\Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);
        Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $other->id,
            'exam_type_id' => $type->id,
            'academic_term_id' => $term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'undone',
        ]));

        $html = $this->actingAs($teacher)->get('/teacher/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString("You haven't been assigned any classes yet", $html);
        $this->assertSame('1', $this->dashboardCount($html, 'students'), 'My students');
        $this->assertSame('1', $this->dashboardCount($html, 'classes'), 'My classes');
        $this->assertMatchesRegularExpression('/data-testid="upcoming-exams-count">\s*1\s*</', $html);
        $this->assertStringContainsString('data-testid="release-marks-button"', $html);
    }

    public function test_section_class_teacher_without_a_stream_teacher_is_counted(): void
    {
        $teacher = $this->teacher('Section Teacher', 'section.teacher@ct-dash.test');
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.4',
            'status' => 1,
            'class_teacher_id' => $teacher->id,
        ]);
        $stream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'stream' => null,
            'class_teacher_id' => null,
            'status' => 1,
        ]);
        $this->student('Section Pupil', $stream);

        $html = $this->actingAs($teacher)->get('/teacher/dashboard')->assertOk()->getContent();
        $this->assertStringNotContainsString("You haven't been assigned any classes yet", $html);
        $this->assertSame('1', $this->dashboardCount($html, 'students'));
        $this->assertSame('1', $this->dashboardCount($html, 'classes'));
    }

    public function test_subject_allocation_still_counts(): void
    {
        $teacher = $this->teacher('Subject Teacher', 'subject.teacher@ct-dash.test');
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.3',
            'status' => 1,
        ]);
        $stream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
        $subject = \App\Models\Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'code' => 'MTC',
            'type' => 'core',
            'status' => 1,
        ]);
        Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $stream->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        $this->student('Maths Pupil', $stream);

        $html = $this->actingAs($teacher)->get('/teacher/dashboard')->assertOk()->getContent();
        $this->assertSame('1', $this->dashboardCount($html, 'students'));
        $this->assertSame('1', $this->dashboardCount($html, 'classes'));
    }

    /** The two counts on the teacher home board (#1283): "my classes" and "my students". */
    private function dashboardCount(string $html, string $which): string
    {
        $pattern = $which === 'students'
            ? '/data-testid="teacher-students">\s*(\d+)\s*</'
            : '/<strong>\s*(\d+)\s*<\/strong><span>my classes/';
        preg_match($pattern, $html, $match);

        return $match[1] ?? '';
    }

    private function teacher(string $name, string $email): User
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name, 2)[1] ?? '',
        ]);

        return $user;
    }

    private function student(string $name, StandardLink $link): void
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@ct-dash.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $user->id,
            'standardLink_id' => $link->id,
        ]);
    }
}
