<?php

namespace Tests\Feature\Marks;

use App\Helpers\SiteHelper;
use App\Models\MarksRelease;
use App\Models\SchoolDetail;
use App\Models\StandardLink;
use App\Services\DemoSchoolCommsGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Marks\Concerns\ReleaseRulesFixtures;
use Tests\TestCase;

/**
 * Phase 6b, revised: who may release.
 *
 * A school admin always may. A class teacher may release their own class —
 * every stream of it when they are its class head — and keeps that right
 * unless the school turns teachers_may_release_marks off. The switch is ON
 * when a school has never set it, because main already let a class teacher
 * release their stream's marks and an existing school must not lose that.
 */
class MarksPhase6WhoMayReleaseTest extends TestCase
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

    public function test_a_school_that_never_set_the_switch_keeps_letting_its_class_teacher_release(): void
    {
        $teacher = $this->classTeacher();
        $this->assertTrue(SiteHelper::teachersMayReleaseMarks((int) $teacher->school_id));

        $this->actingAs($teacher)->get('/teacher/marks/releases/new')->assertOk();
        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload('demo', $this->fx['demo']['link']->id))
            ->assertRedirect();

        $this->assertSame(1, MarksRelease::count());
    }

    public function test_a_school_that_turns_the_switch_off_closes_the_teacher_screens(): void
    {
        $teacher = $this->classTeacher();
        $this->turnOff($teacher->school_id);
        $this->assertFalse(SiteHelper::teachersMayReleaseMarks((int) $teacher->school_id));

        $this->actingAs($teacher)->get('/teacher/marks/releases/new')->assertForbidden();
        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload('demo', $this->fx['demo']['link']->id))
            ->assertForbidden();

        $this->assertSame(0, MarksRelease::count());
    }

    public function test_a_class_teacher_releases_their_class_when_the_switch_is_on(): void
    {
        $teacher = $this->classTeacher();
        $this->turnOn($teacher->school_id);

        $this->actingAs($teacher)->get('/teacher/marks/releases/new')->assertOk();
        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload('demo', $this->fx['demo']['link']->id))
            ->assertRedirect();

        $release = MarksRelease::sole();
        $this->assertSame('class', $release->scope);
        $this->assertSame((int) $this->fx['demo']['link']->id, (int) $release->standard_link_id);
        $this->assertSame((int) $teacher->id, (int) $release->created_by);
    }

    public function test_a_class_head_releases_every_stream_of_their_class(): void
    {
        $teacher = $this->classTeacher();
        $second = $this->addStream('demo', 'B');

        // Only the A stream names the teacher as class head.
        $this->assertNull(StandardLink::find($second->id)->class_teacher_id);

        $links = app(\App\Services\MarksRelease\MarksReleaseService::class)->links($teacher);
        $this->assertEqualsCanonicalizing(
            [(int) $this->fx['demo']['link']->id, (int) $second->id],
            $links->pluck('id')->map(fn ($id) => (int) $id)->all(),
        );

        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload('demo', $second->id))
            ->assertRedirect();

        $this->assertSame((int) $second->id, (int) MarksRelease::sole()->standard_link_id);
    }

    public function test_a_class_teacher_cannot_release_another_class_or_the_whole_school(): void
    {
        $teacher = $this->classTeacher();
        $other = $this->secondClass();

        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload('demo', $other->id))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload('demo', null))
            ->assertForbidden();

        $this->assertSame(0, MarksRelease::count());
    }

    public function test_a_school_admin_releases_whether_the_setting_is_off_or_on(): void
    {
        $admin = $this->admin();
        $this->assertTrue(SiteHelper::teachersMayReleaseMarks((int) $admin->school_id));

        $this->actingAs($admin)->get('/admin/marks/releases/new')->assertOk();
        $this->actingAs($admin)->post('/admin/marks/releases', $this->scheduled())->assertRedirect();

        $this->assertSame(1, MarksRelease::count());
        $this->assertSame('school', MarksRelease::sole()->scope);
    }

    public function test_saving_the_page_without_touching_the_release_switch_keeps_it_on(): void
    {
        $admin = $this->admin();
        $schoolId = (int) $admin->school_id;

        $html = $this->actingAs($admin)->get('/admin/settings/maintenancesettings')
            ->assertOk()
            ->assertSee('On by default')
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/name="teachers_may_release_marks"[^>]*\bchecked\b/',
            $html,
            'a school that has never saved the switch sees it on',
        );

        // The browser submits a checked box. Leaving it alone must not write '0'.
        $this->actingAs($admin)->post('/admin/settings/maintenancesettings', [
            'login_status' => 1,
            'teachers_may_release_marks' => 1,
        ])->assertRedirect();

        $this->assertSame('1', (string) SchoolDetail::query()
            ->where('school_id', $schoolId)
            ->where('meta_key', SiteHelper::TEACHERS_MAY_RELEASE_MARKS_KEY)
            ->value('meta_value'));
        $this->assertTrue(SiteHelper::teachersMayReleaseMarks($schoolId));

        // Unticking the box is an explicit off.
        $this->actingAs($admin)->post('/admin/settings/maintenancesettings', ['login_status' => 1])
            ->assertRedirect();

        $this->assertSame('0', (string) SchoolDetail::query()
            ->where('school_id', $schoolId)
            ->where('meta_key', SiteHelper::TEACHERS_MAY_RELEASE_MARKS_KEY)
            ->value('meta_value'));
        $this->assertFalse(SiteHelper::teachersMayReleaseMarks($schoolId));

        $off = $this->actingAs($admin)->get('/admin/settings/maintenancesettings')->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression(
            '/name="teachers_may_release_marks"[^>]*\bchecked\b/',
            $off,
        );

        $this->actingAs($admin)->post('/admin/settings/maintenancesettings', [
            'login_status' => 1,
            'teachers_may_release_marks' => 1,
        ])->assertRedirect();

        $this->assertTrue(SiteHelper::teachersMayReleaseMarks($schoolId));
    }

    private function turnOn(?int $schoolId): void
    {
        $this->setSwitch($schoolId, '1');
    }

    private function turnOff(?int $schoolId): void
    {
        $this->setSwitch($schoolId, '0');
    }

    private function setSwitch(?int $schoolId, string $value): void
    {
        SchoolDetail::create([
            'school_id' => $schoolId,
            'meta_key' => SiteHelper::TEACHERS_MAY_RELEASE_MARKS_KEY,
            'meta_value' => $value,
        ]);
        SiteHelper::forgetTeachersMayReleaseMarks((int) $schoolId);
    }

    /** A second stream in the same class (same section), as a school with streams has. */
    private function addStream(string $which = 'demo', string $stream = 'B'): StandardLink
    {
        $link = $this->fx[$which]['link'];

        return StandardLink::create([
            'school_id' => $link->school_id,
            'academic_year_id' => $link->academic_year_id,
            'standard_id' => $link->standard_id,
            'section_id' => $link->section_id,
            'stream' => $stream,
            'status' => 1,
        ]);
    }
}
