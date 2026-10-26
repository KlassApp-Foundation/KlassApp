<?php

namespace Tests\Feature\Marks;

use App\Models\MarksRelease;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Marks\Concerns\ReleaseRulesFixtures;
use Tests\TestCase;

/**
 * Phase 6d: the per-release WhatsApp switch and the template gate.
 *
 * With the switch off, a release still happens and nothing is sent. With it on,
 * a real-school send waits for results_released to be approved by Meta, skips a
 * parent who opted out or whose link is not approved, and only ever messages
 * parents of the students in the released class and sitting.
 *
 * The demo follow-along path is unchanged: those sends are not a school sending
 * a template (see marks-flow.md, decision 3). The demo-only recipient guard is
 * still the last line for a real number; these tests lower that deployment flag
 * to prove the release gates themselves, which is what has to hold when the
 * guard is lifted.
 */
class MarksPhase6WhatsAppGateTest extends TestCase
{
    use RefreshDatabase;
    use ReleaseRulesFixtures;

    private const REAL_PARENT_MOBILE = '256780000001';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config([
            'queue.default' => 'database',
            'services.whatsapp.replies_enabled' => true,
            // The release gates, not the deployment guard, are under test here.
            'services.whatsapp.demo_only_recipients' => false,
            'whatsapp.approved_templates' => [],
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
        $this->fakeGraph();
    }

    private function approveTemplate(bool $approved = true): void
    {
        config(['whatsapp.approved_templates' => $approved ? ['results_released'] : []]);
    }

    private function realRelease(array $extra = [], ?int $linkId = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin('real'))->post('/admin/marks/releases', array_merge(
            $this->payload('real', $linkId ?? (int) $this->fx['real']['link']->id),
            $extra,
        ));
    }

    private function work(): void
    {
        $this->artisan('queue:work', ['--stop-when-empty' => true, '--sleep' => 0, '--memory' => 4096])->assertExitCode(0);
    }

    private function parentFor(string $which, User $student, string $name, string $phone, bool $approved, bool $optedIn): User
    {
        $school = $this->fx[$which]['school'];
        $parent = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'name' => $name,
            'email' => str()->slug($name) . '-' . $school->id . '@example.com',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'firstname' => $name,
            'lastname' => '',
            'status' => 'active',
        ]);
        StudentParentLink::create([
            'school_id' => $school->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => $approved ? 1 : 0,
        ]);
        WhatsAppUser::create([
            'phone' => '+' . $phone,
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'opted_in' => $optedIn,
        ]);

        return $parent;
    }

    private function pupilIn(string $which, StandardLink $link, string $name): User
    {
        $fx = $this->fx[$which];
        $pupil = User::create([
            'school_id' => $fx['school']->id,
            'usergroup_id' => 6,
            'name' => $name,
            'email' => str()->slug($name) . '-' . $fx['school']->id . '@example.com',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $fx['school']->id,
            'academic_year_id' => $fx['link']->academic_year_id,
            'user_id' => $pupil->id,
            'standardLink_id' => $link->id,
        ]);

        return $pupil;
    }

    private function reasons(): array
    {
        return MarksRelease::sole()->result['reasons'] ?? [];
    }

    public function test_the_switch_is_off_by_default_and_then_a_release_sends_nothing(): void
    {
        $this->approveTemplate();

        $this->realRelease()->assertRedirect();

        $release = MarksRelease::sole();
        $this->assertFalse((bool) $release->whatsapp_send);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame([], $this->outbound());
        $this->assertSame(['WhatsApp was switched off for this release' => 1], $this->reasons());
    }

    public function test_the_switch_on_still_waits_for_meta_to_approve_the_template(): void
    {
        $this->approveTemplate(false);

        $this->realRelease(['whatsapp_send' => '1'])->assertRedirect();

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame([], $this->outbound());
        $this->assertSame(['results_released is not approved by Meta yet' => 1], $this->reasons());
    }

    public function test_an_approved_release_messages_only_the_approved_opted_in_parents_of_the_released_class(): void
    {
        $this->approveTemplate();
        $real = $this->fx['real'];
        $student = $real['students'][0]['user'];

        // Repelled in two different ways, and one parent of a pupil in another class.
        $this->parentFor('real', $student, 'Opted Out Parent', self::REAL_PARENT_MOBILE, true, false);
        $this->parentFor('real', $student, 'Unapproved Parent', '256780000002', false, true);
        $otherLink = $this->secondClass('real', 'P.5');
        $otherPupil = $this->pupilIn('real', $otherLink, 'Pupil Next Door');
        $this->parentFor('real', $otherPupil, 'Next Door Parent', '256780000003', true, true);

        $this->realRelease(['whatsapp_send' => '1'], (int) $real['link']->id)->assertRedirect();

        $this->assertSame(1, DB::table('jobs')->count(), 'one approved, opted-in parent of this class');
        $this->work();

        $phone = WhatsAppUser::query()
            ->where('school_id', $real['school']->id)
            ->where('user_id', $real['students'][0]['parent']->id)
            ->value('phone');
        $this->assertSame([ltrim((string) $phone, '+')], array_column($this->outbound(), 'to'));

        $reasons = $this->reasons();
        $this->assertSame(1, $reasons['This parent opted out of WhatsApp results']);
        $this->assertSame(1, $reasons['This parent link is not approved']);
        $this->assertArrayNotHasKey('No approved, linked parent for this student', $reasons);

        $names = array_column(MarksRelease::sole()->result['messages'], 'name');
        $this->assertNotContains('Pupil Next Door', $names, 'a pupil in another class is never in scope');
        $this->assertSame(1, MarksRelease::sole()->result['sent']);
    }

    public function test_the_job_rechecks_the_template_so_a_lost_approval_sends_nothing(): void
    {
        $this->approveTemplate();
        $this->realRelease(['whatsapp_send' => '1'])->assertRedirect();
        $this->assertSame(1, DB::table('jobs')->count());

        // Approval is withdrawn between queueing and sending.
        $this->approveTemplate(false);
        $this->work();

        $this->assertSame([], $this->outbound());
        $entry = collect(MarksRelease::sole()->result['messages'])->firstWhere('status', 'skipped');
        $this->assertSame('results_released is not approved by Meta yet', $entry['reason']);
    }

    public function test_a_demo_release_keeps_its_follow_along_sends_with_the_release_switch_off(): void
    {
        $this->bind('256700100001', (int) $this->fx['demo']['students'][0]['user']->id);

        $this->actingAs($this->admin())->post('/admin/marks/releases', $this->payload())->assertRedirect();
        $this->work();

        $this->assertCount(1, $this->outbound(), 'the demo follow-along path is unchanged');
        $this->assertFalse((bool) MarksRelease::sole()->whatsapp_send);
    }
}
