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

    /**
     * Send (or resend) the code for this admin's own number.
     *
     * The code stays in the session the wizard already uses. The returned
     * message never contains it. `code` is set only when mayDisplayCode()
     * is true, so the wizard can keep its local/testing hint; callers that
     * render a chat transcript must ignore that field.
     *
     * @return array{sent: bool, failed: bool, reason: string, phone: string, message: string, code: ?string}
     */
    public function sendAdminVerificationCode(int $adminUserId, string $phone): array
    {
        $phone = trim($phone);

        if ($wait = $this->consumeVerificationAttempt($adminUserId)) {
            return [
                'sent' => false,
                'failed' => true,
                'reason' => 'rate_limited',
                'phone' => $phone,
                'message' => $wait,
                'code' => null,
            ];
        }

        $otp = $this->generateCode();
        session([
            'wizard_wa_otp.code' => $otp,
            'wizard_wa_otp.phone' => $phone,
            'wizard_wa_otp.user_id' => $adminUserId,
            'wizard_wa_otp.expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        $delivery = $this->deliver($phone, $otp, $adminUserId > 0 ? $adminUserId : null);
        $sent = (bool) ($delivery['sent'] ?? false);

        return [
            'sent' => $sent,
            'failed' => ! $sent,
            'reason' => $sent ? 'sent' : 'failed',
            'phone' => $phone,
            'message' => $sent
                ? 'We sent a verification code to your WhatsApp. Enter it below.'
                : "We couldn't send the verification code. Please try again.",
            'code' => $this->mayDisplayCode() ? $otp : null,
        ];
    }

    /**
     * Check the code the admin typed against the one sendAdminVerificationCode stored.
     * Does not write whatsapp_users. On a match, the stored code is forgotten.
     *
     * @return array{verified: bool, phone: string, message: string}
     */
    public function verifyAdminCode(int $adminUserId, string $provided, string $currentPhone = ''): array
    {
        $expected = (string) session('wizard_wa_otp.code', '');
        $sessionPhone = (string) session('wizard_wa_otp.phone', '');
        $ownerId = (int) session('wizard_wa_otp.user_id', 0);
        $expiresAt = (int) session('wizard_wa_otp.expires_at', 0);

        if ($expected === '' || $sessionPhone === '' || ($adminUserId > 0 && $ownerId > 0 && $ownerId !== $adminUserId)) {
            return [
                'verified' => false,
                'phone' => $sessionPhone,
                'message' => 'Send a verification code first.',
            ];
        }

        if ($expiresAt > 0 && now()->timestamp > $expiresAt) {
            return [
                'verified' => false,
                'phone' => $sessionPhone,
                'message' => 'That code has expired. Send a new one.',
            ];
        }

        if (trim($currentPhone) !== '' && trim($currentPhone) !== $sessionPhone) {
            return [
                'verified' => false,
                'phone' => $sessionPhone,
                'message' => 'Phone number changed after the code was sent. Send a new code.',
            ];
        }

        if (! $this->matches($expected, $provided)) {
            return [
                'verified' => false,
                'phone' => $sessionPhone,
                'message' => "That code doesn't match. Try again or send a new code.",
            ];
        }

        session()->forget(['wizard_wa_otp.code', 'wizard_wa_otp.phone', 'wizard_wa_otp.user_id', 'wizard_wa_otp.expires_at']);

        return [
            'verified' => true,
            'phone' => $sessionPhone,
            'message' => "WhatsApp verified for {$sessionPhone}.",
        ];
    }

    /**
     * The code currently waiting to be typed. Server-side only — never render it.
     */
    public function pendingCode(): ?string
    {
        $code = trim((string) session('wizard_wa_otp.code', ''));

        return $code !== '' ? $code : null;
    }

    public function pendingPhone(): ?string
    {
        $phone = trim((string) session('wizard_wa_otp.phone', ''));

        return $phone !== '' ? $phone : null;
    }
}
