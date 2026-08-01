<?php

namespace App\AiAgents\Tools\Parent;

use App\AiAgents\Concerns\AuthorizesParentToshiAction;
use App\Services\Toshi\ParentActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListChildrenTool implements Tool
{
    use AuthorizesParentToshiAction;

    public function description(): Stringable|string
    {
        return 'List the children linked to the signed-in parent WhatsApp account. Never ask for student IDs.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeParentOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = ParentActionService::listChildren($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
