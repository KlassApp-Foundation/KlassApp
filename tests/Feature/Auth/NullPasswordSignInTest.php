<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Mail\TeacherInviteLinkMail;
use App\Models\Authentication;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * An account with no password cannot sign in until it finishes its setup link.
 */
class NullPasswordSignInTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('settings')->insertOrIgnore([
            ['key' => 'login_status', 'value' => '1'],
        ]);
        config(['settings.login_status' => 1]);

        $this->school = School::create([
            'name' => 'Unset Password School',
            'email' => 'office@unset-password.test',
            'phone' => '+256700444000',
            'slug' => 'unset-password',
            'status' => 1,
        ]);

        $this->teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Unset Teacher',
            'email' => 'unset.teacher@example.test',
            'mobile_no' => '+256700444111',
            'password' => null,
            'email_verified' => 1,
            'is_reset' => 0,
            'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'Unset',
            'lastname' => 'Teacher',
            'status' => 'active',
        ]);
    }

    public function test_password_login_refuses_any_password_including_an_empty_one(): void
    {
        $this->post('/login', [
            'email' => $this->teacher->email,
            'password' => '',
        ])->assertSessionHasErrors('password');
        $this->assertGuest();

        $this->post('/login', [
            'email' => $this->teacher->email,
            'password' => 'Setup1pass',
        ]);
        $this->assertGuest();
        $this->assertNull($this->teacher->fresh()->password);

        $this->assertFalse(Auth::attempt([
            'email' => $this->teacher->email,
            'password' => '',
        ]));
        $this->assertFalse(Auth::attempt([
            'email' => $this->teacher->email,
            'password' => 'Setup1pass',
        ]));
        $this->assertGuest();
    }

    public function test_google_sign_in_does_not_open_an_account_without_a_password(): void
    {
        $google = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $google->shouldReceive('getId')->andReturn('google-unset-password');
        $google->shouldReceive('getEmail')->andReturn($this->teacher->email);
        $google->shouldReceive('getName')->andReturn('Unset Teacher');
        $google->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($google);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get('/auth/google/callback')
            ->assertRedirect('/login')
            ->assertSessionHas('failmessage');

        $this->assertGuest();
        $this->assertNull($this->teacher->fresh()->password);
        $this->assertNull($this->teacher->fresh()->google_id);
    }

    public function test_one_time_email_code_does_not_sign_in_or_set_a_password(): void
    {
        $this->withSession(['pending_verification_user_id' => $this->teacher->id])
            ->post('/register/verify/continue')
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertNull($this->teacher->fresh()->password);
    }

    public function test_forgot_password_resends_the_setup_link_and_does_not_set_a_password(): void
    {
        $this->post('/password/reset', ['email' => $this->teacher->email])
            ->assertRedirect();

        $this->assertSame(0, Authentication::query()->where('user_id', $this->teacher->id)->where('type', 'password_reset')->count());
        Mail::assertQueued(TeacherInviteLinkMail::class);
        $this->assertNull($this->teacher->fresh()->password);
        $this->assertGuest();

        $token = Password::createToken($this->teacher);
        $this->post('/password/reset/change', [
            'email' => $this->teacher->email,
            'token' => $token,
            'password' => 'Setup1pass',
            'password_confirmation' => 'Setup1pass',
        ]);

        $this->assertGuest();
        $this->assertNull($this->teacher->fresh()->password);
        $this->assertFalse(Hash::check('Setup1pass', (string) $this->teacher->fresh()->password));
    }

    public function test_password_reset_code_cannot_be_exchanged_for_a_password(): void
    {
        Authentication::create([
            'user_id' => $this->teacher->id,
            'type' => 'password_reset',
            'token' => '123456',
            'ip_address' => '127.0.0.1',
            'expires_on' => now()->addMinutes(5),
            'status' => 0,
        ]);

        $this->post('/password/reset-code/verify', [
            'email' => $this->teacher->email,
            'code' => '123456',
        ])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertNull($this->teacher->fresh()->password);
    }

    public function test_teacher_api_and_parent_token_login_issue_no_token(): void
    {
        $this->postJson('/api/teacher/login', [
            'email' => $this->teacher->mobile_no,
            'password' => '',
            'device_id' => 'device-1',
            'device_name' => 'phone',
        ])->assertStatus(422);
        $this->assertSame(0, $this->teacher->fresh()->tokens()->count());

        $this->postJson('/api/teacher/login', [
            'email' => $this->teacher->mobile_no,
            'password' => 'Setup1pass',
            'device_id' => 'device-1',
            'device_name' => 'phone',
        ])->assertStatus(422);
        $this->assertSame(0, $this->teacher->fresh()->tokens()->count());
        $this->assertGuest();

        $parent = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 7,
            'name' => 'Unset Parent',
            'email' => 'unset.parent@example.test',
            'mobile_no' => '+256700444222',
            'password' => null,
            'email_verified' => 1,
            'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $parent->id,
            'usergroup_id' => 7,
            'firstname' => 'Unset',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        $this->postJson('/api/parent/login', [
            'email' => $parent->mobile_no,
            'password' => 'Setup1pass',
            'device_id' => 'device-1',
            'device_name' => 'phone',
        ]);
        $this->assertSame(0, $parent->fresh()->tokens()->count());
        $this->assertNull($parent->fresh()->password);
        $this->assertGuest();
    }

    public function test_api_password_reset_does_not_set_a_password(): void
    {
        Authentication::create([
            'user_id' => $this->teacher->id,
            'type' => 'reset',
            'token' => '654321',
            'ip_address' => '127.0.0.1',
            'expires_on' => now()->addMinutes(5),
            'status' => 1,
        ]);

        $this->postJson('/api/reset/change/password', [
            'mobile_no' => $this->teacher->mobile_no,
            'usergroup' => 5,
            'oldpassword' => '654321',
            'newpassword' => 'Setup1pass',
            'confirmpassword' => 'Setup1pass',
        ])->assertStatus(422);

        $this->assertNull($this->teacher->fresh()->password);
        $this->assertSame(0, $this->teacher->fresh()->tokens()->count());
    }
}
