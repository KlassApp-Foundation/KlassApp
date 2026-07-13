<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ListSectionsTool implements Tool
{
    public function description(): string
    {
        return 'List all sections/streams configured for the school.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::listSections($user);
        if (!$result['success']) {
            return '❌ ' . $result['message'];
        }

        $sections = $result['sections'] ?? collect();
        if ($sections->isEmpty()) {
            return 'No sections configured for this school.';
        }

        $lines = $sections->map(fn($s) => '• ' . $s->name)->implode("\n");
        return '**Sections:**' . "\n" . $lines;
    }
}
