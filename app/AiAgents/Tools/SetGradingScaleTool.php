<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\Models\Standard;

class SetGradingScaleTool implements Tool
{
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
        $standardName = $request->get('standardName');
        $grades = $request->get('gradeDefinitions', []);

        $standard = Standard::where('name', $standardName)->first();
        if (!$standard) {
            return '❌ Standard "' . $standardName . '" not found.';
        }

        $standard->grade_scale = json_encode($grades);
        $standard->save();

        return '✅ Grading scale for "' . $standardName . '" has been updated with ' . count($grades) . ' grade levels.';
    }
}
