<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use Database\Seeders\UsersSiteAdminTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CreatePlatformAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_one_platform_admin_with_a_one_time_password(): void
    {
        Mail::fake();

        $exit = Artisan::call('platform:create-admin', [
            'email' => 'new.platform.admin@klassapp.test',
            'name' => 'New Platform Admin',
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Platform admin created.', $output);
        $this->assertSame(1, substr_count($output, 'One-time password:'));

        preg_match('/One-time password: (\S+)/', $output, $matches);
        $password = $matches[1] ?? '';
        $this->assertNotSame('', $password);

        $user = User::where('email', 'new.platform.admin@klassapp.test')->firstOrFail();

        $this->assertSame(1, (int) $user->usergroup_id);
        $this->assertNull($user->school_id);
        $this->assertSame(1, (int) $user->is_reset);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertSame(1, substr_count($output, $password));
        Mail::assertNothingOutgoing();

        $this->assertDatabaseHas('userprofiles', [
            'user_id' => $user->id,
            'usergroup_id' => 1,
        ]);
    }

    public function test_it_refuses_an_email_that_already_exists(): void
    {
        User::create([
            'name' => 'Existing',
            'email' => 'existing@klassapp.test',
            'password' => Hash::make('not-a-public-password'),
            'usergroup_id' => 1,
        ]);

        $exit = Artisan::call('platform:create-admin', [
            'email' => 'existing@klassapp.test',
            'name' => 'Another Admin',
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('already exists', Artisan::output());
        $this->assertSame(1, User::where('email', 'existing@klassapp.test')->count());
    }

    public function test_it_refuses_invalid_input(): void
    {
        $this->assertSame(1, Artisan::call('platform:create-admin', [
            'email' => 'not-an-email',
            'name' => 'Bad Email',
        ]));

        $this->assertSame(1, Artisan::call('platform:create-admin', [
            'email' => 'valid@klassapp.test',
            'name' => '   ',
        ]));

        $this->assertSame(0, User::where('usergroup_id', 1)->count());
    }

    public function test_the_site_admin_seeder_never_creates_public_password_admins(): void
    {
        Mail::fake();

        $this->seed(UsersSiteAdminTableSeeder::class);

        Mail::assertNothingOutgoing();

        $this->assertSame(0, User::where('email', 'like', '%@gmail.com')->count());

        foreach (User::where('usergroup_id', 1)->get() as $user) {
            $this->assertSame(1, (int) $user->is_reset, $user->email.' must change its password at first login');
            $this->assertFalse(Hash::check('password', $user->password), $user->email.' still has the public password');
        }
    }
}
