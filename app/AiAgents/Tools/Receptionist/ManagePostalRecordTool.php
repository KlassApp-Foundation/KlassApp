<?php

namespace App\AiAgents\Tools\Receptionist;

use App\AiAgents\Concerns\AuthorizesReceptionistToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\ReceptionistActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ManagePostalRecordTool implements Tool
{
    use AuthorizesReceptionistToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Log a postal record (type, confidential yes/no or 0/1, description, postal_date Y-m-d; optional reference_number, sender_title, sender_address, receiver_title, receiver_address).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required(),
            'confidential' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'postal_date' => $schema->string()->required(),
            'reference_number' => $schema->string()->nullable(),
            'sender_title' => $schema->string()->nullable(),
            'sender_address' => $schema->string()->nullable(),
            'receiver_title' => $schema->string()->nullable(),
            'receiver_address' => $schema->string()->nullable(),
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
            'type' => $request->get('type'),
            'confidential' => $request->get('confidential'),
            'description' => $request->get('description'),
            'postal_date' => $request->get('postal_date'),
            'reference_number' => $request->get('reference_number'),
            'sender_title' => $request->get('sender_title'),
            'sender_address' => $request->get('sender_address'),
            'receiver_title' => $request->get('receiver_title'),
            'receiver_address' => $request->get('receiver_address'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolReceptionistManagePostalRecord',
            $args,
            fn () => "Log postal ({$args['type']}) on {$args['postal_date']}"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = ReceptionistActionService::managePostalRecord($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
