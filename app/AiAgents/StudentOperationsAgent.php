<?php

namespace App\AiAgents;

use App\AiAgents\Tools\Student\ManageConversationsTool;
use App\AiAgents\Tools\Student\ManageTasksTool;
use App\AiAgents\Tools\Student\SubmitAssignmentTool;
use App\AiAgents\Tools\Student\SubmitHomeworkTool;
use App\AiAgents\Tools\Student\ViewAssignmentsTool;
use App\AiAgents\Tools\Student\ViewAttendanceTool;
use App\AiAgents\Tools\Student\ViewClassWallTool;
use App\AiAgents\Tools\Student\ViewDashboardTool;
use App\AiAgents\Tools\Student\ViewEventsTool;
use App\AiAgents\Tools\Student\ViewHomeworkTool;
use App\AiAgents\Tools\Student\ViewLibraryActivityTool;
use App\AiAgents\Tools\Student\ViewMarksTool;
use App\AiAgents\Tools\Student\ViewNoticesTool;
use App\Models\School;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Student-scoped Toshi operator (ug6) — self-scope only.
 *
 * Structural isolation: tools() lists only the 13 student actions.
 * Every tool resolves identity from auth()->user() — never LLM student_id.
 * Deterministic scope router in ToshiSdkV2Service selects this agent for ug6.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class StudentOperationsAgent implements Agent, HasTools
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
You are Toshi assisting a **student** at {$schoolName}.

You may only use student self-scope tools: view dashboard, marks, attendance, library activity, events, notices, assignments, homework, class wall (read-only), and manage personal tasks / conversations. You may also submit the student's own assignment or homework.

Rules:
- Never ask for or accept another student's id — all data is for the signed-in student only.
- Do not attempt school-admin, teacher, accountant, librarian, or receptionist actions.
- Class wall is read-only — do not like, comment, or post.
- Writes (submit assignment/homework, tasks, conversations) require confirmation — wait for the user to confirm.
- Be concise and confirm outcomes clearly.
PROMPT;
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new ViewDashboardTool,
            new ViewMarksTool,
            new ViewAttendanceTool,
            new ViewLibraryActivityTool,
            new ViewEventsTool,
            new ViewNoticesTool,
            new ViewAssignmentsTool,
            new ViewHomeworkTool,
            new ViewClassWallTool,
            new SubmitAssignmentTool,
            new SubmitHomeworkTool,
            new ManageTasksTool,
            new ManageConversationsTool,
        ];
    }
}
