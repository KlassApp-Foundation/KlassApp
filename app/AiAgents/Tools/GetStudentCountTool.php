<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Models\User;

class GetStudentCountTool implements Tool
{
    use AuthorizesToshiAction;
    public function description(): string
    {
        return 'Get the total number of students enrolled in the school.';
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
        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return 'You are not assigned to a school.';
        }

        $count = User::where('school_id', $schoolId)->where('usergroup_id', 6)->count();
        return 'Total students enrolled: **' . $count . '**.';
    }
}
