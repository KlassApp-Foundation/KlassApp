<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;

class ListTeachersTool implements Tool
{
    use AuthorizesToshiAction;
    public function description(): string
    {
        return 'List all teachers in the school with their subject and class assignments.';
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
        $result = \App\Services\ToshiActionService::listTeachers($user);
        if (!$result['success']) {
            return '❌ ' . $result['message'];
        }

        $teachers = $result['teachers'] ?? collect();
        if ($teachers->isEmpty()) {
            return 'No teachers found in this school.';
        }

        $lines = $teachers->map(fn($t) => '• ' . $t->name . ' (' . $t->email . ')')->implode("\n");
        return '**Teachers (' . $teachers->count() . '):**' . "\n" . $lines;
    }
}
