<?php

namespace Tests\Feature\Teacher;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Page 8 teacher home keeps its board, and the marks tiles that board had dropped
 * are back inside it: exams to mark labelled "Marks needing you", upcoming exams,
 * returned work under "Marks & corrections", and Marks Release for someone who may release.
 */
class TeacherHomeMarksTilesTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private Standard $standard;

    private Section $section;

    private StandardLink $link;

    private User $classHead;

    private User $mathsTeacher;

    private User $englishTeacher;

    private Subject $maths;

    private Subject $english;

    private ExamType $type;

    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Marks Tile School',
            'email' => 'marks-tiles@example.test',
            'phone' => '+256700888000',
            'slug' => 'marks-tiles',
            'status' => 1,
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'AY',
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
        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.5',
            'status' => 1,
        ]);
        $this->link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'stream' => 'A',
            'status' => 1,
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-04-30',
        ]);
        $this->type = ExamType::create([
            'name' => 'Mid Term',
            'code' => 'MID-TILES',
            'contributes_to_report_total' => false,
        ]);
        $this->maths = $this->subject('Mathematics', 'MTC');
        $this->english = $this->subject('English', 'ENG');
        $this->classHead = $this->teacher('Class Head', 'class.head@marks-tiles.test');
        $this->mathsTeacher = $this->teacher('Maths Teacher', 'maths.teacher@marks-tiles.test');
        $this->englishTeacher = $this->teacher('English Teacher', 'english.teacher@marks-tiles.test');
        $this->section->forceFill(['class_teacher_id' => $this->classHead->id])->save();
        $this->allocate($this->mathsTeacher, $this->maths);
        $this->allocate($this->englishTeacher, $this->english);
    }

    public function test_exams_to_mark_are_labelled_marks_needing_you_and_upcoming_exams_stay_on_the_board(): void
    {
        $this->exam($this->maths, $this->mathsTeacher, 'undone', now()->addDays(3));
        $past = $this->exam($this->english, $this->englishTeacher, 'undone', now()->subDay());

        $html = $this->page($this->mathsTeacher);

        $this->assertStringContainsString('data-testid="teacher-dashboard"', $html);
        $this->assertStringContainsString('data-testid="teacher-attention"', $html);
        $this->assertStringContainsString('Marks needing attention', $html);
        $this->assertMatchesRegularExpression('/data-testid="marks-needing-you-count">\s*1\s*</', $html);
        $this->assertStringContainsString('Marks needing you', $html);
        $this->assertStringContainsString('exams to mark', $html);
        $this->assertMatchesRegularExpression('/data-testid="upcoming-exams-count">\s*1\s*</', $html);
        $this->assertStringContainsString('Upcoming exams', $html);
        $this->assertStringContainsString('MATHEMATICS', $html);
        $this->assertStringNotContainsString('ENGLISH', $html);
        $this->assertStringNotContainsString('data-testid="release-marks-button"', $html);
        $this->assertStringContainsString('data-testid="teacher-corrections-empty"', $html);

        $classHead = $this->page($this->classHead);
        $this->assertMatchesRegularExpression('/data-testid="marks-needing-you-count">\s*2\s*</', $classHead);
        $this->assertMatchesRegularExpression('/data-testid="upcoming-exams-count">\s*1\s*</', $classHead);
        $this->assertStringContainsString('data-testid="release-marks-button"', $classHead);
        $this->assertStringContainsString('Marks Release', $classHead);
        $this->assertStringContainsString(route('teacher.attendance.register'), $classHead);
        $this->assertStringContainsString('MATHEMATICS', $classHead);
        $this->assertStringContainsString('ENGLISH', $classHead);
        $this->assertNotSame($past->id, 0);
    }

    public function test_a_subject_teacher_does_not_see_another_teachers_exam_or_the_release_button(): void
    {
        $this->exam($this->maths, $this->mathsTeacher, 'undone', now()->addDay());

        $html = $this->page($this->englishTeacher);

        $this->assertMatchesRegularExpression('/data-testid="marks-needing-you-count">\s*0\s*</', $html);
        $this->assertMatchesRegularExpression('/data-testid="upcoming-exams-count">\s*0\s*</', $html);
        $this->assertStringNotContainsString('MATHEMATICS', $html);
        $this->assertStringNotContainsString('data-testid="release-marks-button"', $html);
    }

    public function test_returned_work_is_listed_for_the_subject_teacher_and_the_class_head_only(): void
    {
        $exam = $this->exam($this->maths, $this->mathsTeacher, 'submitted', now()->subDays(2));
        ExamMarksSubmission::create([
            'school_id' => $this->school->id,
            'exam_id' => $exam->id,
            'class_id' => $this->section->id,
            'subject_id' => $this->maths->id,
            'teacher_id' => $this->mathsTeacher->id,
            'status' => 'reopened',
            'approval_status' => 'rejected',
            'rejection_reason' => 'Recheck the totals.',
        ]);

        $owner = $this->page($this->mathsTeacher);
        $this->assertStringContainsString('Marks &amp; corrections', $owner);
        $this->assertStringContainsString('data-testid="teacher-correction"', $owner);
        $this->assertStringContainsString('Recheck the totals.', $owner);
        $this->assertStringContainsString('data-testid="teacher-marks-entry-link"', $owner);
        $this->assertStringContainsString('Fix and resubmit', $owner);
        $this->assertMatchesRegularExpression('/data-testid="teacher-returned-count">\s*1\s*</', $owner);
        $this->assertMatchesRegularExpression('/data-testid="marks-needing-you-count">\s*0\s*</', $owner);

        $head = $this->page($this->classHead);
        $this->assertStringContainsString('Recheck the totals.', $head);
        $this->assertStringContainsString('data-testid="release-marks-button"', $head);

        $other = $this->page($this->englishTeacher);
        $this->assertStringNotContainsString('Recheck the totals.', $other);
        $this->assertStringNotContainsString('MATHEMATICS', $other);
        $this->assertStringContainsString('data-testid="teacher-corrections-empty"', $other);
    }

    public function test_another_schools_exams_stay_on_that_school(): void
    {
        $this->exam($this->maths, $this->mathsTeacher, 'undone', now()->addDay());

        $other = School::create([
            'name' => 'Other Marks School',
            'email' => 'other-marks@example.test',
            'phone' => '+256700888001',
            'slug' => 'other-marks',
            'status' => 1,
        ]);
        $year = AcademicYear::create([
            'school_id' => $other->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $other->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $other->id, 'name' => 'P.2', 'status' => 1,
        ]);
        StandardLink::create([
            'school_id' => $other->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
        $subject = Subject::create([
            'school_id' => $other->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Quarantine Subject',
            'code' => 'QRN',
            'type' => 'core',
            'status' => 1,
        ]);
        $stranger = $this->teacher('Other Head', 'other.head@other-marks.test', $other);
        $section->forceFill(['class_teacher_id' => $stranger->id])->save();
        Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $other->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $stranger->id,
            'exam_type_id' => $this->type->id,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $year->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'undone',
        ]));

        $home = $this->page($this->classHead);
        $this->assertStringContainsString('MATHEMATICS', $home);
        $this->assertStringNotContainsString('QUARANTINE SUBJECT', $home);

        $away = $this->page($stranger);
        $this->assertStringContainsString('QUARANTINE SUBJECT', $away);
        $this->assertStringNotContainsString('MATHEMATICS', $away);
        $this->assertStringNotContainsString('Recheck', $away);
        $this->assertStringContainsString('data-testid="release-marks-button"', $away);
    }

    public function test_release_button_follows_class_headship_and_the_school_switch(): void
    {
        $this->section->forceFill(['class_teacher_id' => null])->save();
        $this->link->forceFill(['class_teacher_id' => $this->mathsTeacher->id])->save();

        $streamHead = $this->page($this->mathsTeacher);
        $this->assertStringContainsString('data-testid="release-marks-button"', $streamHead);

        $subjectOnly = $this->page($this->englishTeacher);
        $this->assertStringNotContainsString('data-testid="release-marks-button"', $subjectOnly);

        SchoolDetail::create([
            'school_id' => $this->school->id,
            'meta_key' => SiteHelper::TEACHERS_MAY_RELEASE_MARKS_KEY,
            'meta_value' => '0',
        ]);
        SiteHelper::forgetTeachersMayReleaseMarks((int) $this->school->id);

        $switchedOff = $this->page($this->mathsTeacher);
        $this->assertStringNotContainsString('data-testid="release-marks-button"', $switchedOff);
        $this->assertStringContainsString('data-testid="teacher-dashboard"', $switchedOff);
    }

    public function test_phone_layout_keeps_two_columns_and_full_width_actions(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));
        $this->assertStringContainsString('.teacher-kpis { grid-template-columns: 1fr 1fr; }', $css);
        $this->assertStringContainsString('.teacher-actions > a { flex: 1 1 100%; min-height: 48px; }', $css);
        $this->assertStringContainsString('.teacher-row a { min-height: 44px; }', $css);
    }

    private function page(User $teacher): string
    {
        return $this->actingAs($teacher)->get('/teacher/dashboard')->assertOk()->getContent();
    }

    private function exam(Subject $subject, User $teacher, string $status, $when): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => $this->type->id,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => $when,
            'status' => $status,
        ]));
    }

    private function subject(string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'name' => $name,
            'code' => $code,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function allocate(User $teacher, Subject $subject): void
    {
        Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->link->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    private function teacher(string $name, string $email, ?School $school = null): User
    {
        $school = $school ?: $this->school;
        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name, 2)[1] ?? '',
        ]);

        return $user;
    }
}
