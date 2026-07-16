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

class SeedDefaultGradingTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Seed default UNEB grading scales (A: 80-100, B: 70-79, C: 55-69, D: 40-54, E: 0-39) for all standards in the school that do not already have a scale configured.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = [];

        $confirm = $this->confirmOrExecute('toolSeedDefaultGrading', $args,
            fn() => 'Seed default UNEB grading scale (A: 80-100, B: 70-79, C: 55-69, D: 40-54, E: 0-39) for all standards');
        if ($confirm !== null) return $confirm;

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (!$schoolId) {
            return 'You are not assigned to a school.';
        }

        $defaultScales = [
            ['grade' => 'A', 'min' => 80, 'max' => 100],
            ['grade' => 'B', 'min' => 70, 'max' => 79],
            ['grade' => 'C', 'min' => 55, 'max' => 69],
            ['grade' => 'D', 'min' => 40, 'max' => 54],
            ['grade' => 'E', 'min' => 0, 'max' => 39],
        ];

        $standards = Standard::whereHas('standardLinks', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->get();

        $count = 0;
        foreach ($standards as $standard) {
            $hasScale = SchoolGradingSystem::where('school_id', $schoolId)
                ->where('standard_id', $standard->id)
                ->exists();
            if (!$hasScale) {
                foreach ($defaultScales as $g) {
                    SchoolGradingSystem::create([
                        'school_id'   => $schoolId,
                        'standard_id' => $standard->id,
                        'grade'       => $g['grade'],
                        'min_score'   => $g['min'],
                        'max_score'   => $g['max'],
                    ]);
                }
                $count++;
            }
        }

        return '✅ Default grading scale seeded for **' . $count . '** standards in your school.';
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $seeded = SchoolGradingSystem::where('school_id', $schoolId)
            ->distinct('standard_id')
            ->count('standard_id');

        return [
            'verified' => $seeded > 0,
            'message' => $seeded > 0
                ? "Grading scale confirmed for {$seeded} standard(s)."
                : 'No grading scales were found after seeding.',
        ];
    }
}
