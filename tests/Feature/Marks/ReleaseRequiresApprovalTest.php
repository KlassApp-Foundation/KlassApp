<?php

namespace Tests\Feature\Marks;

use App\Models\MarksRelease;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_admin_override_with_a_reason_releases_and_is_logged(): void
    {
        $admin = $this->admin();
        $reason = 'Parents were promised these marks today.';

        $this->actingAs($admin)
            ->post('/admin/marks/releases', $this->scheduled() + ['override_reason' => $reason])
            ->assertRedirect();

        $this->assertSame(1, MarksRelease::count());
        $log = DB::table('activity_log')->where('description', 'Marks release scheduled')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString($reason, (string) $log->properties);
        $this->assertSame((int) $admin->id, (int) $log->causer_id);
    }

    public function test_class_teacher_cannot_override_the_approval_rule(): void
    {
        $teacher = $this->fx['demo']['teacher'];
        StandardLink::where('school_id', $teacher->school_id)->update(['class_teacher_id' => $teacher->id]);

        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload($this->fx['demo']['link']->id) + [
                'override_reason' => 'A teacher must not skip approval.',
            ])
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
