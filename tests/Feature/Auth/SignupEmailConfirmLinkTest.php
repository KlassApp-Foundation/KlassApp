<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationCodeMail;
use App\Models\Authentication;
use App\Models\User;
use App\Services\EmailVerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Part D2: the signup verification email carries the 6-digit code AND a signed
 * "Confirm email" link. Either confirms and uses up both.
 */
class SignupEmailConfirmLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('plans')->insert([
            ['id' => 1, 'cycle' => 30, 'name' => 'Freemium', 'display_name' => 'Freemium', 'order' => 1, 'is_active' => 1, 'amount' => 0, 'no_of_students' => 0, 'no_of_users' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
    }

    private function signup(): void
    {
        $this->post('/register', [
            'name' => 'Grace Nakato',
            'email' => 'grace@example.com',
            'phone' => '0701234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsandcondn' => '1',
        ])->assertRedirect(route('register.verify'));
    }

    /** Latest queued verification mail. */
    private function latestMail(): EmailVerificationCodeMail
    {
        $mails = Mail::queued(EmailVerificationCodeMail::class);
        $this->assertNotEmpty($mails, 'No verification mail was queued.');

        return $mails->last();
    }

    private function user(): User
    {
        return User::where('email', 'grace@example.com')->firstOrFail();
    }

    /** Simulate opening the link on a different device: no signup session cookie. */
    private function otherDevice(): void
    {
        $this->flushSession();
    }

    // ---------------------------------------------------------------- code

    public function test_confirm_by_code_still_works_and_uses_up_the_link(): void
    {
        $this->signup();
        $mail = $this->latestMail();

        $this->post('/register/verify', ['code' => $mail->code])->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
        $this->assertSame(1, (int) $this->user()->email_verified);

        $this->otherDevice();
        $this->get($mail->confirmUrl)->assertStatus(410);
        $this->post($mail->confirmUrl)->assertStatus(410);
    }

    // ---------------------------------------------------------------- link

    public function test_get_on_the_link_shows_a_confirm_button_and_does_not_confirm(): void
    {
        $this->signup();
        $mail = $this->latestMail();

        $response = $this->get($mail->confirmUrl);

        $response->assertOk();
        $response->assertSee('Confirm email');
        $response->assertSee('method="POST"', false);
        $this->assertSame(0, (int) $this->user()->email_verified);
        $this->assertNull($this->user()->email_verified_at);
        $this->assertGuest();

        // A scanner hitting it repeatedly still doesn't confirm.
        $this->otherDevice();
        $this->get($mail->confirmUrl)->assertOk();
        $this->assertSame(0, (int) $this->user()->email_verified);
    }

    public function test_confirm_by_link_in_the_signup_browser_continues_into_onboarding(): void
    {
        $this->signup();
        $mail = $this->latestMail();

        $response = $this->post($mail->confirmUrl);

        $response->assertRedirect('/admin/dashboard');
        $response->assertSessionHas('open_toshi_onboarding', true);
        $this->assertStringContainsString('Email confirmed', (string) session('successmessage'));
        $this->assertAuthenticated();

        $user = $this->user();
        $this->assertSame(1, (int) $user->email_verified);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull(session('pending_verification_user_id'));
    }

    public function test_confirm_by_link_on_another_device_confirms_without_signing_that_device_in(): void
    {
        $this->signup();
        $mail = $this->latestMail();
        $this->otherDevice();

        $response = $this->post($mail->confirmUrl);

        $response->assertOk();
        $response->assertSee('Email confirmed');
        $response->assertSee('continue where you signed up');
        $response->assertSee('grace@example.com');
        $response->assertSee('Sign in on this device instead');
        $this->assertGuest();

        $this->assertSame(1, (int) $this->user()->email_verified);
    }

    public function test_the_other_device_page_shows_no_account_data(): void
    {
        $this->signup();
        $school = DB::table('schools')->where('id', $this->user()->school_id)->value('name');
        $mail = $this->latestMail();
        $this->otherDevice();

        $response = $this->post($mail->confirmUrl);

        $response->assertOk();
        if ($school) {
            $response->assertDontSee($school);
        }
        $response->assertDontSee('Grace Nakato');
    }

    public function test_a_different_pending_session_is_treated_as_another_device(): void
    {
        $this->signup();
        $mail = $this->latestMail();

        // Browser holds a pending signup for someone else.
        $this->flushSession();
        $this->withSession(['pending_verification_user_id' => 999999]);

        $this->post($mail->confirmUrl)->assertOk()->assertSee('continue where you signed up');
        $this->assertGuest();
    }

    public function test_reused_link_returns_410(): void
    {
        $this->signup();
        $mail = $this->latestMail();

        $this->otherDevice();
        $this->post($mail->confirmUrl)->assertOk();

        $this->get($mail->confirmUrl)->assertStatus(410);
        $this->post($mail->confirmUrl)->assertStatus(410)->assertSee('This link has expired');
    }

    public function test_expired_link_returns_410_with_send_a_new_code(): void
    {
        $this->signup();
        $mail = $this->latestMail();

        $this->travel(16)->minutes();

        $this->get($mail->confirmUrl)
            ->assertStatus(410)
            ->assertSee('This link has expired')
            ->assertSee('Send a new code')
            ->assertSee('Already confirmed? Just sign in.');
        $this->post($mail->confirmUrl)->assertStatus(410);
        $this->assertSame(0, (int) $this->user()->email_verified);
    }

    public function test_tampered_signature_returns_403_and_does_not_confirm(): void
    {
        $this->signup();
        $mail = $this->latestMail();
        $this->otherDevice();

        $tampered = preg_replace('/signature=[0-9a-f]+/', 'signature=deadbeef', $mail->confirmUrl);
        $this->assertNotSame($mail->confirmUrl, $tampered);

        $this->get($tampered)->assertStatus(403)->assertSee("This link doesn't work", false);
        $this->post($tampered)->assertStatus(403);
        $this->assertSame(0, (int) $this->user()->email_verified);
    }

    public function test_truncated_link_without_signature_returns_403(): void
    {
        $this->signup();
        $mail = $this->latestMail();

        $bare = strtok($mail->confirmUrl, '?');

        $this->get($bare)->assertStatus(403);
        $this->post($bare)->assertStatus(403);
        $this->assertSame(0, (int) $this->user()->email_verified);
    }

    public function test_validly_signed_link_with_unknown_token_returns_410(): void
    {
        $url = \URL::temporarySignedRoute('register.verify.link', now()->addMinutes(5), ['token' => 'not-a-real-token']);

        $this->get($url)->assertStatus(410);
        $this->post($url)->assertStatus(410);
    }

    public function test_link_token_is_hashed_at_rest_and_tied_to_the_pending_user(): void
    {
        $this->signup();
        $mail = $this->latestMail();
        $token = basename(parse_url($mail->confirmUrl, PHP_URL_PATH));

        $row = Authentication::where('type', EmailVerificationCodeService::LINK_TYPE)->where('status', 0)->firstOrFail();

        $this->assertSame($this->user()->id, (int) $row->user_id);
        $this->assertNotSame($token, $row->token);
        $this->assertSame(hash('sha256', $token), $row->token);
        $this->assertGreaterThanOrEqual(40, strlen($token));

        $code = Authentication::where('type', EmailVerificationCodeService::TYPE)->where('status', 0)->firstOrFail();
        $this->assertEqualsWithDelta($code->expires_on->timestamp, $row->expires_on->timestamp, 2);
    }

    public function test_the_signed_url_expires_with_the_code(): void
    {
        $this->signup();
        $mail = $this->latestMail();

        parse_str((string) parse_url($mail->confirmUrl, PHP_URL_QUERY), $query);

        $this->assertEqualsWithDelta(
            now()->addMinutes(EmailVerificationCodeService::MINUTES_VALID)->timestamp,
            (int) $query['expires'],
            5
        );
    }

    // ---------------------------------------------------------------- resend

    public function test_resend_cancels_the_old_code_and_old_link(): void
    {
        $this->signup();
        $first = $this->latestMail();

        $this->post('/register/verify/resend')->assertRedirect(route('register.verify'));
        $second = $this->latestMail();

        $this->assertNotSame($first->confirmUrl, $second->confirmUrl);
        $this->assertNotSame($first->code, $second->code);

        // Old link is dead (from any device) ...
        $this->otherDevice();
        $this->get($first->confirmUrl)->assertStatus(410);
        $this->post($first->confirmUrl)->assertStatus(410);
        $this->assertSame(0, (int) $this->user()->email_verified);

        // ... the new one works.
        $this->post($second->confirmUrl)->assertOk();
        $this->assertSame(1, (int) $this->user()->email_verified);
    }

    public function test_confirming_by_link_cancels_the_code_too(): void
    {
        $this->signup();
        $mail = $this->latestMail();
        $userId = $this->user()->id;

        $this->otherDevice();
        $this->post($mail->confirmUrl)->assertOk();

        $this->assertSame(0, Authentication::where('user_id', $userId)
            ->whereIn('type', [EmailVerificationCodeService::TYPE, EmailVerificationCodeService::LINK_TYPE])
            ->where('status', 0)->count());

        $this->assertSame(EmailVerificationCodeService::EXPIRED, app(EmailVerificationCodeService::class)->verify($this->user(), $mail->code));
    }

    // ---------------------------------------------------------------- email

    public function test_email_has_code_subject_confirm_button_expiry_and_footer_wording(): void
    {
        $this->signup();
        $mail = $this->latestMail();
        $mail->build();

        $this->assertSame('Your KlassApp code is '.$mail->code, $mail->subject);

        $spaced = substr($mail->code, 0, 3).' '.substr($mail->code, 3);

        $mail->assertSeeInHtml('Confirm email');
        $mail->assertSeeInHtml($spaced);
        $mail->assertSeeInHtml('#15803D', false);
        $mail->assertSeeInHtml(EmailVerificationCodeService::MINUTES_VALID.' minutes');
        $mail->assertSeeInHtml('no one can use this account until the email is confirmed');
        $mail->assertDontSeeInHtml('no account is created');
        $mail->assertSeeInHtml('Enter it on the sign-up page, or tap Confirm email.');
        $mail->assertSeeInHtml(htmlspecialchars($mail->confirmUrl, ENT_QUOTES), false);
    }

    public function test_email_plain_text_part_includes_code_link_expiry_and_ignore_line(): void
    {
        $this->signup();
        $mail = $this->latestMail();

        $mail->assertSeeInText($mail->code);
        $mail->assertSeeInText($mail->confirmUrl, false);
        $mail->assertSeeInText(EmailVerificationCodeService::MINUTES_VALID.' minutes');
        $mail->assertSeeInText('no one can use this account until the email is confirmed');
    }

    public function test_email_expiry_follows_the_service_constant_not_copy(): void
    {
        $this->signup();
        $mail = new EmailVerificationCodeMail($this->user(), '482915', 7, 'https://klassapp.test/x');

        $mail->assertSeeInHtml('7 minutes');
        $mail->assertDontSeeInHtml('15 minutes');
    }

    // ---------------------------------------------------------------- status + continue

    public function test_status_is_false_until_confirmed_and_true_after_confirming_elsewhere(): void
    {
        $this->signup();
        $mail = $this->latestMail();

        $this->getJson('/register/verify/status')->assertOk()->assertExactJson(['confirmed' => false]);

        // Another device confirms via the link; this browser keeps its signup session.
        $user = $this->user();
        app(EmailVerificationCodeService::class)->confirmLinkToken(
            basename(parse_url($mail->confirmUrl, PHP_URL_PATH))
        );

        $this->getJson('/register/verify/status')->assertOk()->assertExactJson(['confirmed' => true]);
        $this->assertGuest();
        $this->assertSame(1, (int) $user->fresh()->email_verified);
    }

    public function test_polling_status_does_not_use_up_the_resend_throttle(): void
    {
        $this->signup();

        // ~1 minute of polling at 5s, then a resend: must not be 429.
        for ($i = 0; $i < 12; $i++) {
            $this->getJson('/register/verify/status')->assertOk();
        }

        $this->post('/register/verify/resend')->assertRedirect(route('register.verify'));
    }

    public function test_status_is_false_without_a_pending_session(): void
    {
        $this->getJson('/register/verify/status')->assertOk()->assertExactJson(['confirmed' => false]);
    }

    public function test_status_never_leaks_another_browsers_confirmation(): void
    {
        $this->signup();
        $mail = $this->latestMail();
        $this->otherDevice();
        $this->post($mail->confirmUrl)->assertOk();

        // The "other device" has no pending session, so it learns nothing.
        $this->getJson('/register/verify/status')->assertExactJson(['confirmed' => false]);
    }

    public function test_original_tab_continues_after_confirmation_on_another_device(): void
    {
        $this->signup();
        $mail = $this->latestMail();
        $token = basename(parse_url($mail->confirmUrl, PHP_URL_PATH));

        // Not confirmed yet: continue must not sign anyone in.
        $this->post(route('register.verify.continue'))->assertRedirect(route('register.verify'));
        $this->assertGuest();

        app(EmailVerificationCodeService::class)->confirmLinkToken($token);

        $response = $this->post(route('register.verify.continue'));

        $response->assertRedirect('/admin/dashboard');
        $response->assertSessionHas('open_toshi_onboarding', true);
        $this->assertStringContainsString('Email confirmed', (string) session('successmessage'));
        $this->assertAuthenticated();
    }

    public function test_continue_without_a_pending_session_goes_to_signup(): void
    {
        $this->post(route('register.verify.continue'))->assertRedirect(route('register'));
        $this->assertGuest();
    }

    public function test_verify_page_polls_status_every_five_seconds_and_on_visibilitychange(): void
    {
        $this->signup();

        $response = $this->get(route('register.verify'));

        $response->assertOk();
        $response->assertSee(route('register.verify.status'), false);
        $response->assertSee('visibilitychange', false);
        $response->assertSee('5000', false);
        $response->assertSee('Confirm email');
        $response->assertSee('continues on its own');
    }
}
