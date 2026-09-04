<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Log;

/**
 * Shared onboarding WhatsApp OTP used by AgentToshi (chat) and ManualOnboardingWizard.
 * Keeps generate / deliver / compare in one place so the two UIs cannot drift.
 */
class WhatsAppOnboardingOtpService
{
    public function __construct(private WhatsAppBusinessService $whatsApp) {}

    public function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    public function messageBody(string $otp): string
    {
        return "Your KlassApp verification code is: {$otp}. It expires in 5 minutes.";
    }

    /**
     * Attempt Meta Cloud API delivery when configured. Failures are non-blocking —
     * callers still show the code in-UI (Toshi chat / wizard banner) so onboarding
     * is never stuck when WhatsApp credentials are missing.
     *
     * @return array{sent: bool, result: array<string, mixed>|null}
     */
    public function deliver(string $phone, string $otp): array
    {
        if (! $this->whatsApp->isConfigured() || trim($phone) === '') {
            return ['sent' => false, 'result' => null];
        }

        try {
            $result = $this->whatsApp->sendTextSafe($phone, $this->messageBody($otp));
            $ok = ($result['status'] ?? '') === 'success' || ($result['success'] ?? false);

            if (! $ok) {
                Log::info('WhatsApp OTP API result (non-blocking)', $result);
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
