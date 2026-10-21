<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicYear;
use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;

class AcademicYearStep extends AbstractOnboardingStep
{
    /** Chat replies that mean "use the year the prompt suggested". */
    private const CONFIRMATIONS = [
        'yes',
        'y',
        'yeah',
        'yep',
        'correct',
        'right',
        'ok',
        'okay',
        'sure',
        'confirm',
    ];

    public function key(): string
    {
        return 'academic_year';
    }

    public function question(): string
    {
        return 'What is the current academic year name?';
    }

    public function inputType(): string
    {
        return 'text';
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_array($raw)) {
            $name = trim((string) ($raw['name'] ?? ''));
            if ($this->isConfirmation($name)) {
                $name = $this->suggestedYear();
            }

            return [
                'name' => $name,
                'start' => $raw['start'] ?? null,
                'end' => $raw['end'] ?? null,
                'free_text' => false,
            ];
        }
        if (is_string($raw) && trim($raw) !== '') {
            $text = trim($raw);
            if ($this->isConfirmation($text)) {
                return ['name' => $this->suggestedYear(), 'start' => null, 'end' => null, 'free_text' => true];
            }

            return [
                'name' => $this->isYearLabel($text) ? $this->canonicalYearLabel($text) : $text,
                'start' => null,
                'end' => null,
                'free_text' => true,
            ];
        }

        return ['name' => $this->suggestedYear(), 'start' => null, 'end' => null, 'free_text' => false];
    }

    public function validate(School $school, mixed $normalized): void
    {
        $name = trim((string) (is_array($normalized) ? ($normalized['name'] ?? '') : ''));
        $fromChat = is_array($normalized) && ! empty($normalized['free_text']);

        if ($name === '' || ($fromChat && ! $this->isYearLabel($name))) {
            $this->reject($this->yearError());
        }
    }

    private function suggestedYear(): string
    {
        return (string) date('Y');
    }

    private function yearError(): string
    {
        $suggested = $this->suggestedYear();
        $following = (string) ((int) $suggested + 1);

        return "Enter an academic year such as {$suggested} or {$suggested}/{$following}, or reply yes to use {$suggested}.";
    }

    private function isConfirmation(string $text): bool
    {
        $text = strtolower(trim($text));
        $text = rtrim($text, ".! ");

        return in_array($text, self::CONFIRMATIONS, true);
    }

    private function isYearLabel(string $text): bool
    {
        $text = $this->canonicalYearLabel($text);
        if (preg_match('/^\d{4}$/', $text)) {
            return $this->plausibleYear((int) $text);
        }
        if (preg_match('/^(\d{4})[\/\-](\d{4})$/', $text, $matches)) {
            $start = (int) $matches[1];
            $end = (int) $matches[2];

            return $this->plausibleYear($start) && $this->plausibleYear($end) && $end > $start;
        }

        return false;
    }

    private function canonicalYearLabel(string $text): string
    {
        $text = trim($text);
        if (preg_match('/^(\d{4})\s*([\/\-])\s*(\d{4})$/', $text, $matches)) {
            return $matches[1].$matches[2].$matches[3];
        }

        return $text;
    }

    private function plausibleYear(int $year): bool
    {
        return $year >= 1990 && $year <= 2200;
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->saveAcademicYear(
            $school,
            (string) $normalized['name'],
            $normalized['start'] ?? null,
            $normalized['end'] ?? null
        );
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        $name = (string) $normalized['name'];
        $existing = AcademicYear::where('school_id', $school->id)->first();
        $dates = collect([$normalized['start'] ?? null, $normalized['end'] ?? null])->filter()->implode(' → ');

        if ($existing && (string) $existing->name === $name) {
            return [
                'action' => 'noop',
                'summary' => "Academic year '{$name}' is already set for this school.",
                'rows' => [['label' => $name, 'status' => 'already_present', 'detail' => $dates !== '' ? $dates : null]],
            ];
        }

        return [
            'action' => 'change',
            'summary' => $existing
                ? "Academic year will change from '{$existing->name}' to '{$name}'."
                : "Academic year '{$name}' will be created.",
            'rows' => [['label' => $name, 'status' => $existing ? 'update' : 'create', 'detail' => $dates !== '' ? $dates : null]],
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        $existing = AcademicYear::where('school_id', $school->id)->first();
        $year = $this->engine->saveAcademicYear(
            $school,
            (string) $normalized['name'],
            $normalized['start'] ?? null,
            $normalized['end'] ?? null
        );

        if ($existing) {
            return ['created' => [], 'skipped' => [['label' => (string) $year->name, 'reason' => 'already present']]];
        }

        return ['created' => [['academic_year_id' => $year->id, 'name' => (string) $year->name]], 'skipped' => []];
    }
}
