<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Standard;
use App\Models\User;
use App\Services\ToshiActionService;

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

        $args = [
            'standardName' => $request->get('standardName'),
            'gradeDefinitions' => $request->get('gradeDefinitions', []),
        ];

        $gradeCount = is_array($args['gradeDefinitions']) ? count($args['gradeDefinitions']) : 0;
        $confirm = $this->confirmOrExecute('toolSetGradingScale', $args,
            fn() => "Set grading scale for {$args['standardName']} with {$gradeCount} grade level(s)");
        if ($confirm !== null) return $confirm;

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);

        $standardName = $args['standardName'];
        $grades = $args['gradeDefinitions'];

        $standard = Standard::where('school_id', $schoolId)->where('name', $standardName)->first();
        if (!$standard) {
            return '❌ Standard "' . $standardName . '" not found.';
        }

        $standard->grade_scale = json_encode($grades);
        $standard->save();

        return '✅ Grading scale for "' . $standardName . '" has been updated with ' . count($grades) . ' grade levels.';
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

        return [
            'verified' => $standard->grade_scale !== null,
            'message' => $standard->grade_scale !== null
                ? 'Grading scale confirmed in database.'
                : 'Grading scale was not found after update.',
        ];
    }
}
