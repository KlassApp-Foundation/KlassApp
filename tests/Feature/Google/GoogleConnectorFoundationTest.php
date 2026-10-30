<?php

namespace Tests\Feature\Google;

use App\Models\SchoolGoogleConnectorAllow;
use App\Models\UserGoogleConnection;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Google\GoogleAccessRevokedException;
use App\Services\Google\GoogleApiClient;
use App\Services\Google\GoogleApiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithFakeGoogle;
use Tests\TestCase;

class GoogleConnectorFoundationTest extends TestCase
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
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = $this->makeSchool('A');
        $this->schoolB = $this->makeSchool('B');
        $this->adminA = $this->makeAdmin($this->schoolA, 'Admin A');
        $this->adminB = $this->makeAdmin($this->schoolB, 'Admin B');
    }

    protected function tearDown(): void
    {
        $this->tearDownFakeGoogle();
        parent::tearDown();
    }

    public function test_connect_act_disconnect_against_the_fake_google_server(): void
    {
        $this->actingAs($this->adminA)->get('/admin/settings/integrations')
            ->assertOk()
            ->assertSee('Connected tools', false)
            ->assertSee('data-testid="google-connector-status-sheets"', false)
            ->assertDontSee('data-testid="google-connector-live-sheets"', false);

        SchoolGoogleConnectorAllow::query()->updateOrCreate(
            ['school_id' => $this->schoolA, 'connector' => 'sheets'],
            ['allowed' => true],
        );

        $started = $this->actingAs($this->adminA)->get(route('admin.google.connect', ['connector' => 'sheets']));
        $query = $this->authorizeQuery((string) $started->headers->get('Location'));
        $scopes = preg_split('/\s+/', urldecode((string) ($query['scope'] ?? ''))) ?: [];
        $this->assertContains('https://www.googleapis.com/auth/spreadsheets', $scopes);
        $this->assertContains('https://www.googleapis.com/auth/userinfo.email', $scopes);
        $this->assertNotContains('https://www.googleapis.com/auth/drive.file', $scopes);
        $this->assertNotContains('https://www.googleapis.com/auth/classroom.rosters.readonly', $scopes);
        $this->assertSame('true', $query['include_granted_scopes'] ?? null);

        $this->connectGoogle($this->adminA, 'sheets');

        $row = UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('active', $row->status);
        $this->assertSame('school@example.test', $row->google_email);
        $this->assertContains('https://www.googleapis.com/auth/spreadsheets', $row->granted_scopes);
        $raw = (string) DB::table('user_google_connections')->where('user_id', $this->adminA->id)->value('credentials');
        $this->assertStringNotContainsString($row->accessToken(), $raw);
        $this->assertStringNotContainsString('g-access-', $raw);

        $profile = app(GoogleApiClient::class)->get($row, (string) config('services.google.userinfo_url'));
        $this->assertSame('school@example.test', $profile['email']);
        $userinfo = collect($this->google->calls())->first(fn ($call) => $call['path'] === '/oauth2/v2/userinfo');
        $this->assertNotNull($userinfo);
        $this->assertStringContainsString($row->fresh()->accessToken(), $userinfo['authorization']);

        $this->actingAs($this->adminA)->get('/admin/settings/integrations')
            ->assertOk()
            ->assertSee('data-testid="google-connector-live-sheets"', false)
            ->assertSee('>Live<', false)
            ->assertDontSee('data-testid="google-connector-live-drive"', false);

        $this->actingAs($this->adminA)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('data-testid="dashboard-google-live-sheets"', false)
            ->assertSee('Google Sheets · Live', false)
            ->assertDontSee('Google Drive · Live', false);

        $this->actingAs($this->adminB)->get('/admin/settings/integrations')
            ->assertOk()
            ->assertDontSee('data-testid="google-connector-live-sheets"', false)
            ->assertDontSee('school@example.test', false);

        $this->actingAs($this->adminB)->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('Google Sheets · Live', false);

        $this->actingAs($this->adminA)->post(route('admin.google.disconnect', ['connector' => 'sheets']))
            ->assertRedirect(route('google.mine'));

        $this->assertNull(UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first());
        $this->assertNotEmpty(collect($this->google->calls())->first(fn ($call) => $call['path'] === '/revoke'));

        $this->actingAs($this->adminA)->get('/admin/settings/integrations')
            ->assertDontSee('data-testid="google-connector-live-sheets"', false);
        $this->actingAs($this->adminA)->get('/admin/dashboard')
            ->assertDontSee('Google Sheets · Live', false);
    }

    public function test_incremental_connect_requests_only_the_new_connector_scopes(): void
    {
        $this->connectGoogle($this->adminA, 'sheets');

        SchoolGoogleConnectorAllow::query()->updateOrCreate(
            ['school_id' => $this->schoolA, 'connector' => 'drive'],
            ['allowed' => true],
        );
        $started = $this->actingAs($this->adminA)->get(route('admin.google.connect', ['connector' => 'drive']));
        $scopes = preg_split('/\s+/', urldecode((string) ($this->authorizeQuery((string) $started->headers->get('Location'))['scope'] ?? ''))) ?: [];
        $this->assertContains('https://www.googleapis.com/auth/drive.file', $scopes);
        $this->assertNotContains('https://www.googleapis.com/auth/spreadsheets', $scopes);

        $this->connectGoogle($this->adminA, 'drive');

        $row = UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first();
        $this->assertContains('https://www.googleapis.com/auth/spreadsheets', $row->granted_scopes);
        $this->assertContains('https://www.googleapis.com/auth/drive.file', $row->granted_scopes);

        $this->actingAs($this->adminA)->post(route('admin.google.disconnect', ['connector' => 'sheets']))
            ->assertRedirect();

        $row->refresh();
        $this->assertNotContains('https://www.googleapis.com/auth/spreadsheets', $row->granted_scopes ?? []);
        $this->assertContains('https://www.googleapis.com/auth/drive.file', $row->granted_scopes);
        $this->assertNotNull($row->refreshToken());
        $this->assertNull(collect($this->google->calls())->first(fn ($call) => $call['path'] === '/revoke'));

        $this->actingAs($this->adminA)->post(route('admin.google.disconnect', ['connector' => 'drive']))
            ->assertRedirect();
        $this->assertNull(UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first());
        $this->assertNotNull(collect($this->google->calls())->first(fn ($call) => $call['path'] === '/revoke'));
    }

    public function test_expired_token_is_refreshed_before_the_api_call(): void
    {
        $this->connectGoogle($this->adminA, 'sheets');
        $row = UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first();
        $old = $row->accessToken();
        $row->forceFill(['token_expires_at' => now()->subMinute()])->save();

        $profile = app(GoogleApiClient::class)->get($row->fresh(), (string) config('services.google.userinfo_url'));

        $this->assertSame('school@example.test', $profile['email']);
        $fresh = $row->fresh();
        $this->assertNotSame($old, $fresh->accessToken());
        $this->assertTrue($fresh->token_expires_at->isFuture());
        $this->assertNotNull(collect($this->google->calls())->first(
            fn ($call) => $call['path'] === '/token' && ($call['body']['grant_type'] ?? '') === 'refresh_token'
        ));
        $userinfo = collect($this->google->calls())->last(fn ($call) => $call['path'] === '/oauth2/v2/userinfo');
        $this->assertStringContainsString($fresh->accessToken(), $userinfo['authorization']);
    }

    public function test_revoked_refresh_clears_the_stored_tokens(): void
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
        } catch (GoogleAccessRevokedException $e) {
            $this->assertSame('invalid_grant', $e->googleError);
        }

        $row->refresh();
        $this->assertSame('disabled', $row->status);
        $this->assertNull($row->credentials);
    }

    public function test_api_error_keeps_the_connection(): void
    {
        $this->connectGoogle($this->adminA, 'sheets');
        $row = UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first();
        $this->google->update(function (array &$state) {
            $state['modes']['userinfo'] = '500';
        });

        try {
            app(GoogleApiClient::class)->get($row->fresh(), (string) config('services.google.userinfo_url'));
            $this->fail('A Google API error must surface.');
        } catch (GoogleApiException $e) {
            $this->assertSame(500, $e->status);
            $this->assertNotInstanceOf(GoogleAccessRevokedException::class, $e);
        }

        $row->refresh();
        $this->assertSame('active', $row->status);
        $this->assertNotNull($row->accessToken());
    }

    public function test_other_school_and_non_admins_cannot_disconnect(): void
    {
        $this->connectGoogle($this->adminA, 'sheets');

        $this->actingAs($this->adminB)
            ->post(route('admin.google.disconnect', ['connector' => 'sheets']))
            ->assertNotFound();

        $this->assertSame('active', UserGoogleConnection::query()->where('user_id', $this->adminA->id)->value('status'));

        $colleague = User::factory()->create(['usergroup_id' => 3, 'school_id' => $this->schoolA, 'status' => 'active']);
        $this->actingAs($colleague)
            ->post(route('admin.google.disconnect', ['connector' => 'sheets']))
            ->assertNotFound();
        $this->actingAs($colleague)->get('/admin/settings/integrations')
            ->assertOk()
            ->assertDontSee('school@example.test', false);
        $this->assertSame('active', UserGoogleConnection::query()->where('user_id', $this->adminA->id)->value('status'));

        $teacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->schoolA, 'status' => 'active']);
        $this->actingAs($teacher)
            ->post(route('admin.google.disconnect', ['connector' => 'sheets']))
            ->assertNotFound();
        $this->assertSame('active', UserGoogleConnection::query()->where('user_id', $this->adminA->id)->value('status'));
        $this->actingAs($teacher)->get('/admin/settings/integrations')->assertNotFound();

        $platform = User::factory()->create(['usergroup_id' => 1, 'school_id' => $this->schoolA, 'status' => 'active']);
        $this->actingAs($platform)
            ->post(route('admin.google.disconnect', ['connector' => 'sheets']))
            ->assertNotFound();
        $this->actingAs($platform)->get(route('google.mine'))->assertNotFound();
        $this->assertNotNull(UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first()->accessToken());
    }

    public function test_oauth_state_must_match_the_session(): void
    {
        SchoolGoogleConnectorAllow::query()->updateOrCreate(
            ['school_id' => $this->schoolA, 'connector' => 'sheets'],
            ['allowed' => true],
        );
        $this->actingAs($this->adminA)->get(route('admin.google.connect', ['connector' => 'sheets']));

        $this->actingAs($this->adminA)
            ->get('/admin/settings/google/callback?code=nope&state=not-the-session-state')
            ->assertRedirect(route('google.mine'));

        $this->assertNull(UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first());
    }

    public function test_missing_client_does_not_redirect_to_google(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.connector_redirect' => null,
        ]);

        SchoolGoogleConnectorAllow::query()->updateOrCreate(
            ['school_id' => $this->schoolA, 'connector' => 'sheets'],
            ['allowed' => true],
        );

        $this->actingAs($this->adminA)
            ->get(route('admin.google.connect', ['connector' => 'sheets']))
            ->assertRedirect(route('google.mine'));

        $this->assertNull(UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first());
    }

    public function test_a_school_must_allow_a_connector_before_a_user_can_connect_it(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('admin.google.connect', ['connector' => 'sheets']))
            ->assertNotFound();
        $this->assertNull(UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first());

        $this->actingAs($this->adminA)
            ->post(route('admin.google.allow', ['connector' => 'sheets']), ['allowed' => '1'])
            ->assertRedirect(route('admin.settings.integrations'));

        $html = $this->actingAs($this->adminA)->get('/admin/settings/integrations')->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="google-allow-count-sheets"', $html);
        $this->assertStringContainsString('>0 connected<', $html);
        $this->assertStringNotContainsString('school@example.test', $html);

        $this->connectGoogle($this->adminA, 'sheets');

        $after = $this->actingAs($this->adminA)->get('/admin/settings/integrations')->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="google-allow-count-sheets">1 connected<', $after);
        $this->assertStringNotContainsString('data-testid="google-allow-email', $after);
    }

    public function test_smoke_command_acts_only_as_the_named_user(): void
    {
        $this->connectGoogle($this->adminA, 'sheets');
        $tokenA = UserGoogleConnection::query()->where('user_id', $this->adminA->id)->first()->accessToken();
        $this->connectGoogle($this->adminB, 'sheets');
        $beforeB = UserGoogleConnection::query()->where('user_id', $this->adminB->id)->first()->accessToken();
        $seen = count($this->google->calls());

        $this->artisan('google:smoke', ['connector' => 'account', '--user' => $this->adminA->id])
            ->assertSuccessful()
            ->expectsOutputToContain('school@example.test');

        $userinfo = collect(array_slice($this->google->calls(), $seen))
            ->first(fn ($call) => $call['path'] === '/oauth2/v2/userinfo');
        $this->assertNotNull($userinfo);
        $this->assertStringContainsString($tokenA, (string) $userinfo['authorization']);
        $this->assertStringNotContainsString($beforeB, (string) $userinfo['authorization']);
        $this->assertSame($beforeB, UserGoogleConnection::query()->where('user_id', $this->adminB->id)->first()->accessToken());
        $this->artisan('google:smoke', ['connector' => 'account'])->assertFailed();
    }

    public function test_foreign_key_rejects_a_missing_school_when_enforced(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('SQLite is the guarded suite.');
        }

        // RefreshDatabase holds a transaction, and SQLite ignores the foreign
        // key pragma until that transaction ends.
        while (DB::transactionLevel() > 0) {
            DB::commit();
        }
        DB::statement('PRAGMA foreign_keys = ON');
        $this->assertSame(1, (int) (DB::select('PRAGMA foreign_keys')[0]->foreign_keys ?? 0));

        $threw = false;
        try {
            UserGoogleConnection::create([
                'user_id' => $this->adminA->id,
                'school_id' => 999999,
                'status' => 'active',
                'credentials' => ['access_token' => 'x', 'refresh_token' => 'y'],
                'granted_scopes' => [],
            ]);
        } catch (\Illuminate\Database\QueryException) {
            $threw = true;
        }

        $this->assertTrue($threw, 'A Google connection for a missing school must fail when foreign keys are enforced.');

        DB::table('user_google_connections')->whereIn('school_id', [$this->schoolA, $this->schoolB, 999999])->delete();
        DB::table('userprofiles')->whereIn('school_id', [$this->schoolA, $this->schoolB])->delete();
        DB::table('users')->whereIn('school_id', [$this->schoolA, $this->schoolB])->delete();
        DB::table('schools')->whereIn('id', [$this->schoolA, $this->schoolB])->delete();
    }

    private function makeSchool(string $suffix): int
    {
        return (int) DB::table('schools')->insertGetId([
            'name' => "Google School {$suffix} ".uniqid(),
            'slug' => 'g-'.$suffix.'-'.uniqid(),
            'email' => uniqid().'@google.test',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeAdmin(int $schoolId, string $name): User
    {
        $admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $schoolId,
            'name' => $name,
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $schoolId,
            'usergroup_id' => 3,
            'firstname' => $name,
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        return $admin;
    }
}
