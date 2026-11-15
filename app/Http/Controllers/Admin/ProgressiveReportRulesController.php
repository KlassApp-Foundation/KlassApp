<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\GradingHelper;
use App\Http\Controllers\Controller;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\ProgressiveRulesChange;
use App\Models\ReportDivisionRange;
use App\Models\School;
use App\Models\Standard;
use App\Services\Reports\GradingPresets;
use App\Services\Reports\GradingRulesKit;
use App\Services\Reports\ProgressiveRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Grade bands, division ranges and ranking for the progressive report.
 * Admins and head teachers share this screen. An empty choice keeps today's rule.
 */
class ProgressiveReportRulesController extends Controller
{
    public function edit(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $school = School::query()->findOrFail($schoolId);
        $standards = Standard::query()
            ->where('school_id', $schoolId)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $standardId = (int) $request->query('standard');
        $standard = $standardId > 0
            ? $standards->firstWhere('id', $standardId)
            : null;
        if ($standardId > 0 && $standard === null) {
            abort(404);
        }

        $ranking = $standard?->ranking_basis ?: ($standard === null ? $school->ranking_basis : null);
        $ranges = $this->storedRanges($schoolId, $standard?->id);
        $usingAutomaticDivision = $ranges === [];
        if ($usingAutomaticDivision) {
            $ranges = ProgressiveRules::defaultDivisionRanges();
        }

        $bands = $standard === null
            ? []
            : $this->bandsFor($school, $standard);

        // A preset only fills the form. Nothing is saved until Save is pressed.
        $preset = (string) $request->query('preset', '');
        $presetLoaded = null;
        if ($preset !== '' && GradingPresets::isPreset($preset) && $preset !== GradingPresets::CUSTOM) {
            $presetLoaded = GradingPresets::labels()[$preset];
            if ($standard !== null) {
                $bands = GradingPresets::bands($preset, $schoolId, $standard) ?? $bands;
            }
            $ranges = GradingPresets::divisionRanges($preset) ?? $ranges;
            $usingAutomaticDivision = $preset === GradingPresets::DEFAULT;
            $ranking = GradingPresets::ranking($preset) ?? $ranking;
        }

        // "Try a mark" and "Try an aggregate" use what is on screen, so they work without a script.
        $tryMark = $this->nullableWhole($request->query('try_mark'));
        $tryBand = $tryMark === null ? null : GradingRulesKit::bandForMark($bands, $tryMark);
        $tryAggregate = $this->nullableWhole($request->query('try_aggregate'));
        $tryDivision = $tryAggregate === null ? null : GradingRulesKit::divisionForAggregate($ranges, $tryAggregate);

        $lastChange = ProgressiveRulesChange::query()
            ->with('user:id,name')
            ->where('school_id', $schoolId)
            ->where('standard_id', $standard?->id)
            ->latest('id')
            ->first();

        return view('admin.reports.progressive-rules', [
            'presets' => GradingPresets::labels(),
            'preset' => $presetLoaded === null ? null : $preset,
            'presetLoaded' => $presetLoaded,
            'tryMark' => $tryMark,
            'tryBand' => $tryBand,
            'tryAggregate' => $tryAggregate,
            'tryDivision' => $tryDivision,
            'lastChange' => $lastChange,
            'standards' => $standards,
            'standard' => $standard,
            'ranking' => $ranking ?: 'automatic',
            'ranges' => $ranges,
            'usingAutomaticDivision' => $usingAutomaticDivision,
            'bands' => $bands,
            'bandsAreStored' => $standard !== null && SchoolGradingSystem::query()
                ->where('school_id', $schoolId)
                ->where('standard_id', $standard->id)
                ->exists(),
        ]);
    }

    /** Put the scope back to the app defaults: default bands, automatic ranges, automatic ranking. */
    public function reset(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $school = School::query()->findOrFail($schoolId);

        $data = $request->validate([
            'standard_id' => ['nullable', 'integer'],
            'confirm' => ['accepted'],
        ]);

        $standard = null;
        $standardId = (int) ($data['standard_id'] ?? 0);
        if ($standardId > 0) {
            $standard = Standard::query()->where('school_id', $schoolId)->whereKey($standardId)->first();
            if ($standard === null) {
                abort(404);
            }
        }

        if ($standard === null) {
            $school->ranking_basis = null;
            $school->save();
        } else {
            $standard->ranking_basis = null;
            $standard->save();
            $this->saveBands($schoolId, (int) $standard->id, GradingPresets::appDefaultBands($schoolId, $standard));
        }

        ReportDivisionRange::query()
            ->where('school_id', $schoolId)
            ->when(
                $standard === null,
                fn ($query) => $query->whereNull('standard_id'),
                fn ($query) => $query->where('standard_id', $standard->id),
            )
            ->delete();

        $this->recordChange($schoolId, $standard?->id, 'reset', 'Back to the app defaults');

        return redirect()
            ->route('admin.reports.cards.progressive-rules', array_filter(['standard' => $standard?->id]))
            ->with('successmessage', 'Back to the app defaults. This applies from the next release; reports already released keep the rules they were released with.');
    }

    private function recordChange(int $schoolId, ?int $standardId, string $action, ?string $summary): void
    {
        ProgressiveRulesChange::query()->create([
            'school_id' => $schoolId,
            'standard_id' => $standardId,
            'user_id' => Auth::id(),
            'action' => $action,
            'summary' => $summary === null ? null : mb_substr($summary, 0, 255),
        ]);
    }

    private function nullableWhole(mixed $value): ?int
    {
        return ($value === null || $value === '' || filter_var($value, FILTER_VALIDATE_INT) === false) ? null : (int) $value;
    }

    public function update(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $school = School::query()->findOrFail($schoolId);

        $data = $request->validate([
            'standard_id' => ['nullable', 'integer'],
            'ranking_basis' => ['required', 'in:automatic,aggregate,total'],
            'use_automatic_division' => ['nullable', 'boolean'],
            'ranges' => ['nullable', 'array'],
            'ranges.*.label' => ['nullable', 'string', 'max:8'],
            'ranges.*.min' => ['nullable', 'integer'],
            'ranges.*.max' => ['nullable', 'integer'],
            'bands' => ['nullable', 'array'],
            'bands.*.grade' => ['nullable', 'string', 'max:20'],
            'bands.*.points' => ['nullable', 'integer', 'min:0', 'max:99'],
            'bands.*.min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bands.*.max_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bands.*.remark' => ['nullable', 'string', 'max:80'],
        ]);

        $standard = null;
        $standardId = (int) ($data['standard_id'] ?? 0);
        if ($standardId > 0) {
            $standard = Standard::query()->where('school_id', $schoolId)->whereKey($standardId)->first();
            if ($standard === null) {
                abort(404);
            }
        }

        $basis = $data['ranking_basis'] === 'automatic' ? null : $data['ranking_basis'];
        if ($standard === null) {
            $school->ranking_basis = $basis;
            $school->save();
        } else {
            $standard->ranking_basis = $basis;
            $standard->save();
        }

        $scope = ReportDivisionRange::query()
            ->where('school_id', $schoolId)
            ->when(
                $standard === null,
                fn ($query) => $query->whereNull('standard_id'),
                fn ($query) => $query->where('standard_id', $standard->id),
            );
        $scope->delete();

        if (! $request->boolean('use_automatic_division')) {
            $ranges = $this->cleanRanges((array) ($data['ranges'] ?? []));
            foreach ($ranges as $position => $range) {
                ReportDivisionRange::query()->create([
                    'school_id' => $schoolId,
                    'standard_id' => $standard?->id,
                    'label' => $range['label'],
                    'min_aggregate' => $range['min'],
                    'max_aggregate' => $range['max'],
                    'position' => $position,
                ]);
            }
        }

        if ($standard !== null && $request->exists('bands')) {
            $this->saveBands($schoolId, (int) $standard->id, (array) $request->input('bands', []));
        }

        $presetName = (string) $request->input('preset', '');
        $this->recordChange(
            $schoolId,
            $standard?->id,
            'saved',
            GradingPresets::isPreset($presetName) && $presetName !== GradingPresets::CUSTOM
                ? 'Saved, started from the preset: '.GradingPresets::labels()[$presetName]
                : 'Saved',
        );

        return redirect()
            ->route('admin.reports.cards.progressive-rules', array_filter(['standard' => $standard?->id]))
            ->with('successmessage', 'Progressive report rules saved. This applies from the next release; reports already released keep the rules they were released with.');
    }

    /**
     * @return list<array{label: string, min: ?int, max: ?int}>
     */
    private function storedRanges(int $schoolId, ?int $standardId): array
    {
        return ReportDivisionRange::query()
            ->where('school_id', $schoolId)
            ->when(
                $standardId === null,
                fn ($query) => $query->whereNull('standard_id'),
                fn ($query) => $query->where('standard_id', $standardId),
            )
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (ReportDivisionRange $row) => [
                'label' => (string) $row->label,
                'min' => $row->min_aggregate === null ? null : (int) $row->min_aggregate,
                'max' => $row->max_aggregate === null ? null : (int) $row->max_aggregate,
            ])
            ->all();
    }

    /**
     * @return list<array{grade: string, points: ?int, min_score: int, max_score: int, remark: string}>
     */
    private function bandsFor(School $school, Standard $standard): array
    {
        $stored = SchoolGradingSystem::query()
            ->where('school_id', $school->id)
            ->where('standard_id', $standard->id)
            ->orderByDesc('min_score')
            ->get();

        if ($stored->isNotEmpty()) {
            return $stored->map(fn (SchoolGradingSystem $row) => [
                'grade' => (string) $row->grade,
                'points' => $row->points === null ? null : (int) $row->points,
                'min_score' => (int) $row->min_score,
                'max_score' => (int) $row->max_score,
                'remark' => (string) ($row->remark ?? ''),
            ])->all();
        }

        $level = GradingHelper::levelTypeForStandard($standard) ?? 'primary';
        $scale = GradingHelper::scaleConfigForSchool((int) $school->id, $level);

        return array_map(fn (array $band) => [
            'grade' => (string) ($band['grade'] ?? ''),
            'points' => isset($band['points']) && $band['points'] !== null ? (int) $band['points'] : null,
            'min_score' => (int) ($band['min_score'] ?? 0),
            'max_score' => (int) ($band['max_score'] ?? 0),
            'remark' => (string) ($band['remark'] ?? ''),
        ], $scale);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{label: string, min: ?int, max: ?int}>
     */
    private function cleanRanges(array $rows): array
    {
        $clean = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $clean[] = [
                'label' => $label,
                'min' => $this->nullableInt($row['min'] ?? null),
                'max' => $this->nullableInt($row['max'] ?? null),
            ];
        }

        $problems = GradingRulesKit::rangeProblems($clean);
        if ($problems !== []) {
            throw ValidationException::withMessages(['ranges' => $problems[0]]);
        }

        return $clean;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     */
    private function saveBands(int $schoolId, int $standardId, array $rows): void
    {
        $clean = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $grade = trim((string) ($row['grade'] ?? ''));
            if ($grade === '') {
                continue;
            }
            $min = filter_var($row['min_score'] ?? null, FILTER_VALIDATE_INT);
            $max = filter_var($row['max_score'] ?? null, FILTER_VALIDATE_INT);
            if ($min === false || $max === false || $min < 0 || $max > 100 || $min > $max) {
                throw ValidationException::withMessages([
                    'bands' => "The {$grade} band needs two marks from 0 to 100, lowest first.",
                ]);
            }
            $points = trim((string) ($row['points'] ?? ''));
            $clean[] = [
                'grade' => $grade,
                'points' => $points === '' ? null : (int) $points,
                'min_score' => (int) $min,
                'max_score' => (int) $max,
                'remark' => trim((string) ($row['remark'] ?? '')),
            ];
        }

        usort($clean, fn (array $a, array $b) => $b['max_score'] <=> $a['max_score']);
        $problems = GradingRulesKit::bandProblems($clean);
        if ($problems !== []) {
            throw ValidationException::withMessages(['bands' => $problems[0]]);
        }

        SchoolGradingSystem::query()
            ->where('school_id', $schoolId)
            ->where('standard_id', $standardId)
            ->delete();

        foreach ($clean as $band) {
            SchoolGradingSystem::query()->create([
                'school_id' => $schoolId,
                'standard_id' => $standardId,
                'grade' => $band['grade'],
                'points' => $band['points'],
                'min_score' => $band['min_score'],
                'max_score' => $band['max_score'],
                'remark' => $band['remark'],
            ]);
        }

        \Illuminate\Support\Facades\Cache::forget("school_grading_{$schoolId}");
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw ValidationException::withMessages([
                'ranges' => 'Aggregate limits must be whole numbers.',
            ]);
        }

        return (int) $value;
    }
}
