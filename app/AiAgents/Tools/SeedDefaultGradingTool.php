<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\Models\Standard;

class SeedDefaultGradingTool implements Tool
{
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
        $schoolId = $user->school_id;
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
            if (!$standard->grade_scale) {
                $standard->grade_scale = json_encode($defaultScales);
                $standard->save();
                $count++;
            }
        }

        return '✅ Default grading scale seeded for **' . $count . '** standards in your school.';
    }
}
