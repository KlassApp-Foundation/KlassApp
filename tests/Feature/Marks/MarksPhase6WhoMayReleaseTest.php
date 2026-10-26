<?php

namespace Tests\Feature\Marks;

use App\Helpers\SiteHelper;
use App\Models\MarksRelease;
use App\Models\SchoolDetail;
use App\Services\DemoSchoolCommsGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Marks\Concerns\ReleaseRulesFixtures;
use Tests\TestCase;

/**
 * Phase 6b: who may release. A school admin always may. A class teacher may
 * release only their own class, and only when the school has turned on
 * teachers_may_release_marks. The setting is off by default, so nothing changes
 * for a school that never turns it on.
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

    public function test_a_class_teacher_cannot_release_while_the_school_setting_is_off(): void
    {
        $teacher = $this->classTeacher();
        $this->assertFalse(SiteHelper::teachersMayReleaseMarks((int) $teacher->school_id));

        $this->actingAs($teacher)->get('/teacher/marks/releases/new')->assertForbidden();
        $this->actingAs($teacher)
            ->post('/teacher/marks/releases', $this->payload('demo', $this->fx['demo']['link']->id))
            ->assertForbidden();

        $this->assertSame(0, MarksRelease::count());
    }

    public function test_a_class_teacher_can_release_their_own_class_once_the_setting_is_on(): void
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

    public function test_a_class_teacher_cannot_release_another_class_or_the_whole_school_even_when_the_setting_is_on(): void
    {
        $teacher = $this->classTeacher();
        $this->turnOn($teacher->school_id);
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
        $this->assertFalse(SiteHelper::teachersMayReleaseMarks((int) $admin->school_id));

        $this->actingAs($admin)->get('/admin/marks/releases/new')->assertOk();
        $this->actingAs($admin)->post('/admin/marks/releases', $this->scheduled())->assertRedirect();

        $this->assertSame(1, MarksRelease::count());
        $this->assertSame('school', MarksRelease::sole()->scope);
    }

    public function test_the_settings_page_saves_the_switch_and_it_is_off_until_someone_turns_it_on(): void
    {
        $admin = $this->admin();
        $schoolId = (int) $admin->school_id;

        $this->actingAs($admin)->get('/admin/settings/maintenancesettings')
            ->assertOk()
            ->assertSee('name="teachers_may_release_marks"', false);

        $this->actingAs($admin)->post('/admin/settings/maintenancesettings', [
            'login_status' => 1,
            'teachers_may_release_marks' => 1,
        ])->assertRedirect();

        $this->assertSame('1', (string) SchoolDetail::query()
            ->where('school_id', $schoolId)
            ->where('meta_key', SiteHelper::TEACHERS_MAY_RELEASE_MARKS_KEY)
            ->value('meta_value'));
        $this->assertTrue(SiteHelper::teachersMayReleaseMarks($schoolId));

        $this->actingAs($admin)->post('/admin/settings/maintenancesettings', ['login_status' => 1]);

        $this->assertFalse(SiteHelper::teachersMayReleaseMarks($schoolId));
    }

    private function turnOn(?int $schoolId): void
    {
        SchoolDetail::create([
            'school_id' => $schoolId,
            'meta_key' => SiteHelper::TEACHERS_MAY_RELEASE_MARKS_KEY,
            'meta_value' => '1',
        ]);
        SiteHelper::forgetTeachersMayReleaseMarks((int) $schoolId);
    }
}
