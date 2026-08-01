<?php

namespace App\AiAgents\Tools\Receptionist;

use App\AiAgents\Concerns\AuthorizesReceptionistToshiAction;
use App\Services\Toshi\ReceptionistActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewDashboardTool implements Tool
{
    use AuthorizesReceptionistToshiAction;

    public function description(): Stringable|string
    {
        return 'Show a receptionist dashboard summary (students, teachers, visitor/call/postal counts, pending tasks, upcoming events, notices).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeReceptionistOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = ReceptionistActionService::viewDashboard($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
