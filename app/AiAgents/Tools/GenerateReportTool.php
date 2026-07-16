<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;

class GenerateReportTool implements Tool
{
    use AuthorizesToshiAction;
    public function description(): string
    {
        return 'Generate a comprehensive school summary report with student, teacher, class, fee, and attendance statistics.';
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
        $result = \App\Services\ToshiActionService::generateReport($user);
        return $result['success'] ? $result['message'] : '❌ ' . $result['message'];
    }
}
