<?php

namespace Tests\Feature\Marks;

use App\Enums\ToshiMode;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Marks\MarksSubmissionService;
use App\Services\Parent\ParentPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ApproveReturnReleasePhoneTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private User $teacher;

    private User $admin;

    private User $otherAdmin;

    private User $student;

    private Exam $exam;

    private Section $section;

    private Section $otherSection;

    private Subject $subject;

    private AcademicYear $year;

    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = $this->school('North Ridge Demo', 'north-ridge-demo');
        $this->otherSchool = $this->school('South Ridge Demo', 'south-ridge-demo');
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026', 'description' => 'y',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term 3', 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-01', 'status' => 'current',
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $this->section = Section::create(['school_id' => $this->school->id, 'name' => 'P.4', 'status' => 1]);
        $this->otherSection = Section::create(['school_id' => $this->school->id, 'name' => 'P.5', 'status' => 1]);
        $this->teacher = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 5, 'name' => 'Ada Teacher',
            'email' => 'ada.teacher@north-ridge.test', 'status' => 'active',
        ]);
        $this->admin = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 3, 'name' => 'Nalongo Reviewer',
            'email' => 'nalongo@north-ridge.test', 'status' => 'active',
        ]);
        $this->otherAdmin = User::factory()->create([
            'school_id' => $this->otherSchool->id, 'usergroup_id' => 3, 'name' => 'Other Admin',
            'email' => 'admin@south-ridge.test', 'status' => 'active',
        ]);
        $this->student = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Kato Learner',
            'email' => 'kato@north-ridge.test', 'status' => 'active',
            'registration_number' => 'KLS0000042',
        ]);
        $this->subject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id, 'section_id' => $this->section->id,
            'name' => 'Mathematics', 'type' => 'core',
        ]);
        $this->exam = $this->examFor($this->section, $this->subject);
        $otherSubject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id, 'section_id' => $this->otherSection->id,
            'name' => 'English', 'type' => 'core',
        ]);
        $this->examFor($this->otherSection, $otherSubject);
        foreach ([$this->section, $this->otherSection] as $section) {
            StandardLink::create([
                'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
                'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1,
            ]);
        }
    }

    public function test_return_note_is_required_and_the_teacher_sees_it(): void
    {
        $this->mark($this->exam, $this->subject);
        app(MarksSubmissionService::class)->submit($this->exam, $this->teacher);

        $this->actingAs($this->admin)
            ->post(route('admin.marks.submissions.reject', $this->exam), [
                'class_id' => $this->section->id,
                'subject_id' => $this->subject->id,
                'return_reason' => '',
            ])
            ->assertSessionHasErrors('return_reason');
        $this->assertNull(ExamMarksSubmission::query()->value('rejection_reason'));

        $note = 'Please re-check section B and resubmit.';
        $this->actingAs($this->admin)
            ->post(route('admin.marks.submissions.reject', $this->exam), [
                'class_id' => $this->section->id,
                'subject_id' => $this->subject->id,
                'return_reason' => $note,
            ])
            ->assertRedirect();

        $list = $this->actingAs($this->teacher)->get(route('teacher.exam.marks'))->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="teacher-return-note"', $list);
        $this->assertStringContainsString($note, $list);
        $this->assertStringContainsString('Returned', $list);

        $enter = $this->actingAs($this->teacher)->get(route('teacher.exam.marks.enter', $this->exam))->assertOk()->getContent();
        $this->assertSame(1, substr_count($enter, 'data-testid="marks-return-reason"'));
        $this->assertStringContainsString($note, $enter);
    }

    public function test_release_button_names_the_exam_and_the_class_count(): void
    {
        $this->mark($this->exam, $this->subject);
        $other = Exam::query()->where('section_id', $this->otherSection->id)->first();
        $this->mark($other, Subject::query()->where('section_id', $this->otherSection->id)->first());

        $classCount = StandardLink::query()->where('school_id', $this->school->id)->distinct()->count('section_id');
        $this->assertSame(2, $classCount);

        $html = $this->actingAs($this->admin)->get(route('admin.marks.releases.create'))->assertOk()->getContent();
        $this->assertStringContainsString('Release Mid Term marks to 2 classes', $html);
        $this->assertStringContainsString('data-ka-filters', $html);
        $this->assertStringContainsString('data-exam-name="Mid Term"', $html);
        $this->assertStringContainsString('data-class-count="2"', $html);
    }

    public function test_re_release_reason_is_visible_in_the_history(): void
    {
        $this->mark($this->exam, $this->subject);
        $reason = 'Science marks were corrected after a re-check of two papers.';
        $link = StandardLink::query()->where('section_id', $this->section->id)->first();
        $release = MarksRelease::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'scope' => 'class',
            'standard_link_id' => $link->id,
            'scheduled_at' => '2026-10-28 09:00:00',
            'released_at' => '2026-10-28 09:00:00',
            'status' => MarksRelease::STATUS_RELEASED,
            'created_by' => $this->admin->id,
            'timezone' => 'Africa/Kampala',
            'whatsapp_send' => false,
            'release_reason' => $reason,
        ]);

        $index = $this->actingAs($this->admin)->get(route('admin.marks.releases.index'))->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="release-history-reason"', $index);
        $this->assertStringContainsString($reason, $index);
        $this->assertStringContainsString('Released on 28 Oct 2026 by Nalongo Reviewer', $index);

        $show = $this->actingAs($this->admin)->get(route('admin.marks.releases.show', $release->id))->assertOk()->getContent();
        $this->assertStringContainsString($reason, $show);
        $this->assertStringContainsString('data-testid="released-pill"', $show);
        $this->assertStringContainsString('Released on 28 Oct 2026 by Nalongo Reviewer', $show);

        $create = $this->actingAs($this->admin)->get(route('admin.marks.releases.create', ['exam_id' => $this->exam->id]))->assertOk()->getContent();
        $this->assertStringContainsString($reason, $create);
        $this->assertStringContainsString('data-testid="release-reason"', $create);
    }

    public function test_a_teacher_cannot_reopen_and_the_school_admin_can(): void
    {
        ExamMarksSubmission::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'class_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'locked',
            'approval_status' => 'approved',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs($this->teacher)
            ->post(route('admin.marks.submissions.reopen', $this->exam), [
                'class_id' => $this->section->id,
                'subject_id' => $this->subject->id,
                'reopen_reason' => 'A teacher must not reopen this.',
            ])
            ->assertRedirect('/teacher/dashboard');
        $this->assertSame('locked', ExamMarksSubmission::query()->value('status'));

        try {
            app(MarksSubmissionService::class)->reopen(
                $this->teacher,
                $this->exam,
                $this->section->id,
                $this->subject->id,
                'A teacher must not reopen this.',
            );
            $this->fail('a teacher cannot reopen');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->actingAs($this->otherAdmin)
            ->post(route('admin.marks.submissions.reopen', $this->exam), [
                'class_id' => $this->section->id,
                'subject_id' => $this->subject->id,
                'reopen_reason' => 'Another school must not reopen this.',
            ])
            ->assertForbidden();
        $this->assertSame('locked', ExamMarksSubmission::query()->value('status'));

        $page = $this->actingAs($this->admin)->get(route('admin.marks.submissions.show', $this->exam))->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="marks-reopen-form"', $page);
        $this->assertStringContainsString('name="reopen_reason"', $page);
        $this->assertStringContainsString('data-ka-filters', $this->actingAs($this->admin)->get(route('admin.marks.submissions'))->assertOk()->getContent());

        $reason = 'The head asked for a recount of two papers.';
        $this->actingAs($this->admin)
            ->post(route('admin.marks.submissions.reopen', $this->exam), [
                'class_id' => $this->section->id,
                'subject_id' => $this->subject->id,
                'reopen_reason' => $reason,
            ])
            ->assertRedirect();

        $row = ExamMarksSubmission::query()->first();
        $this->assertSame('reopened', $row->status);
        $this->assertSame($reason, $row->reopen_reason);
        $this->assertSame($this->admin->id, (int) $row->reopened_by);
    }

    public function test_a_head_teacher_reopens_without_hiding_the_released_mark(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->mark($this->exam, $this->subject);
        ExamMarksSubmission::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'class_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'locked',
            'approval_status' => 'approved',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);
        $link = StandardLink::query()->where('section_id', $this->section->id)->first();
        $link->forceFill(['class_teacher_id' => $this->teacher->id])->save();
        $release = MarksRelease::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'scope' => 'class',
            'standard_link_id' => $link->id,
            'scheduled_at' => '2026-10-28 09:00:00',
            'released_at' => '2026-10-28 09:00:00',
            'status' => MarksRelease::STATUS_RELEASED,
            'created_by' => $this->admin->id,
            'timezone' => 'Africa/Kampala',
            'whatsapp_send' => false,
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $link->id,
        ]);
        $parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'name' => 'Mara Kato',
            'email' => 'mara.kato@north-ridge.test',
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Mara',
            'lastname' => 'Kato',
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $this->student->id,
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'firstname' => 'Kato',
            'lastname' => 'Learner',
            'status' => 'active',
        ]);
        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $parent->id,
            'student_id' => $this->student->id,
            'status' => 1,
        ]);
        $head = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 4,
            'name' => 'Helen Adong',
            'email' => 'helen.adong@north-ridge.test',
            'status' => 'active',
        ]);
        $otherHead = User::factory()->create([
            'school_id' => $this->otherSchool->id,
            'usergroup_id' => 4,
            'name' => 'Kidepo Head',
            'email' => 'head@south-ridge.test',
            'status' => 'active',
        ]);

        $show = $this->actingAs($this->admin)->get(route('admin.marks.releases.show', $release))->assertOk()->getContent();
        $this->assertStringContainsString('Released and locked', $show);
        $this->assertStringContainsString('Released on 28 Oct 2026 by Nalongo Reviewer', $show);
        $this->assertStringContainsString('data-testid="release-reopen"', $show);
        $this->assertStringContainsString('Reopen P.4 · Mid Term?', $show);
        $this->assertStringContainsString('Families keep seeing the released results until you release again.', $show);
        $this->assertStringContainsString('Your reason is kept in this history. It isn\'t sent to families.', $show);
        $this->assertStringContainsString('Reopen P.4 marks', $show);
        $this->assertStringContainsString('Admins and head teachers only', $show);

        $teacherPage = $this->actingAs($this->teacher)->get(route('teacher.marks.releases.show', $release))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-testid="release-reopen"', $teacherPage);

        $this->actingAs($head)->get(route('admin.marks.releases.show', $release))->assertOk();
        $this->actingAs($otherHead)->get(route('admin.marks.releases.show', $release))->assertNotFound();

        $this->actingAs($this->teacher)
            ->post(route('admin.marks.releases.reopen', $release), [
                'reason' => 'A teacher must not reopen this.',
            ])
            ->assertRedirect('/teacher/dashboard');
        $this->actingAs($head)
            ->from(route('admin.marks.releases.show', $release))
            ->post(route('admin.marks.releases.reopen', $release), ['reason' => ''])
            ->assertRedirect(route('admin.marks.releases.show', $release))
            ->assertSessionHasErrors('reason');
        $this->actingAs($otherHead)
            ->post(route('admin.marks.releases.reopen', $release), [
                'reason' => 'Another school must not reopen this.',
            ])
            ->assertNotFound();

        $this->assertSame('locked', ExamMarksSubmission::query()->value('status'));
        $this->assertSame(MarksRelease::STATUS_RELEASED, $release->fresh()->status);

        $reason = 'The head asked for a recount of two papers.';
        $this->actingAs($head)
            ->post(route('admin.marks.releases.reopen', $release), ['reason' => $reason])
            ->assertRedirect(route('admin.marks.releases.show', $release));

        $row = ExamMarksSubmission::query()->first();
        $this->assertSame('reopened', $row->status);
        $this->assertSame($reason, $row->reopen_reason);
        $this->assertSame($head->id, (int) $row->reopened_by);
        $this->assertSame(MarksRelease::STATUS_RELEASED, $release->fresh()->status);

        $grades = app(ParentPortalService::class)->grades($parent, null, $this->student->id);
        $this->assertTrue($grades['success'] ?? false, json_encode($grades));
        $this->assertSame(70.0, (float) $grades['data']['exam_groups'][0]['subjects'][0]['score']);
        $page = $this->actingAs($parent)->get(route('parent.children.grades', $this->student))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/scope="row">MATHEMATICS<\/th>\s*<td>70<\/td>/', $page);
    }

    private function school(string $name, string $slug): School
    {
        $school = School::create([
            'name' => $name,
            'slug' => $slug,
            'email' => $slug.'@example.test',
            'phone' => '+256700'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_mode' => ToshiMode::Preview,
            'toshi_enabled' => 0,
        ]);
        $school->forceFill(['is_demo' => 1, 'toshi_mode' => ToshiMode::Preview, 'toshi_enabled' => 0])->save();

        return $school->fresh();
    }

    private function examFor(Section $section, Subject $subject): Exam
    {
        return Exam::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'standard_id' => $subject->standard_id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => 1,
            'status' => 'undone',
        ]);
    }

    private function mark(Exam $exam, Subject $subject): void
    {
        Marks::create([
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'section_id' => $exam->section_id,
            'exam_id' => $exam->id,
            'teacher_id' => $this->teacher->id,
            'school_id' => $this->school->id,
            'marks' => 70,
            'grade' => 'C',
        ]);
    }
}
