<?php

namespace App\AiAgents\Skills;

use App\AiAgents\Concerns\UsesToshiLlm;
use App\AiAgents\Tools\Teacher\CancelMyLeaveTool;
use App\AiAgents\Tools\Teacher\ListMyLeavesTool;
use App\AiAgents\Tools\Teacher\ListStudentAssignmentsTool;
use App\AiAgents\Tools\Teacher\ListStudentHomeworkTool;
use App\AiAgents\Tools\Teacher\MarkStudentAssignmentTool;
use App\AiAgents\Tools\Teacher\ShowMyLeaveTool;
use App\AiAgents\Tools\Teacher\ShowStudentAssignmentTool;
use App\AiAgents\Tools\Teacher\ShowStudentHomeworkTool;
use App\AiAgents\Tools\Teacher\UpdateStudentHomeworkTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Teacher Batch 2 — submission review + own leave status.
 *
 * Invoked via RouteToTeacherTeachingOpsSkillTool. UsesToshiLlm because prompt()
 * is called from the router (SchoolComms / SchoolAcademicsOps lesson).
 */
#[MaxSteps(5)]
class TeacherTeachingOpsSkill implements Agent, HasTools
{
    use Promptable;
    use UsesToshiLlm;

    public function instructions(): string
    {
        return 'You help a teacher with the teaching loop: review student homework '
            .'submissions (list/show/check), mark student assignment submissions, '
            .'and manage the teacher\'s own leave applications (list/show/cancel pending). '
            .'Homework review requires studentHomework-review; assignment review requires '
            .'studentAssignment-review; own leave requires teacher-leave. Never approve peer '
            .'leave, never destroy homework/assignments, never widen student ownership Gates.';
    }

    public function tools(): iterable
    {
        return [
            new ListStudentHomeworkTool,
            new ShowStudentHomeworkTool,
            new UpdateStudentHomeworkTool,
            new ListStudentAssignmentsTool,
            new ShowStudentAssignmentTool,
            new MarkStudentAssignmentTool,
            new ListMyLeavesTool,
            new ShowMyLeaveTool,
            new CancelMyLeaveTool,
        ];
    }
}
