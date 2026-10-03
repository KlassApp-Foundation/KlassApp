<?php

namespace App\Onboarding\Steps\Contracts;

use App\Models\School;
use InvalidArgumentException;

interface OnboardingStep
{
    public function key(): string;

    public function applies(School $school): bool;

    public function question(): string;

    /** @return string text|choice|list|phone_otp|plan|structure|skipable_list */
    public function inputType(): string;

    /**
     * @return list<array{value: string, label: string}>
     */
    public function options(School $school): array;

    public function normalize(mixed $raw): mixed;

    /**
     * @throws InvalidArgumentException
     */
    public function validate(School $school, mixed $normalized): void;

    public function save(School $school, mixed $normalized, ?int $userId = null): void;

    public function isComplete(School $school, ?int $userId = null): bool;

    public function required(): bool;
}
