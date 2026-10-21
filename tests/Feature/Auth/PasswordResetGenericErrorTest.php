<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Mail\ResetPasswordCodeMail;
use App\Models\Authentication;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Failed set-password submissions share one message. A different message for
 * an unknown email, a bad token, or a throttle would show whether an account exists.
 */
class PasswordResetGenericErrorTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC = 'This link is invalid or has expired. Request a new one.';

    private const CODE_GENERIC = 'Invalid or expired code. Please request a new one.';

    private School $school;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutVite();

        $this->school = School::create([
            'name' => 'Reset Message School',
            'slug' => 'reset-message-school',
            'email' => 'school@reset-message.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Reset Message Teacher',
            'email' => 'reset-message@test.sch.ug',
            'password' => Hash::make('OldPassword!123'),
            'is_reset' => 0,
            'status' => 'active',
            'email_verified' => 1,
            'email_verified_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Password::clearResolvedInstances();

        parent::tearDown();
    }

    public function test_unknown_email_and_bad_token_show_the_same_generic_message(): void
    {
        $token = 'not-a-real-token';

        $unknown = $this->submitReset('nobody@example.com', $token);
        $unknownMessage = session('errors')->get('email');

        $badToken = $this->submitReset($this->user->email, $token);
        $badTokenMessage = session('errors')->get('email');

        $unknown->assertRedirect('/password/reset/'.$token);
        $badToken->assertRedirect('/password/reset/'.$token);
        $this->assertSame([self::GENERIC], $unknownMessage);
        $this->assertSame($unknownMessage, $badTokenMessage);
        $this->assertSame($unknown->status(), $badToken->status());

        $this->followRedirects($badToken)
            ->assertOk()
            ->assertSee(self::GENERIC)
            ->assertDontSee("We can't find a user with that e-mail address.")
            ->assertDontSee('This password reset token is invalid.');

        $unknownJson = $this->postJson('/password/reset/change', $this->resetPayload('nobody@example.com', $token));
        $badTokenJson = $this->postJson('/password/reset/change', $this->resetPayload($this->user->email, $token));

        $unknownJson->assertStatus(422);
        $this->assertSame($unknownJson->status(), $badTokenJson->status());
        $this->assertSame($unknownJson->json(), $badTokenJson->json());
        $this->assertSame(self::GENERIC, $unknownJson->json('errors.email.0'));
        $this->assertStringNotContainsString('e-mail address', $unknownJson->getContent());
    }

    public function test_expired_and_used_tokens_show_the_same_generic_message(): void
    {
        $expired = Password::createToken($this->user);
        DB::table('password_resets')->where('email', $this->user->email)->update([
            'created_at' => now()->subHours(2),
        ]);

        $expiredResponse = $this->submitReset($this->user->email, $expired);
        $expiredResponse->assertSessionHasErrors(['email' => self::GENERIC]);
        $this->assertTrue(Hash::check('OldPassword!123', $this->user->fresh()->password));

        $used = Password::createToken($this->user);
        $this->submitReset($this->user->email, $used)->assertRedirect();
        $this->assertTrue(Hash::check('NewSecret123!x', $this->user->fresh()->password));

        auth()->logout();

        $usedResponse = $this->submitReset($this->user->email, $used);
        $usedResponse->assertSessionHasErrors(['email' => self::GENERIC]);
        $this->assertSame(
            session('errors')->get('email'),
            [self::GENERIC]
        );
    }

    public function test_throttled_reset_shows_the_same_generic_message(): void
    {
        $broker = new class implements PasswordBroker
        {
            public function sendResetLink(array $credentials, ?\Closure $callback = null)
            {
                return static::RESET_THROTTLED;
            }

            public function reset(array $credentials, \Closure $callback)
            {
                return static::RESET_THROTTLED;
            }
        };

        $manager = new class($this->app, $broker) extends PasswordBrokerManager
        {
            public function __construct($app, private readonly PasswordBroker $fixedBroker)
            {
                parent::__construct($app);
            }

            public function broker($name = null)
            {
                return $this->fixedBroker;
            }
        };

        Password::clearResolvedInstances();
        $this->app->instance('auth.password', $manager);

        $response = $this->submitReset($this->user->email, 'some-token');

        $response->assertRedirect('/password/reset/some-token');
        $response->assertSessionHasErrors(['email' => self::GENERIC]);
        $this->assertTrue(Hash::check('OldPassword!123', $this->user->fresh()->password));
    }

    public function test_valid_reset_still_updates_the_password(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->submitReset($this->user->email, $token);

        $response->assertRedirect('/home');
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($this->user);
        $this->assertTrue(Hash::check('NewSecret123!x', $this->user->fresh()->password));
        $this->assertFalse(Hash::check('OldPassword!123', $this->user->fresh()->password));
    }

    public function test_password_confirmation_failure_stays_a_password_error(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->from('/password/reset/'.$token)->post('/password/reset/change', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'NewSecret123!x',
            'password_confirmation' => 'DifferentSecret123!x',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertFalse(session('errors')->has('email'));
        $this->assertStringNotContainsString(self::GENERIC, (string) session('errors')->first('password'));
        $this->assertTrue(Hash::check('OldPassword!123', $this->user->fresh()->password));
        $this->assertGuest();
    }

    public function test_reset_code_unknown_email_matches_a_bad_code(): void
    {
        Authentication::create([
            'user_id' => $this->user->id,
            'type' => 'password_reset',
            'token' => '123456',
            'ip_address' => '127.0.0.1',
            'expires_on' => now()->addMinutes(5),
            'status' => 0,
        ]);

        $page = '/password/reset-code?email='.$this->user->email;

        $badCode = $this->from($page)->post('/password/reset-code/verify', [
            'email' => $this->user->email,
            'code' => '000000',
        ]);
        $badCodeErrors = session('errors')->getMessages();

        $unknown = $this->from($page)->post('/password/reset-code/verify', [
            'email' => 'nobody@example.com',
            'code' => '000000',
        ]);

        $badCode->assertRedirect($page);
        $unknown->assertRedirect($page);
        $this->assertSame([self::CODE_GENERIC], $badCodeErrors['code']);
        $this->assertSame($badCodeErrors, session('errors')->getMessages());
        $this->assertArrayNotHasKey('email', session('errors')->getMessages());
        $this->assertSame(0, (int) Authentication::first()->status);
    }

    public function test_valid_reset_code_still_opens_the_set_password_form(): void
    {
        Authentication::create([
            'user_id' => $this->user->id,
            'type' => 'password_reset',
            'token' => '123456',
            'ip_address' => '127.0.0.1',
            'expires_on' => now()->addMinutes(5),
            'status' => 0,
        ]);

        $response = $this->post('/password/reset-code/verify', [
            'email' => $this->user->email,
            'code' => '123456',
        ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/password/reset/', (string) $location);
        $this->assertStringContainsString('email='.urlencode($this->user->email), (string) $location);
        $this->assertSame(1, (int) Authentication::first()->status);
    }

    public function test_reset_code_resend_uses_the_same_response_for_an_unknown_email(): void
    {
        Mail::fake();

        $known = $this->post('/password/reset-code/resend', ['email' => $this->user->email]);
        $unknown = $this->post('/password/reset-code/resend', ['email' => 'nobody@example.com']);

        $known->assertRedirect(route('password.reset.code', ['email' => $this->user->email]));
        $unknown->assertRedirect(route('password.reset.code', ['email' => 'nobody@example.com']));
        $known->assertSessionHas('status', 'A new code has been sent to your email.');
        $unknown->assertSessionHas('status', 'A new code has been sent to your email.');
        $this->assertNull(session('errors'));

        Mail::assertSent(ResetPasswordCodeMail::class, 1);
    }

    private function submitReset(string $email, string $token)
    {
        return $this->from('/password/reset/'.$token)
            ->post('/password/reset/change', $this->resetPayload($email, $token));
    }

    private function resetPayload(string $email, string $token): array
    {
        return [
            'token' => $token,
            'email' => $email,
            'password' => 'NewSecret123!x',
            'password_confirmation' => 'NewSecret123!x',
        ];
    }
}
