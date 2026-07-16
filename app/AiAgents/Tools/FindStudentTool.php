<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;

class FindStudentTool implements Tool
{
    use AuthorizesToshiAction;
    public function description(): string
    {
        return 'Find a student by name, email, or student ID. Returns matching student details.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Search query — student name, email, or ID number'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;
        $effectiveUser = \App\Services\ToshiActionService::getEffectiveUser($user);
        $student = \App\Services\ToshiActionService::findStudentSimple($effectiveUser, $request->get('query'));
        if (!$student) {
            return 'No student found matching "' . $request->get('query') . '".';
        }
        return 'Found: ' . $student->name . ' (ID: ' . $student->id . ', Email: ' . $student->email . ')';
    }
}
