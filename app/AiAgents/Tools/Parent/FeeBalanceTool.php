<?php

namespace App\AiAgents\Tools\Parent;

use App\AiAgents\Concerns\AuthorizesParentToshiAction;
use App\Services\Toshi\ParentActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FeeBalanceTool implements Tool
{
    use AuthorizesParentToshiAction;

    public function description(): Stringable|string
    {
        return 'Show fee balance for a linked child. Optional child_name when the parent has more than one child. Never accept student_id.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'child_name' => $schema->string()->description('Child name or 1-based ordinal among linked children')->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeParentOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = ParentActionService::feeBalance($user, $request->get('child_name'));

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
