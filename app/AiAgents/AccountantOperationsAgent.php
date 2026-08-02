<?php

namespace App\AiAgents;

use App\AiAgents\Tools\Accountant\CreateTaskTool;
use App\AiAgents\Tools\Accountant\ManagePayrollTool;
use App\AiAgents\Tools\Accountant\RecordPaymentTool;
use App\AiAgents\Tools\Accountant\ViewDashboardTool;
use App\AiAgents\Tools\Accountant\ViewFeeStructureTool;
use App\AiAgents\Tools\Accountant\ViewUnpaidReportsTool;
use App\Models\School;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use App\AiAgents\Concerns\UsesToshiLlm;
use Laravel\Ai\Promptable;

/**
 * Accountant-scoped Toshi operator (ug11).
 *
 * Structural isolation: tools() lists only the 6 advisory accountant actions.
 * School-admin tools (e.g. AddCoAdminTool) and teacher tools are never registered here.
 * Deterministic scope router in ToshiSdkV2Service selects this agent for ug11 —
 * this is not Laravel AI Sub-Agents / CanActAsTool.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class AccountantOperationsAgent implements Agent, HasTools
{
    use Promptable;
    use UsesToshiLlm;


    public function instructions(): string
    {
        $user = auth()->user() ?? request()->user();
        $schoolName = 'your school';
        if ($user?->school_id) {
            $schoolName = School::find($user->school_id)?->name ?? $schoolName;
        }

        return <<<PROMPT
You are Toshi assisting an **accountant** at {$schoolName}.

You may only use accountant tools: record fee payments, run payroll (batch by template), view unpaid payroll reports, view fee structure, view dashboard summaries, and manage personal tasks.

Rules:
- Do not attempt school-admin actions (co-admins, teachers/students CRUD, school settings).
- Do not attempt teacher actions (attendance, marks, lesson plans).
- Money movement (payments, payroll) requires confirmation — wait for the user to confirm.
- Be concise and confirm outcomes clearly.
PROMPT;
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new RecordPaymentTool,
            new ManagePayrollTool,
            new ViewUnpaidReportsTool,
            new ViewFeeStructureTool,
            new ViewDashboardTool,
            new CreateTaskTool,
        ];
    }
}
