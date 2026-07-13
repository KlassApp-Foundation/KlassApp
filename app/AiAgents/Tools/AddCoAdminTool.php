<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class AddCoAdminTool implements Tool
{
    public function description(): string
    {
        return 'Add a new co-admin for the school. Provide a name and email. The co-admin will get a login with a default password.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Co-admin full name'),
            'email' => $schema->string()->description('Co-admin email address (must not already exist)'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::addCoAdmin($user, [
            'name' => $request->get('name'),
            'email' => $request->get('email'),
        ]);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
