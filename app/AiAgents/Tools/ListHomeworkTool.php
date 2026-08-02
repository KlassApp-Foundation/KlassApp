<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Services\Toshi\SchoolAcademicsOpsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListHomeworkTool implements Tool
{
    use AuthorizesToshiAction;

    public function description(): Stringable|string
    {
        return 'List school homework (optional approval status filter). Uses school scope.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('pending|approved|rejected')->nullable(),
            'limit' => $schema->integer()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = SchoolAcademicsOpsActionService::listHomework($user, [
            'status' => $request->get('status'),
            'limit' => $request->get('limit') ?? 20,
        ]);

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
