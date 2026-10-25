<?php

namespace App\Onboarding\Steps;

/**
 * One field on a wizard onboarding step.
 */
final class StepField
{
    /**
     * @param  list<string>  $rules
     * @param  list<array{value: string, label: string}>  $options
     * @param  list<string>  $extensions  upload extensions the wizard already accepts
     * @param  list<StepField>  $children
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $type,
        public bool $required,
        public array $rules = [],
        public array $options = [],
        public mixed $default = null,
        public array $extensions = [],
        public array $children = [],
    ) {}
}
