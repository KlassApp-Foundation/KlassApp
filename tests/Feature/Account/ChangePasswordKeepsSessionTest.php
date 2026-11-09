<?php

namespace Tests\Feature\Account;

use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Services\AccountPasswordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\Support\AccountRoleFixture;
use Tests\TestCase;

/**
 * Changing the password keeps THIS device signed in and signs every other device out.
 * Before: the change left the remember-me cookie stale (it embeds the old password hash), so
 * the person was signed out when their session next lapsed.
 */
class ChangePasswordKeepsSessionTest extends TestCase
{
    use AccountRoleFixture;
    use RefreshDatabase;

    private const NEW = 'Brand-new-pass-77';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBeTeacher::class, MustBeSchoolAdmin::class]);
        $this->buildRoleFixture();
    }

    private function change(string $url = '/account/password', string $old = 'old-pass-123', bool $json = false)
    {
        $data = ['oldpassword' => $old, 'newpassword' => self::NEW, 'confirmpassword' => self::NEW];

        return $json ? $this->postJson($url, $data) : $this->post($url, $data);
    }

    /** A different device: no guard state, a session that still holds the password hash it stored at sign-in. */
    private function asOtherDevice(User $user, string $storedStamp): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->withSession([
            Auth::guard()->getName() => $user->getAuthIdentifier(),
            'password_hash_web' => $storedStamp,
        ]);
    }

    public function test_the_current_device_stays_signed_in_and_sees_a_clear_message(): void
    {
        $user = $this->userFor(5);
        $this->post('/login', ['email' => $user->email, 'password' => 'old-pass-123'])->assertRedirect('/teacher/dashboard');

        $response = $this->change();

        $response->assertRedirect(route('account.password'));
        $response->assertSessionHas('status', AccountPasswordService::SUCCESS_MESSAGE);
        $this->assertTrue(Hash::check(self::NEW, $user->fresh()->password));
        $this->assertAuthenticatedAs($user->fresh());

        $this->get('/account/password')->assertOk()
            ->assertSee('data-testid="account-password-success"', false)
            ->assertSee('You are still signed in here');
        $this->get('/account/profile')->assertOk();
    }

    public function test_another_device_is_signed_out_by_the_change(): void
    {
        $user = $this->userFor(5);
        $this->post('/login', ['email' => $user->email, 'password' => 'old-pass-123']);
        $oldStamp = (string) session('password_hash_web');
        $this->assertNotSame('', $oldStamp, 'the sign-in stored the password hash in the session');

        $this->change()->assertRedirect(route('account.password'));

        $this->get('/account/profile')->assertOk(); // this device carries on

        $this->asOtherDevice($user, $oldStamp);
        $this->get('/account/profile')->assertRedirect('/login');
    }

    public function test_a_remember_me_cookie_issued_before_the_change_stops_working_and_this_device_gets_a_fresh_one(): void
    {
        $user = $this->userFor(5);
        $recaller = Auth::guard()->getRecallerName();

        $login = $this->post('/login', ['email' => $user->email, 'password' => 'old-pass-123', 'remember' => 'on']);
        $oldCookie = $login->getCookie($recaller, false)?->getValue();
        $this->assertNotNull($oldCookie, 'remember me issued a cookie');

        $change = $this->change();
        $newCookie = $change->getCookie($recaller, false)?->getValue();
        $this->assertNotNull($newCookie, 'the change re-issued this device\'s remember-me cookie');
        $this->assertNotSame($oldCookie, $newCookie);

        // Session lapsed, only the cookie is left: the fresh cookie restores the user, the old one does not.
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->withUnencryptedCookie($recaller, $newCookie)->get('/account/profile')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->withUnencryptedCookie($recaller, $oldCookie)->get('/account/profile')->assertRedirect('/login');
    }

    public function test_the_existing_per_role_change_password_endpoints_behave_the_same(): void
    {
        foreach ([5 => '/teacher/changepassword', 6 => '/student/changepassword'] as $group => $url) {
            $this->flushSession();
            $this->app['auth']->forgetGuards();
            $user = $this->userFor($group);
            $this->post('/login', ['email' => $user->email, 'password' => 'old-pass-123']);

            $this->change($url, json: true)->assertOk()
                ->assertJson(['message' => AccountPasswordService::SUCCESS_MESSAGE]);

            $this->assertTrue(Hash::check(self::NEW, $user->fresh()->password), self::ROLES[$group]);
            $this->get('/account/profile')->assertOk();
            Auth::logout();
        }
    }

    public function test_a_wrong_current_password_changes_nothing(): void
    {
        $user = $this->userFor(5);
        $this->actingAs($user);

        $this->change(old: 'not-my-password')->assertSessionHasErrors('oldpassword');

        $this->assertTrue(Hash::check('old-pass-123', $user->fresh()->password));
        $this->get('/account/profile')->assertOk();
    }

    public function test_a_new_password_must_differ_and_be_confirmed(): void
    {
        $user = $this->userFor(5);
        $this->actingAs($user);

        $this->post('/account/password', ['oldpassword' => 'old-pass-123', 'newpassword' => 'old-pass-123', 'confirmpassword' => 'old-pass-123'])
            ->assertSessionHasErrors('newpassword');
        $this->post('/account/password', ['oldpassword' => 'old-pass-123', 'newpassword' => self::NEW, 'confirmpassword' => 'different-pass'])
            ->assertSessionHasErrors('confirmpassword');

        $this->assertTrue(Hash::check('old-pass-123', $user->fresh()->password));
    }
}
