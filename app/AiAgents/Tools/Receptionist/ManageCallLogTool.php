<?php

namespace App\AiAgents\Tools\Receptionist;

use App\AiAgents\Concerns\AuthorizesReceptionistToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\ReceptionistActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ManageCallLogTool implements Tool
{
    use AuthorizesReceptionistToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Log a phone call. call_type is incoming or outgoing. Incoming requires incoming_number; outgoing requires outgoing_number and calling_purpose. call_date is Y-m-d (today or earlier). Optional start_time/end_time (H:i) and description.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'call_type' => $schema->string()->required(),
            'call_date' => $schema->string()->required(),
            'calling_purpose' => $schema->string()->nullable(),
            'incoming_number' => $schema->string()->nullable(),
            'outgoing_number' => $schema->string()->nullable(),
            'start_time' => $schema->string()->nullable(),
            'end_time' => $schema->string()->nullable(),
            'description' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeReceptionistOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'name' => $request->get('name'),
            'call_type' => $request->get('call_type'),
            'call_date' => $request->get('call_date'),
            'calling_purpose' => $request->get('calling_purpose'),
            'incoming_number' => $request->get('incoming_number'),
            'outgoing_number' => $request->get('outgoing_number'),
            'start_time' => $request->get('start_time'),
            'end_time' => $request->get('end_time'),
            'description' => $request->get('description'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolReceptionistManageCallLog',
            $args,
            fn () => "Log {$args['call_type']} call: {$args['name']} on {$args['call_date']}"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = ReceptionistActionService::manageCallLog($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
