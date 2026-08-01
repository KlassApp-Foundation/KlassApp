<?php

namespace App\AiAgents\Tools\Accountant;

use App\AiAgents\Concerns\AuthorizesAccountantToshiAction;
use App\Services\Toshi\AccountantActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewDashboardTool implements Tool
{
    use AuthorizesAccountantToshiAction;

    public function description(): Stringable|string
    {
        return 'Show an accountant dashboard summary (fees, students, tasks, unpaid payrolls).';
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

        $result = AccountantActionService::viewDashboard($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
