<?php

namespace App\Onboarding\Steps;

use Illuminate\Validation\ValidationException;

/**
 * A save-time validation failure, carrying the same error bag as the engine
 * exception plus the StepIssue a face can show.
 */
class OnboardingStepValidationException extends ValidationException
{
    public function __construct(public readonly StepIssue $issue, ValidationException $previous)
    {
        parent::__construct($previous->validator, $previous->response, $previous->errorBag);
        $this->status = $previous->status;
        $this->redirectTo = $previous->redirectTo;
        $this->errorBag = $previous->errorBag;
    }

    public static function wrap(string $key, ValidationException $previous): self
    {
        return new self(StepIssueCatalog::fromValidation($key, $previous), $previous);
    }
}
