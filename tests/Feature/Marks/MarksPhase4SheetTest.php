<?php

namespace Tests\Feature\Marks;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
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

class MarksPhase4SheetTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private User $admin;

    private User $teacher;

    private User $otherTeacher;

    private User $ada;

    private User $bea;

    private Section $section;

    private Section $otherSection;

    private StandardLink $east;

    private StandardLink $west;

    private AcademicTerm $term;

    private Subject $english;

    private Exam $englishExam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Sheet School', 'slug' => 'sheet-school',
            'email' => 'sheet@p4.test', 'phone' => '+256700000041',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $this->otherSchool = School::create([
            'name' => 'Other Sheet School', 'slug' => 'sheet-other',
            'email' => 'other@p4.test', 'phone' => '+256700000042',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'description' => 'AY 2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'name' => 'Term I', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $this->section = Section::create([
            'school_id' => $this->school->id, 'name' => 'P.7', 'status' => 1,
        ]);
        $this->teacher = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 5,
            'name' => 'Class Teacher', 'email' => 'class@p4.test', 'status' => 'active',
        ]);
        $this->otherTeacher = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 5,
            'name' => 'Other Teacher', 'email' => 'other.teacher@p4.test', 'status' => 'active',
        ]);
        $this->section->class_teacher_id = $this->teacher->id;
        $this->section->save();

        $this->east = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $this->section->id,
            'class_teacher_id' => $this->teacher->id, 'stream' => 'East', 'status' => 1,
        ]);
        $this->west = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $this->section->id,
            'class_teacher_id' => $this->teacher->id, 'stream' => 'West', 'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 3,
            'name' => 'Sheet Admin', 'email' => 'admin@p4.test', 'status' => 'active',
        ]);
        $this->ada = $this->learner('Ada East', $this->east, $year);
        $this->bea = $this->learner('Bea West', $this->west, $year);

        $this->english = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $this->section->id,
            'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        SchoolGradingSystem::create([
            'school_id' => $this->school->id, 'standard_id' => $standard->id,
            'grade' => 'C3', 'points' => 3, 'min_score' => 0, 'max_score' => 100, 'remark' => 'C3',
        ]);

        $this->englishExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->english->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => 2,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $year->id,
            'scheduled_at' => '2026-04-01',
            'status' => 'undone',
        ]));
        $this->mark($this->ada, 80);
        $this->mark($this->bea, 70);
        ExamMarksSubmission::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->englishExam->id,
            'class_id' => $this->section->id,
            'subject_id' => $this->english->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'submitted',
            'approval_status' => 'pending',
        ]);

        $otherYear = AcademicYear::create([
            'school_id' => $this->otherSchool->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $this->otherSection = Section::create([
            'school_id' => $this->otherSchool->id, 'name' => 'S.1', 'status' => 1,
        ]);
        AcademicTerm::create([
            'school_id' => $this->otherSchool->id, 'academic_year_id' => $otherYear->id,
            'name' => 'Term I', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);
    }

    public function test_the_menu_opens_one_sitting_for_the_whole_class(): void
    {
        $this->assertSame('admin/marks', $this->childUrl('Exams and marks'));

        $empty = $this->actingAs($this->admin)->get(route('admin.marks'))->assertOk()->getContent();
        $this->assertStringContainsString('Ready when you are', $empty);
        $this->assertStringContainsString('data-testid="marks-stream-filter"', $empty);

        $html = $this->actingAs($this->admin)->get(route('admin.marks.filter', [
            'class' => $this->section->id,
            'term' => $this->term->id,
            'examType' => 2,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('Ada East', $html);
        $this->assertStringContainsString('Bea West', $html);
        $this->assertStringContainsString('data-testid="marks-stream"', $html);
        $this->assertStringContainsString('East', $html);
        $this->assertStringContainsString('West', $html);
        $this->assertStringContainsString('Submitted', $html);
        $this->assertStringContainsString('1 of 2', $html);
        $this->assertStringContainsString('2 of 2', $html);
        $this->assertStringContainsString('data-testid="marks-cell-grade">C3', $html);
        $this->assertStringNotContainsString('>primary<', $html);
        $this->assertStringNotContainsString('marks->sum', file_get_contents(resource_path('views/admin/marks/results-table2.blade.php')));
        $this->assertStringNotContainsString('Current Academic Year', file_get_contents(app_path('Http/Controllers/Admin/DownloadMarksSheet.php')));
        $this->assertStringNotContainsString('+ Add Marks', file_get_contents(resource_path('views/teacher/marks/view.blade.php')));
        $this->assertStringNotContainsString('All Classes', file_get_contents(resource_path('views/teacher/marks/view.blade.php')));
        $this->assertStringNotContainsString('All Exams', file_get_contents(resource_path('views/teacher/marks/view.blade.php')));

        $west = $this->actingAs($this->admin)->get(route('admin.marks.filter', [
            'class' => $this->section->id,
            'term' => $this->term->id,
            'examType' => 2,
            'stream' => $this->west->id,
        ]))->assertOk()->getContent();
        $this->assertStringContainsString('Bea West', $west);
        $this->assertStringNotContainsString('Ada East', $west);
        $this->assertStringContainsString('1 of 1', $west);

        $this->actingAs($this->admin)->get(route('admin.marks.filter', [
            'class' => $this->otherSection->id,
            'term' => $this->term->id,
            'examType' => 2,
        ]))->assertNotFound();

        $this->actingAs($this->admin)->get(route('admin.marksheet.download', [
            'class' => $this->section->id,
            'term' => $this->term->id,
            'examType' => 2,
        ]))->assertOk()->assertHeader('content-disposition');
    }

    public function test_a_promoted_learner_still_appears_on_the_old_sitting(): void
    {
        $nextYear = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2027',
            'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'status' => 0,
        ]);
        $nextSection = Section::create([
            'school_id' => $this->school->id, 'name' => 'S.1', 'status' => 1,
        ]);
        $nextLink = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $nextYear->id,
            'standard_id' => $this->east->standard_id, 'section_id' => $nextSection->id,
            'stream' => 'North', 'status' => 1,
        ]);
        StudentAcademic::query()->where('user_id', $this->ada->id)->update([
            'standardLink_id' => $nextLink->id,
            'academic_year_id' => $nextYear->id,
        ]);

        $html = $this->actingAs($this->admin)->get(route('admin.marks.filter', [
            'class' => $this->section->id,
            'term' => $this->term->id,
            'examType' => 2,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('Ada East', $html);
        $this->assertStringContainsString('Bea West', $html);
    }

    public function test_the_class_teacher_opens_the_same_sheet_and_another_teacher_does_not(): void
    {
        $html = $this->actingAs($this->teacher)->get(route('teacher.marks.sheet', [
            'class' => $this->section->id,
            'term' => $this->term->id,
            'examType' => 2,
            'stream' => $this->east->id,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('Ada East', $html);
        $this->assertStringNotContainsString('Bea West', $html);
        $this->assertStringContainsString('1 of 1', $html);
        $this->assertStringContainsString('Submitted', $html);

        $this->actingAs($this->teacher)->get(route('teacher.marks.sheet.download', [
            'class' => $this->section->id,
            'term' => $this->term->id,
            'examType' => 2,
        ]))->assertOk();

        $this->actingAs($this->otherTeacher)->get(route('teacher.marks.sheet', [
            'class' => $this->section->id,
            'term' => $this->term->id,
            'examType' => 2,
        ]))->assertForbidden();

        $this->assertStringContainsString(
            'teacher.marks.sheet',
            file_get_contents(resource_path('views/teacher/marks/teacher-exam-list.blade.php'))
        );
    }

    private function learner(string $name, StandardLink $link, AcademicYear $year): User
    {
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@p4.test',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $user->id,
            'standardLink_id' => $link->id,
        ]);

        return $user;
    }

    private function mark(User $student, int $score): void
    {
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $this->englishExam->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->english->id,
            'teacher_id' => $this->teacher->id,
            'section_id' => $this->section->id,
            'marks' => $score,
            'grade' => 'stored',
        ]);
    }

    private function childUrl(string $label): ?string
    {
        $found = null;
        $walk = function ($node) use (&$walk, &$found, $label): void {
            if ($found !== null || ! is_array($node)) {
                return;
            }
            if (($node['label'] ?? null) === $label && isset($node['url'])) {
                $found = $node['url'];

                return;
            }
            foreach ($node as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };
        $walk(config('navigation.roles'));

        return $found;
    }
}
