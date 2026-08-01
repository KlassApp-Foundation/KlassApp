<?php

namespace App\AiAgents\Tools\Accountant;

use App\AiAgents\Concerns\AuthorizesAccountantToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\AccountantActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ManagePayrollTool implements Tool
{
    use AuthorizesAccountantToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Run a batch payroll for a payroll template and pay period (start_date / end_date as Y-m-d). Creates unpaid payroll records for all staff on that template.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'template_id' => $schema->integer()->required(),
            'start_date' => $schema->string()->required(),
            'end_date' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeAccountantOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'template_id' => $request->get('template_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolAccountantManagePayroll',
            $args,
            fn () => "Run payroll for template #{$args['template_id']} ({$args['start_date']} → {$args['end_date']})"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = AccountantActionService::managePayroll($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
