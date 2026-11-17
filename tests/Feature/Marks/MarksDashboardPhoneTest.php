<?php

namespace Tests\Feature\Marks;

use App\Enums\ToshiMode;
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
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Item 16: one tap from the teacher dashboard to the exams-to-mark list,
 * exams grouped from TeacherHomeBoard, and school-scoped admin counts.
 */
class MarksDashboardPhoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'headteacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_teacher_dashboard_links_to_the_exams_to_mark_page_and_groups_exams(): void
    {
        $school = $this->school('Fictional Marks Dash');
        $year = $this->year($school);
        $term = $this->term($school, $year);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $school->id, 'name' => 'P.5', 'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
        $teacher = $this->user($school, 5, 'Amina Okello');
        $subject = $this->subject($school, $year, $standard, $section, 'Mathematics', 'MTC');
        Teacherlink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $link->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        $student = $this->user($school, 6, 'Kato Mutebi');
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);
        $mid = $this->examType('Mid Term', 'MID');
        $end = $this->examType('End of Term', 'EOT');

        $returned = $this->exam($school, $year, $term, $standard, $section, $subject, $teacher, $mid);
        ExamMarksSubmission::create([
            'school_id' => $school->id,
            'exam_id' => $returned->id,
            'class_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'status' => 'reopened',
            'approval_status' => 'rejected',
            'rejection_reason' => 'Primary Five W marks look like they are out of 50.',
        ]);

        $progress = $this->exam($school, $year, $term, $standard, $section, $subject, $teacher, $end);
        Marks::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'exam_id' => $progress->id,
            'section_id' => $section->id,
            'marks' => 41,
            'grade' => 'C3',
        ]);

        $freshSubject = $this->subject($school, $year, $standard, $section, 'English', 'ENG');
        $this->exam($school, $year, $term, $standard, $section, $freshSubject, $teacher, $end);

        $html = $this->actingAs($teacher)->get('/teacher/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('teacher.exam.marks').'"', $html);
        $this->assertStringContainsString('data-testid="teacher-marks-needing-you"', $html);
        $this->assertStringContainsString('Primary Five W marks look like they are out of 50.', $html);
        $this->assertStringContainsString('data-testid="teacher-return-note"', $html);
        $this->assertStringContainsString('1 of 1 entered', $html);
        $this->assertStringContainsString('href="'.route('teacher.exam.marks.enter', $progress).'"', $html);
        $this->assertMatchesRegularExpression('/data-testid="teacher-marks-continue"[^>]*>Continue</', $html);
        $this->assertStringContainsString('data-testid="teacher-marks-enter"', $html);
        $this->assertStringContainsString('>Enter marks</a>', $html);
        $this->assertStringContainsString('Take attendance', $html);
        $returnedAt = strpos($html, 'data-state="Returned"');
        $progressAt = strpos($html, 'data-state="In progress"');
        $freshAt = strpos($html, 'data-state="Not started"');
        $this->assertNotFalse($returnedAt);
        $this->assertNotFalse($progressAt);
        $this->assertNotFalse($freshAt);
        $this->assertLessThan($progressAt, $returnedAt);
        $this->assertLessThan($freshAt, $progressAt);

        $school->refresh();
        $this->assertSame(0, (int) $school->toshi_enabled);
        $this->assertSame(ToshiMode::Preview, $school->toshi_mode);
    }

    public function test_admin_waiting_count_is_only_this_schools_submissions(): void
    {
        $school = $this->school('Fictional Approval School');
        $other = $this->school('Fictional Other Approval School');
        $admin = $this->user($school, 3, 'Grace Achieng');
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Grace',
            'lastname' => 'Achieng',
            'status' => 'active',
        ]);
        $this->user($school, 6, 'One Pupil');

        $this->pendingSubmission($school, $admin, 'Waiting Subject');
        $ready = $this->classWithExams($school, $admin, 'P.4 Ready', ['Ready Maths'], 'approved');
        $this->classWithExams($school, $admin, 'P.5 Open', ['Open Maths', 'Open English'], 'approved', approvedCount: 1);
        $this->pendingSubmission($other, $admin, 'Other Waiting Subject');
        $this->pendingSubmission($other, $admin, 'Other Waiting Again');
        $this->classWithExams($other, $admin, 'P.2 Away', ['Away Maths'], 'approved');

        $html = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.url('/admin/exams').'"', $html);
        $this->assertStringContainsString('href="'.route('admin.marks.submissions').'"', $html);
        $this->assertStringContainsString('href="'.route('admin.marks.releases.create').'"', $html);
        $this->assertMatchesRegularExpression('/data-testid="admin-marks-waiting-count">\s*1\s*</', $html);
        $this->assertMatchesRegularExpression('/data-testid="admin-marks-ready-count">\s*1\s*</', $html);
        $this->assertStringNotContainsString('Other Waiting Subject', $html);
        $this->assertNotSame(0, $ready['section']->id);

        $school->refresh();
        $this->assertSame(0, (int) $school->toshi_enabled);
        $this->assertSame(ToshiMode::Preview, $school->toshi_mode);
    }

    private function school(string $name): School
    {
        return School::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.test',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => ToshiMode::Preview,
        ]);
    }

    private function year(School $school): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
    }

    private function term(School $school, AcademicYear $year): AcademicTerm
    {
        return AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 3',
            'status' => 'current',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-12-01',
        ]);
    }

    private function user(School $school, int $group, string $name): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name.' '.uniqid(),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    private function subject(School $school, AcademicYear $year, Standard $standard, Section $section, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => $name,
            'code' => $code.'-'.uniqid(),
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function examType(string $name, string $code): ExamType
    {
        return ExamType::create([
            'name' => $name,
            'code' => $code.'-'.uniqid(),
            'contributes_to_report_total' => false,
        ]);
    }

    private function exam(School $school, AcademicYear $year, AcademicTerm $term, Standard $standard, Section $section, Subject $subject, User $teacher, ExamType $type): Exam
    {
        return Exam::withoutEvents(function () use ($school, $year, $term, $standard, $section, $subject, $teacher, $type) {
            $exam = new Exam([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'academic_term_id' => $term->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'status' => 'undone',
            ]);
            $exam->exam_type_id = $type->id;
            $exam->save();

            return $exam;
        });
    }

    /**
     * @param  list<string>  $subjectNames
     * @return array{section: Section}
     */
    private function classWithExams(School $school, User $actor, string $className, array $subjectNames, string $approval, int $approvedCount = -1): array
    {
        $year = AcademicYear::query()->where('school_id', $school->id)->where('status', 1)->first()
            ?? $this->year($school);
        $term = AcademicTerm::query()->where('school_id', $school->id)->where('academic_year_id', $year->id)->first()
            ?? $this->term($school, $year);
        $standard = Standard::query()->where('school_id', $school->id)->first()
            ?? Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create([
            'school_id' => $school->id, 'name' => $className, 'status' => 1,
        ]);
        StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
        $type = $this->examType('End of Term', 'EOT');
        $limit = $approvedCount < 0 ? count($subjectNames) : $approvedCount;
        foreach ($subjectNames as $index => $subjectName) {
            $subject = $this->subject($school, $year, $standard, $section, $subjectName, 'SUB'.$index);
            $exam = $this->exam($school, $year, $term, $standard, $section, $subject, $actor, $type);
            if ($index < $limit) {
                ExamMarksSubmission::create([
                    'school_id' => $school->id,
                    'exam_id' => $exam->id,
                    'class_id' => $section->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $actor->id,
                    'status' => 'submitted',
                    'approval_status' => $approval,
                    'submitted_at' => now(),
                ]);
            }
        }

        return ['section' => $section];
    }

    private function pendingSubmission(School $school, User $actor, string $subjectName): void
    {
        $year = AcademicYear::query()->where('school_id', $school->id)->where('status', 1)->first()
            ?? $this->year($school);
        $term = AcademicTerm::query()->where('school_id', $school->id)->where('academic_year_id', $year->id)->first()
            ?? $this->term($school, $year);
        $standard = Standard::query()->where('school_id', $school->id)->first()
            ?? Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create([
            'school_id' => $school->id, 'name' => $subjectName, 'status' => 1,
        ]);
        $subject = $this->subject($school, $year, $standard, $section, $subjectName, 'PND');
        $exam = $this->exam($school, $year, $term, $standard, $section, $subject, $actor, $this->examType('Mid Term', 'MID'));
        ExamMarksSubmission::create([
            'school_id' => $school->id,
            'exam_id' => $exam->id,
            'class_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $actor->id,
            'status' => 'submitted',
            'approval_status' => 'pending',
            'submitted_at' => now(),
        ]);
    }
}
