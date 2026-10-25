<?php

namespace App\Onboarding\Steps;

use RuntimeException;

/**
 * The step accepted the reply but still needs another answer before it saves.
 * The message is safe to show: it must not contain a verification code.
 */
class OnboardingStepAwaitingInput extends RuntimeException
{
    public function __construct(string $message = '', public readonly ?StepIssue $issue = null)
    {
        parent::__construct($message);
    }
}
