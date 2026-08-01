<?php

namespace App\AiAgents\Tools\Receptionist;

use App\AiAgents\Concerns\AuthorizesReceptionistToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\ReceptionistActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ManageVisitorLogTool implements Tool
{
    use AuthorizesReceptionistToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Log a school visitor (name, contact_number, visiting_purpose, date_of_visit Y-m-d; optional entry_time/exit_time H:i, email, company_name, address, number_of_visitors, employee_id, remark).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'contact_number' => $schema->string()->required(),
            'visiting_purpose' => $schema->string()->required(),
            'date_of_visit' => $schema->string()->required(),
            'entry_time' => $schema->string()->nullable(),
            'exit_time' => $schema->string()->nullable(),
            'email' => $schema->string()->nullable(),
            'company_name' => $schema->string()->nullable(),
            'address' => $schema->string()->nullable(),
            'number_of_visitors' => $schema->integer()->nullable(),
            'employee_id' => $schema->integer()->nullable(),
            'remark' => $schema->string()->nullable(),
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
            'contact_number' => $request->get('contact_number'),
            'visiting_purpose' => $request->get('visiting_purpose'),
            'date_of_visit' => $request->get('date_of_visit'),
            'entry_time' => $request->get('entry_time'),
            'exit_time' => $request->get('exit_time'),
            'email' => $request->get('email'),
            'company_name' => $request->get('company_name'),
            'address' => $request->get('address'),
            'number_of_visitors' => $request->get('number_of_visitors'),
            'employee_id' => $request->get('employee_id'),
            'remark' => $request->get('remark'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolReceptionistManageVisitorLog',
            $args,
            fn () => "Log visitor: {$args['name']} on {$args['date_of_visit']}"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = ReceptionistActionService::manageVisitorLog($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
