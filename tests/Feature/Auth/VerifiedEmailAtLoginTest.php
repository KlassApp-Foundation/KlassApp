<?php

namespace Tests\Feature\Auth;

use App\Helpers\AuthRedirectHelper;
use App\Mail\EmailVerificationCodeMail;
use App\Models\School;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\EmailVerificationGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * An account whose email is not verified must never reach a dashboard by
 * signing in. Every password-based sign-in path sends the user to the
 * code-entry screen instead (with a resend), while verified users
 * (invite links, Google, the sign-up code flow, and the pre-gate
 * backfill) keep working as before. Accounts without an email are
 * exempt — there is nothing to verify.
 */
class VerifiedEmailAtLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
    }

    private function makeSchool(): School
    {
        return School::create([
            'name' => 'Verified Email Test School',
            'email' => 've-users@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
        ]);
    }

    private function makeUser(School $school, int $groupId, string $email = '', bool $verified = false, ?string $emailOverride = null, string $createdAt = '2026-10-18 08:00:00'): User
    {
        $mobile = '+25677'.random_int(1000000, 9999999);

        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => $groupId,
            'name' => 'Verified Email Tester',
            'email' => (($emailOverride ?? $email) ?: null),
            'mobile_no' => $mobile,
            'password' => Hash::make('correct-password'),
            'email_verified' => $verified ? 1 : 0,
            'email_verified_at' => $verified ? now() : null,
            'status' => 'active',
        ]);

        $profile = new Userprofile;
        $profile->forceFill([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $groupId,
            'firstname' => 'Verified',
            'lastname' => 'Email Tester',
            'status' => 'active',
        ])->save();

        if ($groupId === 5) {
            $academicYear = \App\Models\AcademicYear::create([
                'school_id' => $school->id,
                'name' => '2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
            ]);

            TeacherProfile::create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'designation' => 'teacher',
                'status' => 1,
            ]);
        }

        // Not in $fillable on User: drawn dates are pinned so the gate's
        // cutoff rule is deterministic regardless of the test machine clock.
        DB::table('users')->where('id', $user->id)->update(['created_at' => $createdAt]);

        return $user->refresh();
    }

    private function queuedCode(): string
    {
        $code = null;
        Mail::assertQueued(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return $code !== null;
        });
        $this->assertNotNull($code, 'The verification code was not queued in the mail.');

        return $code;
    }

    // ---- 1. Web login (POST /login, Auth::routes) -------------------------

    public function test_unverified_web_login_is_sent_to_the_code_entry_screen_not_the_dashboard(): void
    {
        $school = $this->makeSchool();
        $this->makeUser($school, 3, 'gate-admin@example.com');

        $response = $this->post('/login', [
            'email' => 'gate-admin@example.com',
            'password' => 'correct-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register.verify'));

        $screen = $this->get(route('register.verify'));
        $screen->assertOk();
        $screen->assertSee('gate-admin@example.com');
    }

    public function test_verified_web_login_still_reaches_the_dashboard(): void
    {
        $school = $this->makeSchool();
        $this->makeUser($school, 3, 'ok-admin@example.com', verified: true);

        $response = $this->post('/login', [
            'email' => 'ok-admin@example.com',
            'password' => 'correct-password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(AuthRedirectHelper::dashboardPathForUser(auth()->user()));
    }

    public function test_wrong_password_on_an_unverified_account_still_fails_plain(): void
    {
        $school = $this->makeSchool();
        $this->makeUser($school, 3, 'wrongpw@example.com');

        $this->post('/login', [
            'email' => 'wrongpw@example.com',
            'password' => 'nope',
        ]);

        $this->assertGuest();
        $this->assertNull(session('pending_verification_user_id'));
    }

    public function test_confirming_the_code_after_login_signs_the_user_in(): void
    {
        $school = $this->makeSchool();
        $this->makeUser($school, 3, 'code-admin@example.com');

        $this->post('/login', [
            'email' => 'code-admin@example.com',
            'password' => 'correct-password',
        ]);

        $code = $this->queuedCode();

        $this->post(route('register.verify.submit'), ['code' => $code]);

        $this->assertAuthenticated();
        $this->assertSame(1, (int) User::where('email', 'code-admin@example.com')->first()->email_verified);
        $this->followRedirects($this->get('/'))
            ->assertOk();
    }

    public function test_resending_from_the_login_gate_issues_a_fresh_code(): void
    {
        // The resend endpoint is throttled 3/min and its counters live in the
        // persisted file cache — earlier suites' hits at 127.0.0.1 can still
        // be inside their window when this test runs. The throttle is network
        // policy, not this test's subject, so bypass it for determinism.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $school = $this->makeSchool();
        $this->makeUser($school, 3, 'resend-admin@example.com');

        $this->post('/login', [
            'email' => 'resend-admin@example.com',
            'password' => 'correct-password',
        ]);

        $first = $this->queuedCode();
        Mail::fake();

        $this->post(route('register.verify.resend'));
        $second = $this->queuedCode();

        $this->assertNotSame($first, $second, 'Resend must issue a NEW code, not reuse the old one.');
        $this->assertGuest();
    }

    // ---- 2. Password reset completes -> still must not auto-login ---------

    public function test_password_reset_does_not_autologin_an_unverified_user(): void
    {
        $school = $this->makeSchool();
        $this->makeUser($school, 3, 'reset-admin@example.com');

        $user = User::where('email', 'reset-admin@example.com')->firstOrFail();
        $token = Password::createToken($user);

        $response = $this->call('POST', '/password/reset/change', [
            'email' => 'reset-admin@example.com',
            'token' => $token,
            'password' => 'NewSecret123!x',
            'password_confirmation' => 'NewSecret123!x',
        ]);

        $this->assertGuest();
        $this->assertNull(auth()->user());
        $response->assertRedirect(route('register.verify'));
    }

    public function test_password_reset_autologin_still_works_for_a_verified_user(): void
    {
        $school = $this->makeSchool();
        $this->makeUser($school, 3, 'reset-ok@example.com', verified: true);

        $user = User::where('email', 'reset-ok@example.com')->firstOrFail();
        $token = Password::createToken($user);

        $this->call('POST', '/password/reset/change', [
            'email' => 'reset-ok@example.com',
            'token' => $token,
            'password' => 'NewSecret123!x',
            'password_confirmation' => 'NewSecret123!x',
        ]);

        $this->assertAuthenticated();
    }

    // ---- 3. Mobile-token API logins ---------------------------------------
    // NOTE: Api\LoginController::login (the old generic /api/login) has no
    // route today — only its logout endpoints are registered — but its
    // controller is gated the same way in case a route comes back. The two
    // token sign-ins below are the live mobile paths.

    public function test_mobile_parent_api_login_refuses_an_unverified_user(): void
    {
        $school = $this->makeSchool();
        $user = $this->makeUser($school, 7, 'gate-parent@example.com');

        $response = $this->postJson('/api/parent/login', [
            'email' => $user->mobile_no,
            'password' => 'correct-password',
            'device_id' => 'test-device',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(403);
        $this->assertSame(0, $user->refresh()->tokens()->count());
        $this->assertGuest();
    }

    public function test_teacher_api_login_refuses_an_unverified_user(): void
    {
        $school = $this->makeSchool();
        $user = $this->makeUser($school, 5, 'gate-teacher@example.com');

        $response = $this->postJson('/api/teacher/login', [
            'email' => $user->mobile_no,
            'password' => 'correct-password',
            'device_id' => 'test-device',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(403);
        $this->assertSame(0, $user->refresh()->tokens()->count());
        $this->assertGuest();
    }

    public function test_teacher_api_login_still_issues_tokens_for_verified_users(): void
    {
        $school = $this->makeSchool();
        $user = $this->makeUser($school, 5, 'ok-teacher@example.com', verified: true);

        $response = $this->postJson('/api/teacher/login', [
            'email' => $user->mobile_no,
            'password' => 'correct-password',
            'device_id' => 'test-device',
            'device_name' => 'test-device',
        ]);

        $response->assertOk();
        $this->assertSame(1, $user->refresh()->tokens()->count());
    }

    // ---- 4. Accounts without emails are never gated ------------------------

    public function test_the_gate_only_asks_when_the_rule_says_so(): void
    {
        $school = $this->makeSchool();

        // WhatsApp-only accounts: no email, nothing to verify.
        $this->assertTrue(
            EmailVerificationGate::needsVerification(
                $this->makeUser($school, 5, emailOverride: '')
            ) === false
        );

        // Pre-gate accounts: created before the cutoff, never locked out.
        $preGate = $this->makeUser($school, 3, 'pre-gate@example.com', createdAt: '2026-10-01 08:00:00');
        $this->assertFalse(EmailVerificationGate::needsVerification($preGate));

        // New signups on/after the cutoff with an unconfirmed email.
        $this->assertTrue(
            EmailVerificationGate::needsVerification(
                $this->makeUser($school, 3, 'post-gate@example.com')
            )
        );
    }

    public function test_cutoff_boundary_is_a_utc_instant_not_an_app_timezone_day(): void
    {
        // A Kampala deployment (the timezone .env.example ships) wrote this
        // account at 2026-10-05 01:00 +03 — the same instant as 2026-10-04
        // 22:00 UTC, before the gate's UTC cutoff. An app-timezone startOfDay
        // comparison gated it the moment the Kampala calendar ticked over;
        // the boundary must not depend on which timezone the app runs in.
        // The lens switch is runtime so this test carries its own timezone
        // whether the suite boots as UTC (local) or Kampala (CI).
        $previousTz = date_default_timezone_get();
        date_default_timezone_set('Africa/Kampala');

        try {
            $school = $this->makeSchool();

            $preGateByInstant = $this->makeUser(
                $school, 3, 'tz-boundary@example.com', createdAt: '2026-10-05 01:00:00'
            );
            $this->assertFalse(EmailVerificationGate::needsVerification($preGateByInstant->refresh()));

            // A genuinely post-cutoff instant still gets asked, Kampala lens or not.
            $postGate = $this->makeUser(
                $school, 3, 'tz-gated@example.com', createdAt: '2026-10-05 13:00:00'
            );
            $this->assertTrue(EmailVerificationGate::needsVerification($postGate->refresh()));
        } finally {
            date_default_timezone_set($previousTz);
        }
    }

    public function test_accounts_without_an_email_can_still_sign_in_on_the_mobile_api(): void
    {
        $school = $this->makeSchool();
        $user = $this->makeUser($school, 5, emailOverride: '');

        $response = $this->postJson('/api/teacher/login', [
            'email' => $user->mobile_no,
            'password' => 'correct-password',
            'device_id' => 'test-device',
            'device_name' => 'test-device',
        ]);

        $response->assertOk();
        $this->assertSame(1, $user->refresh()->tokens()->count());
    }

    // ---- 5. Google accounts verify by construction --------------------------

    public function test_google_sign_in_verifies_the_account_and_signs_it_in(): void
    {
        $school = $this->makeSchool();
        $user = $this->makeUser($school, 3, 'google-admin@example.com');
        $this->assertSame(0, (int) $user->email_verified);

        $googleId = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $googleId->shouldReceive('getId')->andReturn('google-verified-email-test-1');
        $googleId->shouldReceive('getEmail')->andReturn('google-admin@example.com');
        $googleId->shouldReceive('getName')->andReturn('Verified Email Tester');
        $googleId->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($googleId);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, (int) $user->refresh()->email_verified);
    }
}
