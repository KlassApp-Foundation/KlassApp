<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;

class FeesStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'fees';
    }

    public function question(): string
    {
        return 'Add fee structures.';
    }

    public function inputType(): string
    {
        return 'list';
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw)) {
            return [];
        }

        $trim = trim($raw);
        if ($trim === '') {
            return [];
        }

        if (in_array(strtolower($trim), ['defaults', 'default', 'yes', 'y'], true)) {
            return [['name' => 'Development Fund', 'amount' => 25000]];
        }

        $fees = [];
        foreach (preg_split('/\r\n|\r|\n/', $trim) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_contains($line, '|')) {
                [$name, $amount] = array_pad(explode('|', $line, 2), 2, '0');
                $fees[] = ['name' => trim($name), 'amount' => (float) trim($amount)];

                continue;
            }
            if (preg_match('/^(.+?)\s+(\d[\d,]*\.?\d*)$/', $line, $m)) {
                $fees[] = [
                    'name' => trim($m[1]),
                    'amount' => (float) str_replace(',', '', $m[2]),
                ];

                continue;
            }
            $fees[] = ['name' => $line, 'amount' => 0];
        }

        return $fees;
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_array($normalized) || $normalized === []) {
            $this->reject('Add at least one fee structure.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->saveFees($school, $normalized);
    }
}
