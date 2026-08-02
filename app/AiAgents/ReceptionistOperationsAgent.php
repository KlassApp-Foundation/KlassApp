<?php

namespace App\AiAgents;

use App\AiAgents\Tools\Receptionist\CreateTaskTool;
use App\AiAgents\Tools\Receptionist\ManageCallLogTool;
use App\AiAgents\Tools\Receptionist\ManagePostalRecordTool;
use App\AiAgents\Tools\Receptionist\ManageVisitorLogTool;
use App\AiAgents\Tools\Receptionist\ViewDashboardTool;
use App\AiAgents\Tools\Receptionist\ViewEventsTool;
use App\AiAgents\Tools\Receptionist\ViewNoticeboardTool;
use App\AiAgents\Tools\ManagePreferencesTool;
use App\Models\School;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Receptionist-scoped Toshi operator (ug10).
 *
 * Structural isolation: tools() lists only the 7 advisory receptionist actions
 * (email ledger capability dropped — no ManageEmailRecordTool).
 * School-admin / teacher / accountant / librarian tools are never registered here.
 * Deterministic scope router in ToshiSdkV2Service selects this agent for ug10 —
 * this is not Laravel AI Sub-Agents / CanActAsTool.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class ReceptionistOperationsAgent implements Agent, HasTools
{
    use Promptable;

    public function provider(): string
    {
        return 'openai-compatible';
    }

    public function model(): string
    {
        return config('toshi.model', 'nvidia/llama-3.3-nemotron-super-49b-v1');
    }

    public function instructions(): string
    {
        $user = auth()->user() ?? request()->user();
        $schoolName = 'your school';
        if ($user?->school_id) {
            $schoolName = School::find($user->school_id)?->name ?? $schoolName;
        }

        return <<<PROMPT
You are Toshi assisting a **receptionist** at {$schoolName}.

You may only use receptionist tools: log visitors, log calls, log postal records, view the reception dashboard, view events, view the noticeboard (read-only), and manage personal tasks.

Rules:
- Do not attempt school-admin actions (co-admins, teachers/students CRUD, school settings).
- Do not attempt teacher, accountant, or librarian actions.
- Do not create or edit notices — noticeboard is view-only.
- Writes (visitor / call / postal logs, tasks) require confirmation — wait for the user to confirm.
- Be concise and confirm outcomes clearly.
PROMPT;
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new ManageVisitorLogTool,
            new ManageCallLogTool,
            new ManagePostalRecordTool,
            new ViewDashboardTool,
            new ViewEventsTool,
            new ViewNoticeboardTool,
            new CreateTaskTool,
            new ManagePreferencesTool,
        ];
    }
}
