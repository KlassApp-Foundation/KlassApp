<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\FeesCategories;

class CreateFeeTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Create a fee category. Provide name, amount, and optionally: level, class, term name.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Fee name, e.g. "Tuition Fees"'),
            'amount' => $schema->number()->min(0)->description('Amount in UGX'),
            'level' => $schema->string()->description('Level, e.g. "Primary" or "Secondary"')->nullable(),
            'class' => $schema->string()->description('Class name')->nullable(),
            'term_name' => $schema->string()->description('Term name, e.g. "Term 1"')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = $request->all();

        $confirm = $this->confirmOrExecute('toolCreateFee', $args,
            fn() => "Create fee: {$args['name']}, UGX {$args['amount']}"
                . (!empty($args['class']) ? ", class: {$args['class']}" : ''));
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::createFee($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $name = trim($request->get('name', ''));
        $amount = $request->get('amount');
        if ($name === '' || $amount === null) {
            return ['verified' => false, 'message' => 'Fee name and amount are required for verification.'];
        }

        $exists = FeesCategories::where('school_id', $schoolId)
            ->where('name', $name)
            ->where('amount', (float) $amount)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Fee category confirmed in database.'
                : 'Fee category was not found after creation.',
        ];
    }
}
