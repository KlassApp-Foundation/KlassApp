<?php

namespace Tests\Feature\Toshi;

use App\Http\Controllers\Admin\Setting\GoogleClassroomOAuthController;
use App\Models\SchoolMcpConnector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GoogleClassroomOAuthConnectTest extends TestCase
{
    use RefreshDatabase;

    private function schoolAndAdmin(): array
    {
        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $schoolId = \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
            'name' => 'Classroom Connect School',
            'slug' => 'classroom-connect-'.uniqid(),
            'email' => 'classroom-connect-'.uniqid().'@test.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolId]);

        return [$schoolId, $admin];
    }

    public function test_callback_rejects_a_missing_or_mismatched_oauth_state(): void
    {
        [, $admin] = $this->schoolAndAdmin();
        $this->actingAs($admin);

        $request = Request::create('/mcp/oauth/google-classroom/callback', 'GET', [
            'code' => 'code',
            'state' => 'wrong',
        ]);
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('state', 'expected');

        $this->expectException(HttpException::class);

        app(GoogleClassroomOAuthController::class)->callback($request);
    }

    public function test_callback_uses_the_classroom_client_and_preserves_the_refresh_token(): void
    {
        [$schoolId, $admin] = $this->schoolAndAdmin();
        $this->actingAs($admin);

        config([
            'services.google_classroom.client_id' => 'classroom-client-id',
            'services.google_classroom.client_secret' => 'classroom-client-secret',
            'services.google_classroom.redirect' => '/mcp/oauth/google-classroom/callback',
        ]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('getAccessTokenResponse')
            ->twice()
            ->andReturn(
                [
                    'access_token' => 'first-access',
                    'refresh_token' => 'first-refresh',
                    'expires_in' => 3600,
                    'token_type' => 'Bearer',
                    'scope' => implode(' ', GoogleClassroomOAuthController::SCOPES),
                ],
                [
                    'access_token' => 'second-access',
                    'expires_in' => 3600,
                    'token_type' => 'Bearer',
                ],
            );

        Socialite::shouldReceive('buildProvider')
            ->twice()
            ->with(GoogleProvider::class, Mockery::on(function (array $config) {
                return $config['client_id'] === 'classroom-client-id'
                    && $config['client_secret'] === 'classroom-client-secret'
                    && str_ends_with($config['redirect'], '/mcp/oauth/google-classroom/callback');
            }))
            ->andReturn($provider);

        foreach (['state-one', 'state-two'] as $state) {
            $request = Request::create('/mcp/oauth/google-classroom/callback', 'GET', [
                'code' => 'code',
                'state' => $state,
            ]);
            $request->setLaravelSession($this->app['session']->driver());
            $request->session()->put('state', $state);

            $response = app(GoogleClassroomOAuthController::class)->callback($request);
            $this->assertSame(302, $response->getStatusCode());
        }

        $row = SchoolMcpConnector::where([
            'school_id' => $schoolId,
            'connector_type' => 'google-classroom',
            'external_team_id' => 'school:'.$schoolId,
        ])->firstOrFail();

        $this->assertSame('second-access', $row->accessToken());
        $this->assertSame('first-refresh', $row->refreshToken(), 'A reconnect without a new refresh token must keep the old one');
        $this->assertSame('deny', $row->write_mode);
        $this->assertSame($admin->id, $row->connected_by);
    }
}
