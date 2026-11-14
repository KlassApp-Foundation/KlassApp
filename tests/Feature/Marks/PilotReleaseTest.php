<?php

namespace Tests\Feature\Marks;

use App\Models\MarksRelease;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Marks\Concerns\ReleaseRulesFixtures;
use Tests\TestCase;

/**
 * A pilot release messages only the chosen parents or learners.
 * The rest of the class is not told. A person who is not linked, or who has
 * no number, is refused and written on the delivery log.
 */
class PilotReleaseTest extends TestCase
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
            'services.whatsapp.demo_only_recipients' => false,
            'services.whatsapp.template_language' => 'en',
            'whatsapp.approved_templates' => ['results_released'],
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

    public function test_the_release_page_offers_a_chosen_list(): void
    {
        $this->actingAs($this->admin('real'))
            ->get('/admin/marks/releases/new?standard_link_id='.$this->fx['real']['link']->id)
            ->assertOk()
            ->assertSee('Who receives this release', false)
            ->assertSee('Everyone in this class', false)
            ->assertSee('Only the people I choose', false)
            ->assertSee('Parent of Zed', false)
            ->assertSee('data-testid="release-pilot"', false);
    }

    public function test_the_preview_names_only_the_chosen_parent_and_the_message(): void
    {
        [$first, $firstParent, $secondParent] = $this->twoFamilies();

        $response = $this->actingAs($this->admin('real'))->post('/admin/marks/releases/preview', array_merge(
            $this->payload('real', (int) $this->fx['real']['link']->id),
            [
                'pilot' => 'chosen',
                'pilot_parents' => [$first->id.':'.$firstParent->id],
                'whatsapp_send' => '1',
            ],
        ));

        $response->assertOk()
            ->assertSee('data-testid="release-pilot-preview"', false)
            ->assertSee('Will be told', false)
            ->assertSee('Parent of Zed', false)
            ->assertSee('Zed Realpupil', false)
            ->assertSee('has released the', false)
            ->assertSee('The rest of the class is not told.', false)
            ->assertDontSee('Second Parent', false);
        $this->assertSame(0, MarksRelease::count());
        $this->assertSame([], $this->outbound());
    }

    public function test_a_pilot_release_messages_only_the_chosen_parent(): void
    {
        [$first, $firstParent] = $this->twoFamilies();
        $admin = $this->admin('real');
        $chosenPhone = $this->phoneOf($firstParent);
        $otherPhone = $this->phoneOf(User::query()->where('school_id', $first->school_id)->where('name', 'Second Parent')->firstOrFail());

        $this->actingAs($admin)->post('/admin/marks/releases', array_merge(
            $this->payload('real', (int) $this->fx['real']['link']->id),
            [
                'pilot' => 'chosen',
                'pilot_parents' => [$first->id.':'.$firstParent->id],
                'whatsapp_send' => '1',
                'pilot_confirmed' => '1',
            ],
        ))->assertRedirect();

        $this->assertSame(1, DB::table('jobs')->count());
        $this->work();

        $this->assertSame([$chosenPhone], array_column($this->outbound(), 'to'));
        $this->assertNotContains($otherPhone, array_column($this->outbound(), 'to'));

        $release = MarksRelease::sole();
        $this->assertIsArray($release->pilot_recipients);
        $names = array_column($release->result['messages'], 'name');
        $this->assertContains('Zed Realpupil', $names);
        $this->assertNotContains('Second Learner', $names);
        $this->assertSame(1, $release->result['sent']);

        $this->actingAs($admin)
            ->get('/admin/marks/releases/'.$release->id)
            ->assertOk()
            ->assertSee('Only the people who were chosen', false)
            ->assertSee('data-testid="release-recipient"', false)
            ->assertSee('Parent of Zed', false)
            ->assertDontSee('Second Learner', false);
    }

    public function test_the_first_post_of_a_pilot_does_not_release(): void
    {
        [$first, $firstParent] = $this->twoFamilies();

        $this->actingAs($this->admin('real'))->post('/admin/marks/releases', array_merge(
            $this->payload('real', (int) $this->fx['real']['link']->id),
            [
                'pilot' => 'chosen',
                'pilot_parents' => [$first->id.':'.$firstParent->id],
                'whatsapp_send' => '1',
            ],
        ))->assertOk()->assertSee('Will be told', false);

        $this->assertSame(0, MarksRelease::count());
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame([], $this->outbound());
    }

    public function test_choosing_a_learner_messages_that_learners_parent_only(): void
    {
        [$first] = $this->twoFamilies();
        $firstParent = $this->fx['real']['students'][0]['parent'];
        $other = User::query()->where('name', 'Second Parent')->where('school_id', $first->school_id)->firstOrFail();

        $this->releasePilot(['pilot_learners' => [$first->id]]);
        $this->work();

        $this->assertSame([$this->phoneOf($firstParent)], array_column($this->outbound(), 'to'));
        $this->assertNotContains($this->phoneOf($other), array_column($this->outbound(), 'to'));
    }

    public function test_a_parent_who_is_not_linked_is_refused_and_nobody_else_is_told(): void
    {
        [$first] = $this->twoFamilies();
        $stranger = $this->makeParent('Unlinked Parent');
        WhatsAppUser::create([
            'phone' => '+256780000088',
            'user_id' => $stranger->id,
            'school_id' => $first->school_id,
            'opted_in' => true,
        ]);

        $this->releasePilot(['pilot_parents' => [$first->id.':'.$stranger->id]]);
        $this->work();

        $this->assertSame([], $this->outbound());
        $this->assertSame(0, DB::table('jobs')->count());
        $release = MarksRelease::sole();
        $this->assertSame(['This parent is not linked to this learner' => 1], $release->result['reasons']);
        $this->assertNotContains('Second Learner', array_column($release->result['messages'], 'name'));
    }

    public function test_a_chosen_parent_with_no_number_is_refused(): void
    {
        [$first] = $this->twoFamilies();
        $quiet = $this->makeParent('Quiet Parent');
        StudentParentLink::create([
            'school_id' => $first->school_id,
            'parent_id' => $quiet->id,
            'student_id' => $first->id,
            'status' => 1,
        ]);

        $this->releasePilot(['pilot_parents' => [$first->id.':'.$quiet->id]]);

        $this->assertSame([], $this->outbound());
        $this->assertSame(['This parent has no number' => 1], MarksRelease::sole()->result['reasons']);
        $this->assertSame(0, MarksRelease::sole()->result['sent']);
    }

    public function test_an_empty_pilot_list_refuses_and_does_not_message_the_class(): void
    {
        $this->twoFamilies();

        $this->actingAs($this->admin('real'))->post('/admin/marks/releases', array_merge(
            $this->payload('real', (int) $this->fx['real']['link']->id),
            ['pilot' => 'chosen', 'whatsapp_send' => '1', 'pilot_confirmed' => '1'],
        ))->assertSessionHasErrors('pilot');

        $this->assertSame(0, MarksRelease::count());
        $this->assertSame([], $this->outbound());
    }

    public function test_a_stored_empty_list_does_not_fall_back_to_the_class(): void
    {
        $this->twoFamilies();
        $admin = $this->admin('real');
        $release = app(MarksReleaseService::class)->create(
            $admin,
            $this->latestExamId('real'),
            (int) $this->fx['real']['link']->id,
            null,
            'Africa/Kampala',
            false,
            true,
            null,
            [],
        );

        $this->assertTrue(app(MarksReleaseService::class)->run($release->id));
        $fresh = $release->fresh();
        $this->assertSame([], $fresh->result['messages']);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame([], $this->outbound());
    }

    public function test_without_a_pilot_the_class_is_still_told(): void
    {
        [$first, $firstParent] = $this->twoFamilies();
        $other = User::query()->where('name', 'Second Parent')->where('school_id', $first->school_id)->firstOrFail();

        $this->actingAs($this->admin('real'))->post('/admin/marks/releases', array_merge(
            $this->payload('real', (int) $this->fx['real']['link']->id),
            ['whatsapp_send' => '1'],
        ))->assertRedirect();
        $this->work();

        $sent = array_column($this->outbound(), 'to');
        sort($sent);
        $expected = [$this->phoneOf($firstParent), $this->phoneOf($other)];
        sort($expected);
        $this->assertSame($expected, $sent);
        $this->assertNull(MarksRelease::sole()->pilot_recipients);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function releasePilot(array $extra): void
    {
        $this->actingAs($this->admin('real'))->post('/admin/marks/releases', array_merge(
            $this->payload('real', (int) $this->fx['real']['link']->id),
            ['pilot' => 'chosen', 'whatsapp_send' => '1', 'pilot_confirmed' => '1'],
            $extra,
        ))->assertRedirect();
    }

    /** @return array{0:User, 1:User, 2:User} */
    private function twoFamilies(): array
    {
        $first = $this->fx['real']['students'][0]['user'];
        $firstParent = $this->fx['real']['students'][0]['parent'];
        $secondParent = $this->makeParent('Second Parent');
        $this->addChild('real', $secondParent, 'Second Learner');
        WhatsAppUser::create([
            'phone' => '+256780000099',
            'user_id' => $secondParent->id,
            'school_id' => $first->school_id,
            'opted_in' => true,
        ]);

        return [$first, $firstParent, $secondParent];
    }

    private function makeParent(string $name): User
    {
        $school = $this->fx['real']['school'];
        $parent = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'name' => $name,
            'email' => str()->slug($name).'-'.$school->id.'@example.com',
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

        return $parent;
    }

    private function phoneOf(User $parent): string
    {
        return ltrim((string) WhatsAppUser::query()
            ->where('school_id', $parent->school_id)
            ->where('user_id', $parent->id)
            ->value('phone'), '+');
    }

    private function work(): void
    {
        $this->artisan('queue:work', ['--stop-when-empty' => true, '--sleep' => 0, '--memory' => 4096])->assertExitCode(0);
    }
}
