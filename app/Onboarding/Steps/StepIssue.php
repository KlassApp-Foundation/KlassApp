<?php

namespace App\Onboarding\Steps;

/**
 * One validation or normalisation problem on an onboarding step.
 *
 * cause is the plain-language reason. fix is what the school should do.
 * row is the 0-based index in a submitted list, or null when the problem
 * is not on a single row.
 */
final class StepIssue
{
    public function __construct(
        public string $code,
        public ?string $field,
        public ?int $row,
        public string $cause,
        public string $fix,
    ) {}
}
