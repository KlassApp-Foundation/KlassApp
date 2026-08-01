<?php

namespace App\AiAgents\Tools\Accountant;

use App\AiAgents\Concerns\AuthorizesAccountantToshiAction;
use App\Services\Toshi\AccountantActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewFeeStructureTool implements Tool
{
    use AuthorizesAccountantToshiAction;

    public function description(): Stringable|string
    {
        return 'List fee categories (fee structure) for the school.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeAccountantOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = AccountantActionService::viewFeeStructure($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
