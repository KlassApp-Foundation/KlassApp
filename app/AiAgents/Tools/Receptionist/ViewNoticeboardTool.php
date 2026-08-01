<?php

namespace App\AiAgents\Tools\Receptionist;

use App\AiAgents\Concerns\AuthorizesReceptionistToshiAction;
use App\Services\Toshi\ReceptionistActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/** Read-only noticeboard list. No create/update/destroy. */
class ViewNoticeboardTool implements Tool
{
    use AuthorizesReceptionistToshiAction;

    public function description(): Stringable|string
    {
        return 'List school noticeboard items for the current academic year (view-only — cannot create or edit notices).';
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

        $result = ReceptionistActionService::viewNoticeboard($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
