<?php

namespace App\AiAgents\Skills;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\MaxSteps;
use App\AiAgents\Tools\CreateFeeTool;
use App\AiAgents\Tools\RecordPaymentTool;
use App\AiAgents\Tools\GetFeeBalanceTool;

#[MaxSteps(5)]
class FeeSkill implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You handle all fee and payment operations. You can create fee categories, record payments against students, and check fee balances. Payment methods are: cash, cheque, mobile_money, bank_transfer. Amounts are in UGX (Ugandan Shillings). Always confirm amounts and methods with the user before recording payments.';
    }

    public function tools(): iterable
    {
        return [
            new CreateFeeTool,
            new RecordPaymentTool,
            new GetFeeBalanceTool,
        ];
    }
}
