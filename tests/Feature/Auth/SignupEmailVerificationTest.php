<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationCodeMail;
use App\Models\Authentication;
use App\Models\User;
use App\Services\EmailVerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SignupEmailVerificationTest extends TestCase
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

    private function signup(array $overrides = [])
    {
        return $this->post('/register', array_merge([
            'name' => 'Grace Nakato',
            'email' => 'grace@example.com',
            'phone' => '0701234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsandcondn' => '1',
        ], $overrides));
    }

    private function queuedCode(): string
    {
        $code = null;

        Mail::assertQueued(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->assertNotNull($code, 'The verification code was not queued in the mail.');

        return $code;
    }

    private function outstandingCodeRow(User $user): Authentication
    {
        return Authentication::where('user_id', $user->id)
            ->where('type', EmailVerificationCodeService::TYPE)
            ->where('status', 0)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    public function test_signup_redirects_to_verification_without_authenticating(): void
    {
        $response = $this->signup();

        $response->assertRedirect(route('register.verify'));
        $this->assertGuest();

        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $this->assertSame(0, (int) $user->email_verified);
        $this->assertNull($user->email_verified_at);
    }

    public function test_signup_queues_the_verification_email(): void
    {
        $this->signup();

        Mail::assertQueued(EmailVerificationCodeMail::class);
    }

    public function test_code_is_hashed_at_rest_and_never_stored_in_plaintext(): void
    {
        $this->signup();

        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $code = $this->queuedCode();
        $row = $this->outstandingCodeRow($user);

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, $row->token);
        $this->assertTrue(Hash::check($code, $row->token));
        $this->assertFalse(Hash::check('000000', $row->token));
    }

    public function test_code_expires_after_fifteen_minutes(): void
    {
        $this->signup();

        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $row = $this->outstandingCodeRow($user);

        $this->assertEqualsWithDelta(now()->addMinutes(15)->timestamp, $row->expires_on->timestamp, 5);
    }

    public function test_correct_code_authenticates_and_marks_email_verified(): void
    {
        $this->signup();
        $code = $this->queuedCode();

        $response = $this->post('/register/verify', ['code' => $code]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $this->assertSame(1, (int) $user->email_verified);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_expired_code_is_rejected(): void
    {
        $this->signup();
        $code = $this->queuedCode();

        $this->travel(16)->minutes();

        $response = $this->post('/register/verify', ['code' => $code]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();

        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $this->assertSame(0, (int) $user->email_verified);
    }

    public function test_incorrect_code_is_rejected_and_counts_toward_lockout(): void
    {
        $this->signup();
        $code = $this->queuedCode();
        $user = User::where('email', 'grace@example.com')->firstOrFail();

        $wrong = $code === '111111' ? '222222' : '111111';

        $this->post('/register/verify', ['code' => $wrong])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertSame(1, (int) $this->outstandingCodeRow($user)->attempts);
        $this->assertSame(0, (int) $user->fresh()->email_verified);
    }

    public function test_code_locks_after_five_failed_attempts_and_rejects_the_right_code(): void
    {
        $this->signup();
        $code = $this->queuedCode();
        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $wrong = $code === '111111' ? '222222' : '111111';

        for ($i = 0; $i < EmailVerificationCodeService::MAX_ATTEMPTS; $i++) {
            $this->post('/register/verify', ['code' => $wrong])->assertSessionHasErrors('code');
        }

        $row = $this->outstandingCodeRow($user);
        $this->assertSame(EmailVerificationCodeService::MAX_ATTEMPTS, (int) $row->attempts);
        $this->assertNotNull($row->locked_until);
        $this->assertTrue($row->locked_until->isFuture());

        $this->post('/register/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertSame(0, (int) $user->fresh()->email_verified);
    }

    public function test_resend_supersedes_the_previous_code(): void
    {
        $this->signup();
        $first = $this->queuedCode();

        $this->post('/register/verify/resend')->assertRedirect(route('register.verify'));

        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $this->assertSame(
            1,
            Authentication::where('user_id', $user->id)
                ->where('type', EmailVerificationCodeService::TYPE)
                ->where('status', 1)
                ->count()
        );

        $row = $this->outstandingCodeRow($user);
        $this->assertNotSame($first, $row->token);
        $this->assertSame(0, (int) $row->attempts);

        $newCode = null;
        Mail::assertQueued(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use (&$newCode) {
            $newCode = $mail->code;

            return $mail->code !== $first;
        });

        $this->post('/register/verify', ['code' => $first])
            ->assertSessionHasErrors('code');

        $this->post('/register/verify', ['code' => $newCode])
            ->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }

    public function test_verification_page_renders_for_a_pending_signup(): void
    {
        $this->signup();

        $response = $this->get(route('register.verify'));

        $response->assertOk();
        $response->assertSee('grace@example.com');
        $response->assertSee('Check your email');
    }

    public function test_verification_redirects_to_signup_without_a_pending_session(): void
    {
        $this->get(route('register.verify'))->assertRedirect(route('register'));

        $this->post('/register/verify', ['code' => '123456'])->assertRedirect(route('register'));
    }
}
