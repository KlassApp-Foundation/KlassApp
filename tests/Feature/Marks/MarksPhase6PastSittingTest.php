<?php

namespace Tests\Feature\Marks;

use App\Models\MarksRelease;
use App\Services\DemoSchoolCommsGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Marks\Concerns\ReleaseRulesFixtures;
use Tests\TestCase;

/**
 * Phase 6c: a sitting from an earlier term or academic year than the school's
 * current one needs an explicit confirmation. The current term's sitting never
 * asks, so the ordinary release keeps its one click.
 */
class MarksPhase6PastSittingTest extends TestCase
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
        Cache::flush();
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

    public function test_a_sitting_from_an_earlier_term_is_refused_without_confirmation(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/marks/releases', $this->payload('demo', null, $this->earlierExamId()))
            ->assertSessionHasErrors('confirm_past');

        $this->assertSame(0, MarksRelease::count());
    }

    public function test_the_same_sitting_releases_once_the_confirmation_is_sent(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/marks/releases', $this->scheduled('demo', null, $this->earlierExamId()) + ['confirm_past' => '1'])
            ->assertRedirect();

        $this->assertSame(1, MarksRelease::count());
        $this->assertSame($this->earlierExamId(), (int) MarksRelease::sole()->exam_id);
    }

    public function test_the_current_terms_sitting_never_asks(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/marks/releases', $this->scheduled())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, MarksRelease::count());
    }

    public function test_the_screen_asks_for_the_confirmation_only_for_a_past_sitting(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/marks/releases/new?exam_id=' . $this->earlierExamId())
            ->assertOk()
            ->assertSee('data-testid="release-confirm-past"', false)
            ->assertSee('earlier term');

        $this->actingAs($admin)
            ->get('/admin/marks/releases/new?exam_id=' . $this->latestExamId())
            ->assertOk()
            ->assertDontSee('data-testid="release-confirm-past"', false);
    }

    public function test_a_class_teacher_releasing_a_past_sitting_needs_the_same_confirmation(): void
    {
        $teacher = $this->classTeacher();
        \App\Models\SchoolDetail::create([
            'school_id' => $teacher->school_id,
            'meta_key' => \App\Helpers\SiteHelper::TEACHERS_MAY_RELEASE_MARKS_KEY,
            'meta_value' => '1',
        ]);
        \App\Helpers\SiteHelper::forgetTeachersMayReleaseMarks((int) $teacher->school_id);

        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload('demo', $this->fx['demo']['link']->id, $this->earlierExamId()))
            ->assertSessionHasErrors('confirm_past');

        $this->assertSame(0, MarksRelease::count());
    }
}
