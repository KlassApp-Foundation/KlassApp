<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Onboarding\Steps\OnboardingStepAwaitingInput;
use App\Services\OnboardingStepsService;
use App\Services\WhatsApp\WhatsAppOnboardingOtpService;
use Illuminate\Validation\ValidationException;

class WhatsAppVerifyStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'whatsapp_verify';
    }

    public function question(): string
    {
        return "What's your WhatsApp number? We'll send a verification code and link the number only after you enter it.";
    }

    public function inputType(): string
    {
        return 'phone_otp';
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_string($normalized) || trim($normalized) === '') {
            $this->reject('Enter a WhatsApp phone number.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        if (! $userId) {
            $this->reject('A logged-in user is required to link WhatsApp.');
        }

        if (! is_string($normalized) && ! is_numeric($normalized)) {
            $this->reject('Enter a WhatsApp phone number.');
        }

        $raw = trim((string) $normalized);
        if ($raw === '') {
            $this->await('Enter a WhatsApp phone number.');
        }

        $existing = WhatsAppUser::where('user_id', $userId)->first();
        if ($existing && $existing->verified_at !== null) {
            return;
        }

        $otp = app(WhatsAppOnboardingOtpService::class);

        if ($this->isResend($raw)) {
            $phone = $otp->pendingPhone();
            if (! is_string($phone) || $phone === '') {
                $this->await('Enter your WhatsApp number first. We will send a verification code.');
            }
            $this->sendCode($userId, $phone, $otp);
        }

        if ($this->isSkip($raw)) {
            if (in_array($this->key(), OnboardingStepsService::OPTIONAL_STEPS, true)) {
                OnboardingStepsService::markStepSkipped($school, $this->key());

                return;
            }

            $this->await('Enter your WhatsApp number. We send a verification code and link it only after you enter that code.');
        }

        if ($this->looksLikeCode($raw)) {
            $result = $otp->verifyAdminCode($userId, $raw);
            if (! $result['verified']) {
                $this->await($result['message'].' Reply **resend** for a new code, or **retry** if it did not arrive.');
            }

            $this->engine->saveWhatsApp($school, $userId, $result['phone'], codeWasVerified: true);

            return;
        }

        $phone = $this->canonicalPhone($school, $raw);
        if ($this->phoneTaken($phone, $userId)) {
            $this->await("WhatsApp number {$phone} is already registered to another account. Use a different number.");
        }

        $this->sendCode($userId, $phone, $otp);
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if (! $userId) {
            return [
                'action' => 'skip',
                'summary' => 'Needs a signed-in user to link WhatsApp.',
                'rows' => [['label' => (string) $normalized, 'status' => 'skip', 'detail' => 'no signed-in user']],
            ];
        }

        $userHas = WhatsAppUser::where('user_id', $userId)->first();
        if ($userHas && $userHas->verified_at === null) {
            return [
                'action' => 'change',
                'summary' => 'This number is saved and still needs the verification code.',
                'rows' => [[
                    'label' => (string) $userHas->phone,
                    'status' => 'create',
                    'detail' => 'not verified yet',
                ]],
            ];
        }
        if ($userHas) {
            $same = (string) $userHas->phone === (string) $normalized;

            return [
                'action' => 'noop',
                'summary' => $same
                    ? "WhatsApp number '{$normalized}' is already linked to this account."
                    : 'This account already has a WhatsApp number — it stays unchanged.',
                'rows' => [[
                    'label' => (string) $normalized,
                    'status' => $same ? 'already_present' : 'skip',
                    'detail' => $same ? null : "current link: {$userHas->phone}",
                ]],
            ];
        }

        $taken = WhatsAppUser::where('phone', $normalized)->exists()
            || User::where('mobile_no', $normalized)->where('id', '!=', $userId)->exists();

        if ($taken) {
            return [
                'action' => 'skip',
                'summary' => "WhatsApp number '{$normalized}' is already registered to another account.",
                'rows' => [['label' => (string) $normalized, 'status' => 'skip', 'detail' => 'registered elsewhere — will refuse']],
            ];
        }

        return [
            'action' => 'change',
            'summary' => "A verification code will be sent to '{$normalized}'. The number is linked only after that code matches.",
            'rows' => [['label' => (string) $normalized, 'status' => 'create', 'detail' => 'verification code required']],
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        if (! $userId) {
            $this->reject('A logged-in user is required to link WhatsApp.');
        }

        $existing = WhatsAppUser::where('user_id', $userId)->first();
        if ($existing && $existing->verified_at !== null) {
            return [
                'created' => [],
                'skipped' => [['label' => (string) $existing->phone, 'reason' => 'already present']],
            ];
        }

        $this->save($school, $normalized, $userId);

        $linked = WhatsAppUser::where('user_id', $userId)->first();

        return [
            'created' => $linked ? [['phone' => (string) $linked->phone]] : [],
            'skipped' => [],
        ];
    }

    private function sendCode(int $userId, string $phone, WhatsAppOnboardingOtpService $otp): never
    {
        $result = $otp->sendAdminVerificationCode($userId, $phone);
        $offer = $result['failed']
            ? 'Reply **retry** to try again, or **resend** for a new code.'
            : 'Reply **resend** for a new code.';

        $this->await($result['message'].' '.$offer);
    }

    private function canonicalPhone(School $school, string $raw): string
    {
        $phone = '';
        try {
            $phone = $this->engine->normaliseOnboardingPhoneForWhatsApp($school, $raw);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();
            $this->await(is_string($message) && $message !== '' ? $message : 'Enter a WhatsApp phone number.');
        }

        if ($phone === '') {
            $this->await('Enter a WhatsApp phone number.');
        }

        return $phone;
    }

    private function phoneTaken(string $phone, int $userId): bool
    {
        return WhatsAppUser::where('phone', $phone)->where('user_id', '!=', $userId)->exists();
    }

    private function looksLikeCode(string $raw): bool
    {
        return (bool) preg_match('/^\d{6}$/', $raw);
    }

    private function isResend(string $raw): bool
    {
        return in_array(strtolower($raw), ['resend', 'retry'], true);
    }

    private function isSkip(string $raw): bool
    {
        return in_array(strtolower($raw), ['skip', 'later', 'no', 'none'], true);
    }

    private function await(string $message): never
    {
        throw new OnboardingStepAwaitingInput(trim($message));
    }
}
