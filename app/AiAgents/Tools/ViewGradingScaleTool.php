<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Models\Academics\SchoolGradingSystem;
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

        $grades = SchoolGradingSystem::where('school_id', $schoolId)
            ->where('standard_id', $standard->id)
            ->orderBy('min_score', 'desc')
            ->get();

        if ($grades->isEmpty()) {
            return 'No grading scale configured for "' . $standardName . '".';
        }

        $lines = $grades->map(function ($g) {
            $parts = '• ' . $g->grade . ': ' . $g->min_score . '% — ' . $g->max_score . '%';
            if ($g->remark) {
                $parts .= ' (' . $g->remark . ')';
            }
            if ($g->points !== null) {
                $parts .= ' — points: ' . $g->points;
            }
            return $parts;
        })->toArray();

        return '**Grading Scale for ' . $standardName . ':**' . "\n" . implode("\n", $lines);
    }
}
