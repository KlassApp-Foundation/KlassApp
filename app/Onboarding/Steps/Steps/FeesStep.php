<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Standard;
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

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        $rows = [];
        foreach ($normalized as $fee) {
            $name = trim((string) ($fee['name'] ?? ''));
            $amount = (float) ($fee['amount'] ?? 0);
            $className = trim((string) ($fee['class'] ?? ''));
            $level = strtolower(trim((string) ($fee['level'] ?? '')));
            $label = $name.' ('.number_format($amount).')';
            $detail = $className !== '' ? "class {$className}" : ($level !== '' ? "level {$level}" : 'whole school');

            [$status, $rowsDetail] = $this->wouldSaveFee($school, $name, $className, $level);
            $rows[] = ['label' => $label, 'status' => $status, 'detail' => trim($detail.': '.$rowsDetail)];
        }

        $createCount = count(array_filter($rows, fn ($r) => $r['status'] === 'create'));

        return [
            'action' => $createCount > 0 ? 'change' : 'noop',
            'summary' => $createCount > 0
                ? "{$createCount} fee structure(s) will be created; existing ones keep their amounts."
                : 'All listed fees already exist — nothing will change.',
            'rows' => $rows,
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        $before = FeesCategories::where('school_id', $school->id)->count();
        $this->save($school, $normalized, $userId);
        $after = FeesCategories::where('school_id', $school->id)->count();

        if ($after === $before) {
            return ['created' => [], 'skipped' => [['label' => 'fees', 'reason' => 'already present']]];
        }

        return ['created' => [['fee_rows_created' => $after - $before]], 'skipped' => []];
    }

    /**
     * Read-only mirror of the engine's fee branches: does this fee already
     * exist everywhere it would land, and if not, how many rows are missing?
     *
     * @return array{0: string, 1: string} status + detail
     */
    private function wouldSaveFee(School $school, string $name, string $className, string $level): array
    {
        $standards = Standard::where('school_id', $school->id)->get();

        if ($className !== '') {
            $section = \App\Models\Section::where('school_id', $school->id)->where('name', $className)->first();
            if (! $section) {
                return ['skip', $className.' does not exist yet — will refuse'];
            }
            $link = \App\Models\StandardLink::where('school_id', $school->id)
                ->where('section_id', $section->id)
                ->orderByDesc('id')
                ->first();
            if (! $link) {
                return ['skip', 'not linked to an academic year yet — will refuse'];
            }
            $exists = $this->feeKeyExists(
                $school,
                Standard::find($link->standard_id),
                $section->id,
                $name
            );

            return $exists
                ? ['already_present', 'row exists']
                : ['create', 'row would be created'];
        }

        if ($level !== '' && $level !== 'all') {
            $tiers = match ($level) {
                'o-level', 'o_level', 'olevel' => ['o-level'],
                'a-level', 'a_level', 'alevel' => ['a-level'],
                'secondary' => ['o-level', 'a-level'],
                default => [$level],
            };
            $targets = $standards->whereIn('name', $tiers);
            if ($targets->isEmpty()) {
                return ['skip', 'no such grading tier yet — will refuse'];
            }
            $missing = $targets->filter(fn (Standard $s) => ! $this->feeKeyExists($school, $s, null, $name))->count();

            return $missing === 0
                ? ['already_present', 'rows exist']
                : ['create', "{$missing} row(s) would be created"];
        }

        // Whole school: one row per grading-tier Standard.
        $missing = $standards->filter(fn (Standard $s) => ! $this->feeKeyExists($school, $s, null, $name))->count();

        return $missing === 0
            ? ['already_present', 'rows exist for every class']
            : ($standards->isEmpty()
                ? ['skip', 'no classes yet — will refuse']
                : ['create', "{$missing} row(s) would be created"]);
    }

    private function feeKeyExists(School $school, Standard $standard, mixed $sectionId, string $name): bool
    {
        $query = FeesCategories::where('school_id', $school->id)
            ->where('standard_id', $standard->id)
            ->where('name', $name);

        $sectionId === null
            ? $query->whereNull('section_id')
            : $query->where('section_id', $sectionId);

        return $query->exists();
    }
}
