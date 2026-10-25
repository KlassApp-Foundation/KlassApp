<?php

namespace App\Onboarding\Steps;

/**
 * Who does the work on one onboarding step.
 *
 * The school uploads or confirms. The engine creates or saves.
 */
final class StepActor
{
    public function __construct(
        public string $school,
        public string $engine,
    ) {}
}
