<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;

class CurriculumStep extends AbstractOnboardingStep
{
    private const OPTIONS = [
        'uneb' => 'UNEB',
        'cambridge' => 'Cambridge',
        'montessori' => 'Montessori',
        'other' => 'Other',
    ];

    public function key(): string
    {
        return 'curriculum';
    }

    public function question(): string
    {
        return 'Which board or curriculum do you follow?';
    }

    public function inputType(): string
    {
        return 'choice';
    }

    public function options(School $school): array
    {
        $out = [];
        foreach (self::OPTIONS as $value => $label) {
            $out[] = ['value' => $value, 'label' => $label];
        }

        return $out;
    }

    public function normalize(mixed $raw): mixed
    {
        $v = is_string($raw) ? strtolower(trim($raw)) : $raw;
        if (! is_string($v)) {
            return $v;
        }
        foreach (self::OPTIONS as $key => $label) {
            if ($v === $key || strcasecmp($v, $label) === 0) {
                return $key;
            }
        }

        return $v;
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_string($normalized) || ! array_key_exists($normalized, self::OPTIONS)) {
            $this->reject('Choose a curriculum option.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->saveCurriculum($school, (string) $normalized);
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $preview = $this->attributePreview($school, $normalized, 'curriculum', 'Curriculum');
        $value = (string) $preview['rows'][0]['detail'] ?? $preview['rows'][0]['label'];

        if ($preview['action'] === 'change' && $value === 'uneb' && strtolower(trim((string) ($school->uneb_center_number ?? ''))) === '') {
            $preview['summary'] .= ' This unlocks the UNEB centre number step.';
        }

        return $preview;
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        return $this->attributeSaveAndReport($school, $normalized, 'curriculum', 'Curriculum');
    }
}
