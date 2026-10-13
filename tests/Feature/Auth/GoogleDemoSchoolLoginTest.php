<?php

namespace Tests\Feature\Auth;

use App\Models\School;
use App\Models\User;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Database\Seeders\UsergroupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Google sign-in must not replace the name or avatar of an account that
 * belongs to an is_demo school. Other schools keep the existing avatar update.
 */
class GoogleDemoSchoolLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        \App\Services\DemoSchoolCommsGuard::flushCache();
    }

    public function test_google_sign_in_does_not_overwrite_name_or_avatar_for_a_demo_school(): void
    {
        $this->seed(UsergroupTableSeeder::class);
        $this->seed(DemoJuniorSchoolSeeder::class);

        $admin = User::where('email', DemoJuniorSchoolSeeder::ADMIN_EMAIL)->firstOrFail();
        $admin->forceFill([
            'name' => 'Demo Admin',
            'google_id' => null,
            'google_avatar' => 'uploads/male.png',
        ])->save();
        $admin->userprofile->forceFill(['firstname' => 'Demo', 'lastname' => 'Admin'])->save();

        $this->mockGoogle($admin->email, 'Taritwebwa Eric', 'https://example.test/google-photo.png', 'google-demo-1');

        $this->get('/auth/google/callback')->assertRedirect();

        $admin->refresh()->load('userprofile');
        $this->assertSame('Demo Admin', $admin->name);
        $this->assertSame('Demo', $admin->userprofile->getRawOriginal('firstname'));
        $this->assertSame('Admin', $admin->userprofile->getRawOriginal('lastname'));
        $this->assertSame('uploads/male.png', $admin->google_avatar);
        $this->assertSame('google-demo-1', $admin->google_id);
    }

    public function test_google_sign_in_still_updates_the_avatar_for_a_non_demo_school(): void
    {
        $this->seed(UsergroupTableSeeder::class);

        $school = School::create([
            'name' => 'Plain School '.uniqid(),
            'slug' => 'plain-'.uniqid(),
            'email' => 'plain-'.uniqid().'@example.com',
            'phone' => '0771234567',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $school->forceFill(['is_demo' => 0])->save();

        $user = User::create([
            'email' => 'plain-admin-'.uniqid().'@example.com',
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Plain Admin',
            'password' => 'not-used',
            'status' => 'active',
            'email_verified' => 1,
            'google_avatar' => 'uploads/male.png',
        ]);
        $user->userprofile()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Plain',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        $this->mockGoogle($user->email, 'Someone Else', 'https://example.test/other.png', 'google-plain-1');

        $this->get('/auth/google/callback')->assertRedirect();

        $user->refresh();
        $this->assertSame('Plain Admin', $user->name);
        $this->assertSame('https://example.test/other.png', $user->google_avatar);
    }

    private function mockGoogle(string $email, string $name, string $avatar, string $id): void
    {
        $google = Mockery::mock(SocialiteUser::class);
        $google->shouldReceive('getId')->andReturn($id);
        $google->shouldReceive('getEmail')->andReturn($email);
        $google->shouldReceive('getName')->andReturn($name);
        $google->shouldReceive('getAvatar')->andReturn($avatar);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($google);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }
}
