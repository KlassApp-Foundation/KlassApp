<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
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
}
