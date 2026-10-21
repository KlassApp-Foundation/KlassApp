<?php

namespace App\Services\WhatsApp;

use App\Services\DemoOnlyRecipientGuard;
use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Shared onboarding WhatsApp OTP used by AgentToshi (chat) and ManualOnboardingWizard.
 * Keeps generate / deliver / compare in one place so the two UIs cannot drift.
 */
class WhatsAppOnboardingOtpService
{
    /** Five codes in ten minutes. Enough to mistype a number, not enough to pump. */
    public const SENDS_PER_WINDOW = 5;

    public const WINDOW_SECONDS = 600;

    public function __construct(private WhatsAppBusinessService $whatsApp) {}

    /**
     * The code is shown in the wizard and in Toshi only on a local or test
     * machine, where WhatsApp is often unconfigured. Production and staging
     * never put it in the response.
     */
    public function mayDisplayCode(): bool
    {
        return app()->environment('local', 'testing');
    }

    /**
     * Null when this attempt may proceed (and the attempt is then counted).
     * A string when the admin must wait. Skipped under the test runner so
     * existing wizard tests can resend; production tests opt in by setting
     * the environment to production.
     */
    public function consumeVerificationAttempt(int $adminUserId): ?string
    {
        if ($adminUserId < 1 || app()->environment('testing')) {
            return null;
        }

        $key = 'admin-wa-verification:'.$adminUserId;
        if (RateLimiter::tooManyAttempts($key, self::SENDS_PER_WINDOW)) {
            $seconds = max(1, RateLimiter::availableIn($key));

            return "Please wait {$seconds} seconds before requesting another code.";
        }

        RateLimiter::hit($key, self::WINDOW_SECONDS);

        return null;
    }

    public function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    public function messageBody(string $otp): string
    {
        return "Your KlassApp verification code is: {$otp}. It expires in 5 minutes.";
    }

    /**
     * Send the verification code. The demo-only recipient guard lets this
     * one message through, to this number, for this school admin. Callers
     * show the code on screen only when mayDisplayCode() is true.
     *
     * @return array{sent: bool, result: array<string, mixed>|null}
     */
    public function deliver(string $phone, string $otp, ?int $adminUserId = null): array
    {
        if (! $this->whatsApp->isConfigured() || trim($phone) === '') {
            return ['sent' => false, 'result' => null];
        }

        $adminUserId = $adminUserId !== null && $adminUserId > 0 ? $adminUserId : null;

        try {
            $send = fn () => $this->whatsApp->sendTextSafe(
                $phone,
                $this->messageBody($otp),
                null,
                [],
                DemoOnlyRecipientGuard::ADMIN_VERIFICATION,
                $adminUserId,
            );

            $result = $adminUserId === null
                ? $send()
                : DemoOnlyRecipientGuard::duringAdminVerification($adminUserId, $phone, $send);

            $ok = ($result['status'] ?? '') === 'success' || ($result['success'] ?? false) === true;

            if (! $ok) {
                Log::info('WhatsApp OTP API result (non-blocking)', [
                    'sent' => false,
                    'blocked' => (bool) ($result['blocked'] ?? false),
                    'reason' => $result['reason'] ?? null,
                ]);
            }

            return ['sent' => $ok, 'result' => $result];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp OTP API send failed (non-blocking): '.$e->getMessage());

            return ['sent' => false, 'result' => null];
        }
    }

    public function matches(?string $expected, string $provided): bool
    {
        $expected = trim((string) $expected);
        $provided = trim($provided);

        return $expected !== '' && hash_equals($expected, $provided);
    }
}
