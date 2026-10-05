<?php

namespace Tests\Feature\Auth;

use App\Models\School;
use App\Models\User;
use App\Services\EmailVerificationGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The gate's cutoff is a config value fed by EMAIL_VERIFICATION_CUTOFF,
 * not a baked-in constant: with the variable unset the built-in default
 * keeps today's behaviour unchanged; an override moves the boundary in
 * either direction; and no cutoff value can ever gate an account whose
 * email is already verified. A configured offset-carrying instant keeps
 * that offset — only the absence of one means UTC.
 */
class EmailVerificationCutoffConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Every test states its own cutoff assumption; nothing leaks in.
        config(['app.email_verification_cutoff' => null]);
    }

    private function unverifiedAt(string $createdAt): User
    {
        $school = School::create([
            'name' => 'Cutoff Config School '.Str::random(6),
            'email' => 'cutoff-test-'.Str::random(6).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Cutoff Config Tester',
            'email' => 'cutoff-'.Str::random(6).'@example.com',
            'mobile_no' => '+25677'.random_int(1000000, 9999999),
            'password' => \Illuminate\Support\Facades\Hash::make('cutoff-password'),
            'email_verified' => 0,
            'status' => 'active',
        ]);

        // created_at is not mass-assignable on purpose; pin the drawn date
        // so the boundary maths never depends on the machine clock.
        DB::table('users')->where('id', $user->id)->update(['created_at' => $createdAt]);

        return $user->refresh();
    }

    private function verifiedAt(string $createdAt): User
    {
        $user = $this->unverifiedAt($createdAt);
        User::where('id', $user->id)->update(['email_verified' => 1]);

        return $user->refresh();
    }

    public function test_unset_config_keeps_the_builtin_default_unchanged(): void
    {
        // Far enough from the boundary hour that either timezone lens
        // (the suite boots UTC locally, Kampala in CI) means the same
        // side of it; the instant-vs-calendar boundary itself has its
        // own dedicated test in VerifiedEmailAtLoginTest.
        $justBefore = $this->unverifiedAt('2026-10-04 18:00:00');
        $justAfter = $this->unverifiedAt('2026-10-05 15:00:00');

        $this->assertFalse(EmailVerificationGate::needsVerification($justBefore));
        $this->assertTrue(EmailVerificationGate::needsVerification($justAfter));
        $this->assertSame('2026-10-05', EmailVerificationGate::CUTOFF);
        $this->assertEquals(
            \Carbon\Carbon::parse('2026-10-05', 'UTC'),
            EmailVerificationGate::cutoffInstant()
        );
    }

    public function test_an_earlier_override_gates_accounts_the_default_exempted(): void
    {
        config(['app.email_verification_cutoff' => '2026-10-01T00:00:00Z']);

        $midWindow = $this->unverifiedAt('2026-10-02 08:00:00');
        $stillOlder = $this->unverifiedAt('2026-09-30 08:00:00');

        $this->assertTrue(EmailVerificationGate::needsVerification($midWindow));
        $this->assertFalse(EmailVerificationGate::needsVerification($stillOlder));
    }

    public function test_a_later_override_exempts_accounts_the_default_gated(): void
    {
        config(['app.email_verification_cutoff' => '2026-10-10T12:00:00Z']);

        $recent = $this->unverifiedAt('2026-10-06 08:00:00');

        $this->assertFalse(EmailVerificationGate::needsVerification($recent));
    }

    public function test_an_offset_carrying_is8601_instant_keeps_its_offset(): void
    {
        // 2026-10-05 03:00 +03 == 2026-10-05 00:00 UTC, the same boundary.
        config(['app.email_verification_cutoff' => '2026-10-05T03:00:00+03:00']);

        $this->assertTrue(
            EmailVerificationGate::cutoffInstant()->equalTo(
                \Carbon\Carbon::parse('2026-10-05 00:00:00', 'UTC')
            )
        );

        $justBefore = $this->unverifiedAt('2026-10-04 23:59:59');
        $this->assertFalse(EmailVerificationGate::needsVerification($justBefore));
    }

    public function test_a_blank_value_falls_back_to_the_builtin_default(): void
    {
        config(['app.email_verification_cutoff' => '   ']);

        $this->assertEquals(
            \Carbon\Carbon::parse('2026-10-05', 'UTC'),
            EmailVerificationGate::cutoffInstant()
        );
    }

    public function test_a_verified_account_is_never_gated_whatever_the_cutoff(): void
    {
        foreach ([null, '2026-10-01T00:00:00Z', '2027-01-01T00:00:00Z'] as $cutoff) {
            config(['app.email_verification_cutoff' => $cutoff]);

            $verified = $this->verifiedAt('2026-10-06 08:00:00');
            $this->assertFalse(
                EmailVerificationGate::needsVerification($verified),
                "email_verified=1 must never be gated (cutoff {$cutoff})"
            );
        }
    }
}
