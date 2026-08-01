<?php

namespace App\AiAgents\Tools\Receptionist;

use App\AiAgents\Concerns\AuthorizesReceptionistToshiAction;
use App\Services\Toshi\ReceptionistActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewEventsTool implements Tool
{
    use AuthorizesReceptionistToshiAction;

    public function description(): Stringable|string
    {
        return 'List recent school events (read-only; excludes holidays).';
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

        $result = ReceptionistActionService::viewEvents($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
