<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Models\Standard;
use App\Models\User;
use App\Services\ToshiActionService;

class ViewGradingScaleTool implements Tool
{
    use AuthorizesToshiAction;
    public function description(): string
    {
        return 'View the current grading scale for a class/standard. Provide the standard name.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'standardName' => $schema->string()->description('Standard/class name, e.g. "Primary Four"'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);

        $standardName = $request->get('standardName');
        $standard = Standard::where('school_id', $schoolId)->where('name', $standardName)->first();

        if (!$standard) {
            return 'Standard "' . $standardName . '" not found.';
        }

        $scale = $standard->grade_scale ? json_decode($standard->grade_scale, true) : null;
        if (!$scale) {
            return 'No grading scale configured for "' . $standardName . '".';
        }

        $lines = array_map(function ($g) {
            return '• ' . $g['grade'] . ': ' . $g['min'] . '% — ' . $g['max'] . '%';
        }, $scale);

        return '**Grading Scale for ' . $standardName . ':**' . "\n" . implode("\n", $lines);
    }
}
