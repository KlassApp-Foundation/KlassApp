<?php

namespace Tests\Feature\Toshi;

use App\Ai\Agents\PlatformOperationsAgent;
use App\AiAgents\AccountantOperationsAgent;
use App\AiAgents\DeputyAdminOperationsAgent;
use App\AiAgents\LibrarianOperationsAgent;
use App\AiAgents\ParentOperationsAgent;
use App\AiAgents\ReceptionistOperationsAgent;
use App\AiAgents\StudentOperationsAgent;
use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\Tools\ManagePreferencesTool;
use App\AiAgents\ToshiOrchestrator;
use App\AiAgents\WhatsApp\SchoolAdminWhatsAppReadAgent;
use App\AiAgents\WhatsApp\WhatsAppWriteExclusion;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\UserPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class UserPreferenceMemoryTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Prefs School',
            'slug' => 'prefs-school',
            'email' => 'prefs@test.sch.ug',
            'phone' => '+256700000088',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_defaults_when_preference_row_unset(): void
    {
        $user = $this->makeUser(5, 'defaults.teacher@test.sch.ug');
        $service = app(UserPreferenceService::class);

        $preference = $service->get($user);

        $this->assertSame('en', $preference->preferred_language);
        $this->assertSame('whatsapp', $preference->notification_channel);
        $this->assertFalse($preference->digest_enabled);
        $this->assertSame('none', $preference->digest_frequency);
        $this->assertNull($preference->digest_weekday);
        $this->assertNull($preference->timezone);
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'preferred_language' => 'en',
            'notification_channel' => 'whatsapp',
            'digest_frequency' => 'none',
        ]);
    }

    public function test_set_in_one_context_readable_in_later_context(): void
    {
        $user = $this->makeUser(3, 'durability.admin@test.sch.ug');
        $service = app(UserPreferenceService::class);

        // Context A — set preferences
        $this->actingAs($user);
        $service->set($user, [
            'preferred_language' => 'sw',
            'notification_channel' => 'email',
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
            'digest_weekday' => 1,
            'timezone' => 'Africa/Kampala',
        ]);
        Auth::logout();

        // Context B — fresh auth / service resolve, same user
        $this->actingAs(User::findOrFail($user->id));
        $later = app(UserPreferenceService::class)->get(User::findOrFail($user->id));

        $this->assertSame('sw', $later->preferred_language);
        $this->assertSame('email', $later->notification_channel);
        $this->assertTrue($later->digest_enabled);
        $this->assertSame('weekly', $later->digest_frequency);
        $this->assertSame(1, $later->digest_weekday);
        $this->assertSame('Africa/Kampala', $later->timezone);

        // Tool path in a third context
        Auth::logout();
        $this->actingAs(User::findOrFail($user->id));
        $result = (new ManagePreferencesTool)->handle(new Request(['action' => 'get']));

        $this->assertStringContainsString('✅', (string) $result);
        $this->assertStringContainsString('"preferred_language":"sw"', (string) $result);
        $this->assertStringContainsString('"timezone":"Africa/Kampala"', (string) $result);
    }

    public function test_cannot_read_or_write_another_users_preferences(): void
    {
        $alice = $this->makeUser(5, 'alice.prefs@test.sch.ug');
        $bob = $this->makeUser(5, 'bob.prefs@test.sch.ug');
        $service = app(UserPreferenceService::class);

        $service->set($alice, [
            'preferred_language' => 'lg',
            'timezone' => 'Africa/Kampala',
        ]);

        $this->actingAs($bob);

        // Tool is self-scoped — Bob's get does not reveal Alice.
        $bobGet = (new ManagePreferencesTool)->handle(new Request(['action' => 'get']));
        $this->assertStringContainsString('"preferred_language":"en"', (string) $bobGet);
        $this->assertStringNotContainsString('lg', (string) $bobGet);

        // Even if LLM smuggles user_id, service/tool ignore it and write Bob only.
        $toolResult = (new ManagePreferencesTool)->handle(new Request([
            'action' => 'set',
            'user_id' => $alice->id,
            'preferred_language' => 'fr',
            'timezone' => 'UTC',
        ]));
        $this->assertStringContainsString('✅', (string) $toolResult);

        $aliceFresh = UserPreference::where('user_id', $alice->id)->first();
        $bobFresh = UserPreference::where('user_id', $bob->id)->first();

        $this->assertSame('lg', $aliceFresh->preferred_language);
        $this->assertSame('Africa/Kampala', $aliceFresh->timezone);
        $this->assertSame('fr', $bobFresh->preferred_language);
        $this->assertSame('UTC', $bobFresh->timezone);

        // Direct service call with Bob never mutates Alice.
        $service->set($bob, ['digest_frequency' => 'daily']);
        $this->assertSame('none', $aliceFresh->fresh()->digest_frequency);
        $this->assertSame('daily', $bobFresh->fresh()->digest_frequency);
    }

    public function test_manage_preferences_tool_is_whatsapp_safe(): void
    {
        $traits = class_uses_recursive(ManagePreferencesTool::class);
        $this->assertArrayNotHasKey(
            \App\AiAgents\Concerns\ConfirmsBeforeWrite::class,
            $traits,
            'ManagePreferencesTool must not use ConfirmsBeforeWrite'
        );

        $this->assertTrue(
            WhatsAppWriteExclusion::allowsClass(ManagePreferencesTool::class),
            'ManagePreferencesTool must pass WhatsAppWriteExclusion::allowsClass'
        );

        $teacher = $this->makeUser(5, 'wa.prefs.teacher@test.sch.ug');
        $parent = $this->makeUser(7, 'wa.prefs.parent@test.sch.ug');
        $admin = $this->makeUser(3, 'wa.prefs.admin@test.sch.ug');

        $teacherTools = collect((new TeacherOperationsAgent)->tools())->map(fn ($t) => $t::class);
        $parentTools = collect((new ParentOperationsAgent)->tools())->map(fn ($t) => $t::class);
        $adminWaTools = collect((new SchoolAdminWhatsAppReadAgent)->tools())->map(fn ($t) => $t::class);

        $this->assertContains(ManagePreferencesTool::class, $teacherTools->all());
        $this->assertContains(ManagePreferencesTool::class, $parentTools->all());
        $this->assertContains(ManagePreferencesTool::class, $adminWaTools->all());

        $filteredTeacher = \App\AiAgents\WhatsApp\WhatsAppWriteExclusion::filterReadOnly(
            (new TeacherOperationsAgent)->tools()
        );
        $filteredClasses = collect($filteredTeacher)->map(fn ($t) => $t::class)->all();
        $this->assertContains(ManagePreferencesTool::class, $filteredClasses);
    }

    public function test_registered_on_every_role_agent_tool_set(): void
    {
        $agents = [
            PlatformOperationsAgent::class,
            ToshiOrchestrator::class,
            SchoolAdminWhatsAppReadAgent::class,
            DeputyAdminOperationsAgent::class,
            TeacherOperationsAgent::class,
            AccountantOperationsAgent::class,
            LibrarianOperationsAgent::class,
            ReceptionistOperationsAgent::class,
            StudentOperationsAgent::class,
            ParentOperationsAgent::class,
        ];

        foreach ($agents as $agentClass) {
            $classes = collect((new $agentClass)->tools())->map(fn ($t) => $t::class)->all();
            $this->assertContains(
                ManagePreferencesTool::class,
                $classes,
                "{$agentClass} must expose ManagePreferencesTool"
            );
        }
    }

    public function test_deputy_admin_ug4_self_scope_and_durability(): void
    {
        $deputy = $this->makeUser(4, 'deputy.prefs@test.sch.ug');
        $peer = $this->makeUser(4, 'deputy.peer@test.sch.ug');
        $service = app(UserPreferenceService::class);

        $this->actingAs($deputy);
        $service->set($deputy, [
            'preferred_language' => 'lg',
            'notification_channel' => 'email',
            'digest_enabled' => true,
            'digest_frequency' => 'daily',
            'timezone' => 'Africa/Kampala',
        ]);
        Auth::logout();

        $this->actingAs(User::findOrFail($deputy->id));
        $later = app(UserPreferenceService::class)->get(User::findOrFail($deputy->id));
        $this->assertSame('lg', $later->preferred_language);
        $this->assertSame('email', $later->notification_channel);
        $this->assertTrue($later->digest_enabled);
        $this->assertSame('daily', $later->digest_frequency);
        $this->assertSame('Africa/Kampala', $later->timezone);

        Auth::logout();
        $this->actingAs(User::findOrFail($deputy->id));
        $toolGet = (new ManagePreferencesTool)->handle(new Request(['action' => 'get']));
        $this->assertStringContainsString('"preferred_language":"lg"', (string) $toolGet);
        $this->assertStringContainsString('"timezone":"Africa/Kampala"', (string) $toolGet);

        Auth::logout();
        $this->actingAs($peer);
        $peerGet = (new ManagePreferencesTool)->handle(new Request(['action' => 'get']));
        $this->assertStringContainsString('"preferred_language":"en"', (string) $peerGet);
        $this->assertStringNotContainsString('lg', (string) $peerGet);

        $smuggle = (new ManagePreferencesTool)->handle(new Request([
            'action' => 'set',
            'user_id' => $deputy->id,
            'preferred_language' => 'sw',
            'timezone' => 'UTC',
        ]));
        $this->assertStringContainsString('✅', (string) $smuggle);

        $deputyFresh = UserPreference::where('user_id', $deputy->id)->first();
        $peerFresh = UserPreference::where('user_id', $peer->id)->first();
        $this->assertSame('lg', $deputyFresh->preferred_language);
        $this->assertSame('Africa/Kampala', $deputyFresh->timezone);
        $this->assertSame('sw', $peerFresh->preferred_language);
        $this->assertSame('UTC', $peerFresh->timezone);

        $agentTools = collect((new DeputyAdminOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertContains(ManagePreferencesTool::class, $agentTools);
        $this->assertTrue(WhatsAppWriteExclusion::allowsClass(ManagePreferencesTool::class));
    }

    public function test_user_preference_relationship(): void
    {
        $user = $this->makeUser(6, 'rel.student@test.sch.ug');
        app(UserPreferenceService::class)->set($user, ['timezone' => 'Africa/Nairobi']);

        $user->refresh();
        $this->assertInstanceOf(UserPreference::class, $user->preference);
        $this->assertSame('Africa/Nairobi', $user->preference->timezone);
    }

    private function makeUser(int $usergroupId, string $email): User
    {
        return User::factory()->create([
            'school_id' => $usergroupId === 1 ? null : $this->schoolId,
            'usergroup_id' => $usergroupId,
            'email' => $email,
            'name' => str_replace(['@', '.'], '_', $email),
        ]);
    }
}
