<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ListClassesTool implements Tool
{
    public function description(): string
    {
        return 'List all classes/standards configured for the school.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::listClasses($user);
        if (!$result['success']) {
            return '❌ ' . $result['message'];
        }

        $classes = $result['classes'] ?? collect();
        if ($classes->isEmpty()) {
            return 'No classes configured for this school.';
        }

        $lines = $classes->map(function ($l) {
            $standard = $l->standard;
            $section = $l->section;
            $label = ($standard?->name ?? '') . ($section ? ' ' . $section->name : '');
            return '• ' . $label;
        })->implode("\n");

        return '**Classes (' . $classes->count() . '):**' . "\n" . $lines;
    }
}
