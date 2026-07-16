<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Standard;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\DB;

class SetGradingScaleTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Set or update a grading scale for a class/standard. Provide the standard name and grade definitions array (each with grade, min, max).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'standardName' => $schema->string()->description('Standard/class name, e.g. "Primary Four"'),
            'gradeDefinitions' => $schema->array()
                ->items($schema->object([
                    'grade' => $schema->string()->description('Grade letter, e.g. "A", "B", "C"'),
                    'min' => $schema->number()->description('Minimum percentage for this grade'),
                    'max' => $schema->number()->description('Maximum percentage for this grade'),
                ]))
                ->description('Array of grade definitions'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        // LLMs sometimes serialize the grade definitions array as a JSON string.
        // Normalize it so downstream code always gets an array.
        $rawDefinitions = $request->get('gradeDefinitions', []);
        $definitions = $this->normalizeGradeDefinitions($rawDefinitions);

        // ── Pre-delete guard: validate grade definitions ──
        $validationError = $this->validateGradeDefinitions($definitions);
        if ($validationError !== null) {
            return $validationError;
        }

        $gradeCount = count($definitions);
        $previewSummary = $this->formatGradeSummary($definitions);

        $args = [
            'standardName' => $request->get('standardName'),
            'gradeDefinitions' => $definitions,
        ];

        $confirm = $this->confirmOrExecute('toolSetGradingScale', $args,
            fn() => "Set grading scale for **{$args['standardName']}**: {$previewSummary}");
        if ($confirm !== null) return $confirm;

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);

        $standardName = $args['standardName'];
        $grades = $args['gradeDefinitions'];

        $standard = Standard::where('school_id', $schoolId)->where('name', $standardName)->first();
        if (!$standard) {
            return '❌ Standard "' . $standardName . '" not found.';
        }

        // Replace grading scale in a transaction: if the insert fails partway
        // through (crash, validation error), the delete is rolled back so the
        // school doesn't end up with zero grade levels where 5 existed before.
        DB::transaction(function () use ($schoolId, $standard, $grades) {
            SchoolGradingSystem::where('school_id', $schoolId)
                ->where('standard_id', $standard->id)
                ->delete();

            foreach ($grades as $g) {
                SchoolGradingSystem::create([
                    'school_id'    => $schoolId,
                    'standard_id'  => $standard->id,
                    'grade'        => $g['grade'],
                    'min_score'    => $g['min'],
                    'max_score'    => $g['max'],
                ]);
            }
        });

        return '✅ Grading scale for "' . $standardName . '" has been updated with ' . count($grades) . ' grade levels.';
    }

    /**
     * Normalize grade definitions that may arrive as a JSON string from the LLM.
     * Returns a clean array of {grade, min, max} objects.
     */
    private function normalizeGradeDefinitions(mixed $definitions): array
    {
        if (is_array($definitions)) {
            // Already an array — check if elements are objects/arrays with grade/min/max
            $cleaned = [];
            foreach ($definitions as $g) {
                if (is_array($g) && isset($g['grade'])) {
                    $cleaned[] = [
                        'grade' => $g['grade'],
                        'min'   => (int) ($g['min'] ?? $g['min_score'] ?? 0),
                        'max'   => (int) ($g['max'] ?? $g['max_score'] ?? 100),
                    ];
                }
            }
            return $cleaned;
        }

        if (is_string($definitions)) {
            $decoded = json_decode($definitions, true);
            if (is_array($decoded)) {
                return $this->normalizeGradeDefinitions($decoded);
            }
        }

        return [];
    }

    /**
     * Validate that grade definitions are sane before deleting existing data.
     * Returns an error message string if validation fails, or null if OK.
     */
    private function validateGradeDefinitions(array $definitions): ?string
    {
        if (empty($definitions)) {
            return '❌ I couldn\'t understand the grade definitions. Please specify them explicitly — for example: "A: 80-100, B: 70-79, C: 60-69, D: 50-59, E: 40-49, F: 0-39".';
        }

        if (count($definitions) < 2) {
            return '❌ A grading scale needs at least 2 grade levels (e.g. "A: 80-100, B: 0-79"). Please provide more levels.';
        }

        // Each entry must have grade, min, max with min < max
        foreach ($definitions as $i => $g) {
            $label = $g['grade'] ?? "level #" . ($i + 1);
            if (!isset($g['grade']) || $g['grade'] === '') {
                return "❌ Grade level #" . ($i + 1) . " is missing a grade label. Each level needs a name (e.g. A, B, C).";
            }
            if (!isset($g['min']) || !isset($g['max'])) {
                return "❌ Grade {$g['grade']} is missing boundaries. Each level needs a min and max percentage.";
            }
            if ($g['min'] >= $g['max']) {
                return "❌ Grade {$g['grade']}: min ({$g['min']}%) must be less than max ({$g['max']}%).";
            }
            if ($g['min'] < 0 || $g['max'] > 100) {
                return "❌ Grade {$g['grade']}: percentages must be between 0 and 100.";
            }
        }

        // Check for overlapping boundaries (sorted by min descending)
        $sorted = $definitions;
        usort($sorted, fn($a, $b) => $b['min'] <=> $a['min']);
        for ($i = 0; $i < count($sorted) - 1; $i++) {
            if ($sorted[$i]['min'] < $sorted[$i + 1]['max']) {
                $a = $sorted[$i]['grade'];
                $b = $sorted[$i + 1]['grade'];
                return "❌ Grade boundaries overlap: {$a} ({$sorted[$i]['min']}-{$sorted[$i]['max']}%) and {$b} ({$sorted[$i + 1]['min']}-{$sorted[$i + 1]['max']}%). Each range must be distinct.";
            }
        }

        return null;
    }

    /**
     * Format grade definitions as a compact human-readable summary for the
     * confirmation card preview (e.g. "A: 80–100%, B: 70–79%, C: 60–69%").
     */
    private function formatGradeSummary(array $definitions): string
    {
        $parts = [];
        foreach ($definitions as $g) {
            $parts[] = $g['grade'] . ': ' . $g['min'] . '–' . $g['max'] . '%';
        }
        return implode(', ', $parts);
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $standardName = trim($request->get('standardName', ''));
        if ($standardName === '') {
            return ['verified' => false, 'message' => 'Standard name is required for verification.'];
        }

        $standard = Standard::where('school_id', $schoolId)
            ->where('name', $standardName)
            ->first();

        if (!$standard) {
            return ['verified' => false, 'message' => 'Standard not found during verification.'];
        }

        $gradeCount = SchoolGradingSystem::where('school_id', $schoolId)
            ->where('standard_id', $standard->id)
            ->count();

        return [
            'verified' => $gradeCount > 0,
            'message' => $gradeCount > 0
                ? "Grading scale confirmed in database ({$gradeCount} grade level(s))."
                : 'Grading scale was not found after update.',
        ];
    }
}
