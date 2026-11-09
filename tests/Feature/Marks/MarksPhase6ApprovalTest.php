<?php

namespace Tests\Feature\Marks;

use App\Helpers\SiteHelper;
use App\Models\MarksRelease;
use App\Models\SchoolDetail;
use App\Services\DemoSchoolCommsGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Marks\Concerns\ReleaseRulesFixtures;
use Tests\TestCase;

/**
 * Phase 6a: approval is always required before a release, for every role, with
 * no reason field and no override path in the service, the controller or the
 * screen.
 */
class MarksPhase6ApprovalTest extends TestCase
{
    use RefreshDatabase;
    use ReleaseRulesFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config([
            'queue.default' => 'database',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => true,
        ]);
        DemoSchoolCommsGuard::flushCache();
        $this->relaxDeliveryLog();
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $this->buildSchools();
        $this->approveFollowAlongSittings();
        DemoSchoolCommsGuard::flushCache();
    }

    public function test_a_school_admin_cannot_release_before_approval_even_with_a_reason(): void
    {
        $this->disapproveFollowAlongSittings();

        $this->actingAs($this->admin())
            ->post('/admin/marks/releases', $this->scheduled() + [
                'override_reason' => 'Parents were promised these marks today.',
            ])
            ->assertSessionHasErrors('approval');

        $this->assertSame(0, MarksRelease::count());
        $this->assertNull(DB::table('activity_log')->where('description', 'Marks release scheduled')->first());
    }

    public function test_the_release_screen_offers_no_override_field(): void
    {
        $this->disapproveFollowAlongSittings();

        $this->actingAs($this->admin())
            ->get('/admin/marks/releases/new')
            ->assertOk()
            ->assertDontSee('data-testid="release-override-reason"', false)
            ->assertDontSee('Reason to release before approval')
            ->assertSee('data-testid="release-subject-approvals"', false);
    }

    public function test_a_class_teacher_cannot_release_before_approval_either(): void
    {
        $this->disapproveFollowAlongSittings();
        $teacher = $this->classTeacher();
        // The setting from 6b is on, so this test reaches the approval rule.
        SchoolDetail::create([
            'school_id' => $teacher->school_id,
            'meta_key' => SiteHelper::TEACHERS_MAY_RELEASE_MARKS_KEY,
            'meta_value' => '1',
        ]);
        SiteHelper::forgetTeachersMayReleaseMarks((int) $teacher->school_id);

        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload('demo', $this->fx['demo']['link']->id) + [
                'override_reason' => 'A teacher must not skip approval.',
            ])
            ->assertSessionHasErrors('approval');

        $this->assertSame(0, MarksRelease::count());
    }
}
