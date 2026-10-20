<?php

namespace Tests\Feature\Admin;

use App\Contracts\ConnectedTools\ConnectedTool;
use App\Models\SchoolMcpConnector;
use App\Models\User;
use App\Models\UserGoogleConnection;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\ConnectedTools\ConnectedToolsRegistry;
use App\Services\Google\GoogleAccessRevokedException;
use App\Services\Google\GoogleApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithFakeGoogle;
use Tests\TestCase;

class ConnectedToolsPageTest extends TestCase
{
    use InteractsWithFakeGoogle;
    use RefreshDatabase;

    private int $schoolA;

    private int $schoolB;

    private User $adminA;

    private User $adminB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeGoogle();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = $this->makeSchool('A');
        $this->schoolB = $this->makeSchool('B');
        $this->adminA = $this->makeUser($this->schoolA, 3, 'Admin A');
        $this->adminB = $this->makeUser($this->schoolB, 3, 'Admin B');
    }

    protected function tearDown(): void
    {
        $this->tearDownFakeGoogle();
        parent::tearDown();
    }

    public function test_each_school_member_opens_their_own_page(): void
    {
        $this->get(route('google.mine'))->assertRedirect();

        foreach ([5, 4, 6, 7] as $group) {
            $this->actingAs($this->makeUser($this->schoolA, $group, 'Member '.$group))
                ->get(route('google.mine'))
                ->assertOk()
                ->assertDontSee('data-testid="google-allow-list"', false);
        }

        $this->actingAs($this->makeUser($this->schoolA, 1, 'Platform'))
            ->get(route('google.mine'))
            ->assertNotFound();

        $html = $this->actingAs($this->adminA)
            ->get(route('google.mine'))
            ->assertOk()
            ->assertSee('My connected tools', false)
            ->assertSee('data-testid="google-allow-list"', false)
            ->getContent();

        foreach (['whatsapp', 'sheets', 'drive', 'classroom', 'calendar', 'gmail', 'docs', 'slides', 'tasks', 'slack'] as $key) {
            $this->assertStringContainsString('data-testid="connected-tool-'.$key.'"', $html);
            $this->assertStringContainsString('data-testid="connected-tool-icon-'.$key.'"', $html);
        }
    }

    public function test_unbuilt_tools_are_coming_soon_and_catalog_tools_are_not_connected(): void
    {
        $html = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();

        foreach (['sheets', 'drive', 'classroom'] as $key) {
            $this->assertSame('Not connected', $this->cardStatus($html, $key));
            $this->assertStringContainsString('data-testid="connected-tool-note-'.$key.'"', $html);
            $this->assertStringNotContainsString('data-testid="connected-tool-connect-'.$key.'"', $html);
        }

        $this->actingAs($this->adminA)
            ->post(route('admin.google.allow', ['connector' => 'sheets']), ['allowed' => '1'])
            ->assertRedirect();
        $allowed = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertStringContainsString('/admin/settings/google/connect/sheets', $allowed);
        $this->assertStringContainsString('data-testid="connected-tool-connect-sheets"', $allowed);
        $this->assertStringNotContainsString('data-testid="connected-tool-connect-drive"', $allowed);

        foreach (['calendar', 'gmail', 'docs', 'slides', 'tasks'] as $key) {
            $this->assertSame('Coming soon', $this->cardStatus($html, $key));
            $this->assertStringNotContainsString('data-testid="connected-tool-connect-'.$key.'"', $html);
            $this->assertStringNotContainsString('data-testid="connected-tool-disconnect-'.$key.'"', $html);
            $this->assertStringNotContainsString('data-testid="connected-tool-settings-'.$key.'"', $html);
        }

        $this->assertSame('Not connected', $this->cardStatus($html, 'whatsapp'));
        $this->assertStringContainsString('data-testid="connected-tool-settings-whatsapp"', $html);
        $this->assertStringContainsString('/admin/whatsapp/dashboard', $html);
    }

    public function test_registering_a_provider_replaces_coming_soon(): void
    {
        app(ConnectedToolsRegistry::class)->register(new class implements ConnectedTool
        {
            public function key(): string
            {
                return 'calendar';
            }

            public function stateFor(User $user): array
            {
                return [
                    'status' => 'not_connected',
                    'attention' => null,
                    'note' => null,
                    'account_email' => null,
                    'last_synced_at' => null,
                    'action' => 'connect',
                    'action_url' => '/calendar-connect-for-school-'.$user->school_id,
                    'action_method' => 'GET',
                ];
            }
        });

        $html = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();

        $this->assertSame('Not connected', $this->cardStatus($html, 'calendar'));
        $this->assertStringContainsString('/calendar-connect-for-school-'.$this->schoolA, $html);
        $this->assertStringNotContainsString('/calendar-connect-for-school-'.$this->schoolB, $html);
        $this->assertSame('Coming soon', $this->cardStatus($html, 'gmail'));
    }

    public function test_google_connect_shows_last_sync_only_for_that_school_and_disconnects(): void
    {
        $before = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($before, 'sheets'));
        $this->assertStringNotContainsString('data-testid="connected-tool-sync-sheets"', $before);

        $this->connectGoogle($this->adminA, 'sheets');

        $row = UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first();
        $this->assertNotNull($row);
        $iso = $row->last_refreshed_at->utc()->toIso8601String();

        $connected = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Connected', $this->cardStatus($connected, 'sheets'));
        $this->assertSame('Not connected', $this->cardStatus($connected, 'drive'));
        $this->assertStringContainsString('datetime="'.$iso.'"', $connected);
        $this->assertStringContainsString('Last sync', $connected);
        $this->assertStringContainsString('data-testid="connected-tool-disconnect-sheets"', $connected);
        $this->assertStringContainsString('/admin/settings/google/sheets/disconnect', $connected);
        $this->assertStringNotContainsString('data-testid="connected-tool-connect-sheets"', $connected);

        $other = $this->actingAs($this->adminB)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($other, 'sheets'));
        $this->assertStringNotContainsString('datetime="'.$iso.'"', $other);
        $this->assertStringNotContainsString('school@example.test', $other);
        $this->assertStringNotContainsString('data-testid="connected-tool-disconnect-sheets"', $other);

        $colleague = $this->makeUser($this->schoolA, 3, 'Colleague');
        $colleaguePage = $this->actingAs($colleague)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($colleaguePage, 'sheets'));
        $this->assertStringNotContainsString('school@example.test', $colleaguePage);
        $this->assertStringNotContainsString('data-testid="connected-tool-email-sheets"', $colleaguePage);
        $this->actingAs($colleague)
            ->post(route('admin.google.disconnect', ['connector' => 'sheets']))
            ->assertNotFound();
        $this->assertNotNull(UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first());

        $this->actingAs($this->adminA)
            ->post(route('admin.google.disconnect', ['connector' => 'sheets']))
            ->assertRedirect(route('google.mine'));

        $after = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($after, 'sheets'));
        $this->assertStringContainsString('data-testid="connected-tool-connect-sheets"', $after);
        $this->assertStringNotContainsString('data-testid="connected-tool-sync-sheets"', $after);
        $this->assertNull($row->fresh());
    }

    public function test_revoked_refresh_token_needs_attention_for_the_granted_tool_only(): void
    {
        $this->connectGoogle($this->adminA, 'sheets');
        $row = UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first();
        $row->forceFill(['token_expires_at' => now()->subMinute()])->save();
        $this->google->update(function (array &$state) {
            $state['modes']['refresh'] = 'invalid_grant';
        });

        try {
            app(GoogleApiClient::class)->get($row->fresh(), (string) config('services.google.userinfo_url'));
            $this->fail('A revoked refresh token must not return a profile.');
        } catch (GoogleAccessRevokedException) {
            // The row is cleared and marked so the page can tell revoke from a clean disconnect.
        }

        $row->refresh();
        $this->assertSame('invalid_grant', $row->last_error);
        $this->assertContains('https://www.googleapis.com/auth/spreadsheets', $row->attention_scopes ?? []);

        $html = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Needs attention', $this->cardStatus($html, 'sheets'));
        $this->assertSame('Not connected', $this->cardStatus($html, 'drive'));
        $this->assertStringContainsString('The Google connection needs to be renewed.', $html);
        $this->assertStringContainsString('data-testid="connected-tool-connect-sheets"', $html);
        $this->assertStringNotContainsString('data-testid="connected-tool-sync-sheets"', $html);

        $other = $this->actingAs($this->adminB)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($other, 'sheets'));
        $this->assertStringNotContainsString('The Google connection needs to be renewed.', $other);
    }

    public function test_a_partial_scope_grant_needs_attention(): void
    {
        $this->connectGoogle($this->adminA, 'classroom');
        $row = UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first();
        $scopes = array_values(array_filter(
            $row->granted_scopes ?? [],
            fn (string $scope) => $scope !== 'https://www.googleapis.com/auth/classroom.rosters.readonly'
        ));
        $row->forceFill(['granted_scopes' => $scopes])->save();

        $html = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Needs attention', $this->cardStatus($html, 'classroom'));
        $this->assertStringContainsString('Google did not grant the access this tool needs.', $html);
        $this->assertSame('Not connected', $this->cardStatus($html, 'sheets'));
    }

    public function test_a_recorded_sync_error_needs_attention(): void
    {
        $this->connectGoogle($this->adminA, 'sheets');
        UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first()
            ->forceFill(['last_error' => 'sync_failed'])
            ->save();

        $html = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Needs attention', $this->cardStatus($html, 'sheets'));
        $this->assertStringContainsString('The last sync did not finish.', $html);
        $this->assertStringContainsString('data-testid="connected-tool-settings-sheets"', $html);
        $this->assertStringContainsString('/admin/settings/integrations', $html);
    }

    public function test_whatsapp_follows_linked_parents_of_this_school(): void
    {
        $plain = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($plain, 'whatsapp'));

        WhatsAppUser::create([
            'phone' => '+1555000'.random_int(100000, 999999),
            'user_id' => $this->adminA->id,
            'school_id' => $this->schoolA,
            'verified_at' => now(),
            'opted_in' => true,
        ]);

        $adminOnly = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($adminOnly, 'whatsapp'));

        $parentB = $this->makeUser($this->schoolB, 7, 'Parent B');
        WhatsAppUser::create([
            'phone' => '+1555111'.random_int(100000, 999999),
            'user_id' => $parentB->id,
            'school_id' => $this->schoolB,
            'verified_at' => now(),
            'opted_in' => true,
            'last_inbound_at' => Carbon::parse('2026-10-01 08:00:00'),
        ]);

        $still = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($still, 'whatsapp'));

        $parentA = $this->makeUser($this->schoolA, 7, 'Parent A');
        $inbound = Carbon::parse('2026-10-20 09:30:00');
        WhatsAppUser::create([
            'phone' => '+1555222'.random_int(100000, 999999),
            'user_id' => $parentA->id,
            'school_id' => $this->schoolA,
            'verified_at' => Carbon::parse('2026-10-19 09:30:00'),
            'opted_in' => true,
            'last_inbound_at' => $inbound,
        ]);

        $linked = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Connected', $this->cardStatus($linked, 'whatsapp'));
        $this->assertStringContainsString('datetime="'.$inbound->utc()->toIso8601String().'"', $linked);
        $this->assertStringContainsString('data-testid="connected-tool-settings-whatsapp"', $linked);

        WhatsAppUser::query()->where('user_id', $parentA->id)->update([
            'opted_in' => false,
            'last_inbound_at' => null,
        ]);

        $stopped = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Needs attention', $this->cardStatus($stopped, 'whatsapp'));
        $this->assertStringContainsString('A parent link is unfinished, or a parent has stopped WhatsApp messages.', $stopped);
    }

    public function test_slack_follows_this_schools_connector_row(): void
    {
        $plain = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($plain, 'slack'));
        $this->assertStringNotContainsString('data-testid="connected-tool-connect-slack"', $plain);

        $used = Carbon::parse('2026-10-18 15:00:00');
        SchoolMcpConnector::create([
            'school_id' => $this->schoolB,
            'connector_type' => 'slack',
            'external_team_id' => 'T-OTHER',
            'external_team_name' => 'Other workspace',
            'credentials' => ['access_token' => 'other-access', 'refresh_token' => 'other-refresh'],
            'status' => 'active',
            'last_used_at' => $used,
        ]);

        $isolated = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($isolated, 'slack'));
        $this->assertStringNotContainsString('Other workspace', $isolated);

        SchoolMcpConnector::create([
            'school_id' => $this->schoolA,
            'connector_type' => 'slack',
            'external_team_id' => 'T-OURS',
            'credentials' => ['access_token' => 'our-access', 'refresh_token' => 'our-refresh'],
            'status' => 'active',
            'last_used_at' => $used,
        ]);

        $connected = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Connected', $this->cardStatus($connected, 'slack'));
        $this->assertStringContainsString('datetime="'.$used->utc()->toIso8601String().'"', $connected);
        $this->assertStringContainsString('/admin/settings/integrations/slack/disconnect', $connected);

        $ours = SchoolMcpConnector::query()->where('school_id', $this->schoolA)->first();
        $ours->forceFill([
            'token_expires_at' => now()->subHour(),
            'credentials' => ['access_token' => 'our-access'],
        ])->save();

        $attention = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Needs attention', $this->cardStatus($attention, 'slack'));
        $this->assertStringContainsString('The Slack connection needs to be renewed.', $attention);

        config([
            'services.slack_mcp.mode' => 'live',
            'services.slack_mcp.client_id' => 'slack-client',
        ]);
        $ours->forceFill(['status' => 'disabled'])->save();

        $reconnect = $this->actingAs($this->adminA)->get(route('google.mine'))->assertOk()->getContent();
        $this->assertSame('Not connected', $this->cardStatus($reconnect, 'slack'));
        $this->assertStringContainsString('data-testid="connected-tool-connect-slack"', $reconnect);
        $this->assertStringContainsString('/mcp/slack/connect', $reconnect);
    }

    public function test_sidebar_and_dashboard_link_to_the_page(): void
    {
        $this->actingAs($this->adminA);
        $sidebar = view('layouts.partials.sidebar-menu', ['role' => 'admin'])->render();
        $this->assertStringContainsString('My connected tools', $sidebar);
        $this->assertStringContainsString('/settings/connected-tools', $sidebar);

        $this->actingAs($this->makeUser($this->schoolA, 4, 'Subadmin menu'));
        $subadmin = view('layouts.partials.sidebar-menu', ['role' => 'admin'])->render();
        $this->assertStringContainsString('/settings/connected-tools', $subadmin);

        $this->actingAs($this->adminA)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('data-testid="dashboard-connected-tools-link"', false)
            ->assertSee(route('google.mine'), false);
    }

    private function cardStatus(string $html, string $key): string
    {
        $pattern = '/data-testid="connected-tool-status-'.preg_quote($key, '/').'"[^>]*>([^<]+)/';
        $this->assertMatchesRegularExpression($pattern, $html, $key);
        preg_match($pattern, $html, $matches);

        return trim($matches[1]);
    }

    private function makeSchool(string $suffix): int
    {
        return (int) DB::table('schools')->insertGetId([
            'name' => "Tools School {$suffix} ".uniqid(),
            'slug' => 'tools-'.$suffix.'-'.uniqid(),
            'email' => uniqid().'@tools.test',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeUser(int $schoolId, int $group, string $name): User
    {
        $user = User::factory()->create([
            'usergroup_id' => $group,
            'school_id' => $schoolId,
            'name' => $name,
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $schoolId,
            'usergroup_id' => $group,
            'firstname' => $name,
            'lastname' => 'User',
            'status' => 'active',
        ]);

        return $user;
    }
}
