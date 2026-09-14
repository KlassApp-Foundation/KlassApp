<?php

namespace Tests\Feature\Auth;

use App\Helpers\AuthRedirectHelper;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleSiteAdminRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_auth_redirect_helper_sends_siteadmin_to_superadmin_shell(): void
    {
        $this->assertSame('/superadmin/dashboard', AuthRedirectHelper::dashboardPathForUsergroup(1));
        $this->assertSame('/admin/dashboard', AuthRedirectHelper::dashboardPathForUsergroup(3));
    }

    public function test_google_login_for_existing_siteadmin_lands_on_superadmin_dashboard(): void
    {
        $user = User::create([
            'school_id' => null,
            'usergroup_id' => 1,
            'name' => 'Site Admin',
            'email' => 'siteadmin.google@klassapp.test',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
            'google_id' => 'google-siteadmin-1',
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'usergroup_id' => 1,
            'firstname' => 'SITE',
            'lastname' => 'ADMIN',
            'status' => 'active',
        ]);

        $this->mockGoogleUser([
            'id' => 'google-siteadmin-1',
            'email' => 'siteadmin.google@klassapp.test',
            'name' => 'Site Admin',
        ]);

        $response = $this->get('/auth/google/callback');
        $response->assertRedirect('/superadmin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_siteadmin_visiting_admin_dashboard_is_redirected_to_superadmin(): void
    {
        $user = User::create([
            'school_id' => null,
            'usergroup_id' => 1,
            'name' => 'Site Admin',
            'email' => 'siteadmin.dash@klassapp.test',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertRedirect('/superadmin/dashboard');
    }

    /**
     * @param  array{id: string, email: string, name: ?string}  $attrs
     */
    private function mockGoogleUser(array $attrs): void
    {
        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn($attrs['id']);
        $abstractUser->shouldReceive('getEmail')->andReturn($attrs['email']);
        $abstractUser->shouldReceive('getName')->andReturn($attrs['name'] ?? null);
        $abstractUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
    }
}
