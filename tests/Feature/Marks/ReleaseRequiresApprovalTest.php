<?php

namespace Tests\Feature\Marks;

use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Helpers\SiteHelper;
use App\Models\MarksRelease;
use App\Models\SchoolDetail;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

class ReleaseRequiresApprovalTest extends TestCase
{
    use BuildsFollowAlongSchools;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $this->buildSchools();
    }

    public function test_release_is_refused_until_every_subject_is_approved(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/marks/releases', $this->payload())
            ->assertSessionHasErrors('approval');

        $this->assertSame(0, MarksRelease::count());
    }

    public function test_release_page_shows_approval_state_per_subject(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/marks/releases/new')
            ->assertOk()
            ->assertSee('data-testid="release-subject-approvals"', false)
            ->assertSee('MATHEMATICS · P.7 — Not submitted')
            ->assertSee('SCIENCE · P.7 — Not submitted')
            ->assertSee('ENGLISH · P.7 — Not submitted')
            ->assertSee('SOCIAL STUDIES · P.7 — Not submitted');
    }

    public function test_a_reason_does_not_release_an_unapproved_sitting_any_more(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/marks/releases', $this->scheduled() + [
                'override_reason' => 'Parents were promised these marks today.',
            ])
            ->assertSessionHasErrors('approval');

        $this->assertSame(0, MarksRelease::count());
        $this->assertNull(DB::table('activity_log')->where('description', 'Marks release scheduled')->first());
    }

    public function test_class_teacher_cannot_override_the_approval_rule(): void
    {
        $teacher = $this->fx['demo']['teacher'];
        StandardLink::where('school_id', $teacher->school_id)->update(['class_teacher_id' => $teacher->id]);
        // Class teachers may only reach this screen when the school allows it (6b).
        SchoolDetail::create([
            'school_id' => $teacher->school_id,
            'meta_key' => SiteHelper::TEACHERS_MAY_RELEASE_MARKS_KEY,
            'meta_value' => '1',
        ]);
        SiteHelper::forgetTeachersMayReleaseMarks((int) $teacher->school_id);

        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload($this->fx['demo']['link']->id) + [
                'override_reason' => 'A teacher must not skip approval.',
            ])
            ->assertSessionHasErrors('approval');

        $this->assertSame(0, MarksRelease::count());
    }

    public function test_several_classes_release_nothing_when_one_class_is_not_approved(): void
    {
        $this->approveFollowAlongSittings();

        $school = $this->fx['demo']['school'];
        $link = $this->fx['demo']['link'];
        $teacher = $this->fx['demo']['teacher'];
        $anchor = $this->fx['demo']['exams']['latest']['Mathematics'];
        Schema::table('sections', function ($table) {
            $table->dropUnique(['school_id', 'name']);
        });
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.7', 'status' => 1]);
        StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'standard_id' => $link->standard_id,
            'section_id' => $section->id,
            'stream' => 'B',
            'status' => 1,
        ]);
        $subject = Subject::create([
            'school_id' => $school->id,
            'standard_id' => $link->standard_id,
            'section_id' => $section->id,
            'academic_year_id' => $link->academic_year_id,
            'name' => 'Mathematics',
            'type' => 'core',
            'status' => 1,
        ]);
        $exam = Exam::create([
            'school_id' => $school->id,
            'standard_id' => $link->standard_id,
            'section_id' => $section->id,
            'academic_year_id' => $link->academic_year_id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'academic_term_id' => $anchor->academic_term_id,
            'exam_type_id' => $anchor->exam_type_id,
            'status' => 'undone',
            'scheduled_at' => '2026-11-21 09:00:00',
        ]);
        Marks::create([
            'student_id' => $this->fx['demo']['students'][0]['user']->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'exam_id' => $exam->id,
            'teacher_id' => $teacher->id,
            'school_id' => $school->id,
            'marks' => 70,
            'grade' => 'C',
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/marks/releases', $this->scheduled() + ['class' => 'P.7'])
            ->assertSessionHasErrors('approval');

        $this->assertSame(0, MarksRelease::count());
    }

    public function test_approved_subjects_can_be_released(): void
    {
        $this->approveFollowAlongSittings();

        $this->actingAs($this->admin())
            ->post('/admin/marks/releases', $this->scheduled())
            ->assertRedirect();

        $this->assertSame(1, MarksRelease::count());
        $log = DB::table('activity_log')->where('description', 'Marks release scheduled')->first();
        $this->assertStringNotContainsString('override_reason', (string) $log->properties);
    }

    private function admin(): User
    {
        $school = $this->fx['demo']['school'];
        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Admin demo',
            'email' => 'admin-approval@example.com',
            'password' => bcrypt('x'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Admin',
            'lastname' => 'Demo',
            'status' => 'active',
        ]);

        return $admin;
    }

    /** @return array<string, mixed> */
    private function payload(?int $linkId = null): array
    {
        $examId = (int) \App\Models\Academics\Exam::where('school_id', $this->fx['demo']['school']->id)
            ->whereHas('examType', fn ($q) => $q->where('code', 'EOT'))
            ->min('id');

        return [
            'exam_id' => $examId,
            'standard_link_id' => $linkId,
            'when' => 'now',
            'timezone' => 'Africa/Kampala',
        ];
    }

    /** @return array<string, mixed> */
    private function scheduled(?int $linkId = null): array
    {
        return array_merge($this->payload($linkId), [
            'when' => 'schedule',
            'scheduled_for' => '2026-12-01T09:00',
        ]);
    }
}
