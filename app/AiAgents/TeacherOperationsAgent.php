<?php

namespace App\AiAgents;

use App\AiAgents\Tools\Teacher\ApplyLeaveTool;
use App\AiAgents\Tools\Teacher\CreateAssignmentTool;
use App\AiAgents\Tools\Teacher\CreateClassWallPostTool;
use App\AiAgents\Tools\Teacher\CreateHomeworkTool;
use App\AiAgents\Tools\Teacher\CreateLessonPlanTool;
use App\AiAgents\Tools\Teacher\CreateTaskTool;
use App\AiAgents\Tools\Teacher\EnterMarksTool;
use App\AiAgents\Tools\Teacher\MarkAttendanceTool;
use App\AiAgents\Tools\Teacher\ViewEventsTool;
use App\AiAgents\Tools\Teacher\ViewNoticeboardTool;
use App\AiAgents\Tools\Teacher\ViewStudentsTool;
use App\AiAgents\Tools\Teacher\ViewTimetableTool;
use App\Models\School;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use App\AiAgents\Concerns\UsesToshiLlm;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Teacher-scoped Toshi operator (ug5).
 *
 * Structural isolation: tools() lists only the 12 advisory teacher actions.
 * School-admin tools (e.g. AddCoAdminTool) are never registered here.
 * Deterministic scope router in ToshiSdkV2Service selects this agent for ug5 —
 * this is not Laravel AI Sub-Agents / CanActAsTool.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class TeacherOperationsAgent implements Agent, HasTools
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
You are Toshi assisting a **teacher** at {$schoolName}.

You may only use teacher tools: attendance, marks (your exams), lesson plans, assignments, homework, leave applications, class wall posts, tasks, and read-only views (students, timetable basis, events, notices).

Rules:
- Do not attempt school-admin actions (co-admins, fees, school settings, adding teachers/students as admin).
- Prefer Teacherlink-scoped operations; refuse class/subject pairs the teacher is not linked to when the tool requires it.
- Be concise and confirm outcomes clearly.
PROMPT;
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new MarkAttendanceTool,
            new EnterMarksTool,
            new CreateLessonPlanTool,
            new CreateAssignmentTool,
            new CreateHomeworkTool,
            new ApplyLeaveTool,
            new CreateClassWallPostTool,
            new CreateTaskTool,
            new ViewStudentsTool,
            new ViewTimetableTool,
            new ViewEventsTool,
            new ViewNoticeboardTool,
        ];
    }
}
