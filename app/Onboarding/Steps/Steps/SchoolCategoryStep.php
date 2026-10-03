<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Services\SchoolCategorySeeder;

class SchoolCategoryStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'school_category';
    }

    public function question(): string
    {
        return 'What kind of school is this?';
    }

    public function inputType(): string
    {
        return 'choice';
    }

    public function options(School $school): array
    {
        $out = [];
        foreach (SchoolCategorySeeder::CATEGORIES as $value => $label) {
            $out[] = ['value' => $value, 'label' => $label];
        }

        return $out;
    }

    public function normalize(mixed $raw): mixed
    {
        $v = is_string($raw) ? trim($raw) : $raw;
        if (! is_string($v)) {
            return $v;
        }
        foreach (SchoolCategorySeeder::CATEGORIES as $key => $label) {
            if ($v === $key || strcasecmp($v, $label) === 0) {
                return $key;
            }
        }

        return $v;
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_string($normalized) || ! array_key_exists($normalized, SchoolCategorySeeder::CATEGORIES)) {
            $this->reject('Choose a school category.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->saveSchoolCategory($school, (string) $normalized);
    }
}
