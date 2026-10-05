<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Onboarding\Steps\AbstractOnboardingStep;

class WhatsAppVerifyStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'whatsapp_verify';
    }

    public function question(): string
    {
        return 'Verify your WhatsApp number.';
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
        $this->validate($school, $normalized);
        if (! $userId) {
            $this->reject('A logged-in user is required to link WhatsApp.');
        }
        $this->engine->saveWhatsApp($school, $userId, (string) $normalized);
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
            'summary' => "WhatsApp number '{$normalized}' will be linked to this account.",
            'rows' => [['label' => (string) $normalized, 'status' => 'create', 'detail' => null]],
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $this->validate($school, $normalized);
        if (! $userId) {
            $this->reject('A logged-in user is required to link WhatsApp.');
        }

        $result = $this->engine->saveWhatsApp($school, $userId, (string) $normalized);

        if (($result['skipped'] ?? null) !== null) {
            // Per-contract idempotent rerun: linking again the same (or any)
            // number reports the existing link as skipped.
            return [
                'created' => [],
                'skipped' => [['label' => (string) $normalized, 'reason' => 'already present']],
            ];
        }

        return ['created' => [$result['linked'] ?? ['phone' => (string) $normalized]], 'skipped' => []];
    }
}
