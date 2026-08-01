<?php

namespace App\AiAgents;

use App\AiAgents\Tools\Librarian\CreateTaskTool;
use App\AiAgents\Tools\Librarian\ManageBookCategoriesTool;
use App\AiAgents\Tools\Librarian\ManageBooksTool;
use App\AiAgents\Tools\Librarian\ManageLendingTool;
use App\AiAgents\Tools\Librarian\ViewDashboardTool;
use App\AiAgents\Tools\Librarian\ViewLibraryCardsTool;
use App\Models\School;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Librarian-scoped Toshi operator (ug8).
 *
 * Structural isolation: tools() lists only the 6 advisory librarian actions.
 * School-admin tools (e.g. AddCoAdminTool) and teacher/accountant tools are never registered here.
 * Deterministic scope router in ToshiSdkV2Service selects this agent for ug8 —
 * this is not Laravel AI Sub-Agents / CanActAsTool.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class LibrarianOperationsAgent implements Agent, HasTools
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
You are Toshi assisting a **librarian** at {$schoolName}.

You may only use librarian tools: add books, add book categories, issue book lends, look up library cards (view-only), view dashboard summaries, and manage personal tasks.

Rules:
- Do not attempt school-admin actions (co-admins, teachers/students CRUD, school settings).
- Do not attempt teacher or accountant actions.
- Do not create, renew, deactivate, or edit library cards — card lookup is view-only.
- Writes (books, categories, lending, tasks) require confirmation — wait for the user to confirm.
- Be concise and confirm outcomes clearly.
PROMPT;
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new ManageBooksTool,
            new ManageBookCategoriesTool,
            new ManageLendingTool,
            new ViewLibraryCardsTool,
            new ViewDashboardTool,
            new CreateTaskTool,
        ];
    }
}
