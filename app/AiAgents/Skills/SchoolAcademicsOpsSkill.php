<?php

namespace App\AiAgents\Skills;

use App\AiAgents\Concerns\UsesToshiLlm;
use App\AiAgents\Tools\ApproveHomeworkTool;
use App\AiAgents\Tools\CreateHomeworkTool;
use App\AiAgents\Tools\CreateTimetableSlotTool;
use App\AiAgents\Tools\GenerateStudentReportCardTool;
use App\AiAgents\Tools\ListHomeworkTool;
use App\AiAgents\Tools\ListStudentHomeworkTool;
use App\AiAgents\Tools\ListTimetableSlotsTool;
use App\AiAgents\Tools\RejectHomeworkTool;
use App\AiAgents\Tools\ShowStudentHomeworkTool;
use App\AiAgents\Tools\UpdateHomeworkTool;
use App\AiAgents\Tools\UpdateStudentHomeworkTool;
use App\AiAgents\Tools\UpdateTimetableSlotTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * School Admin Batch 2 — timetable slots + homework oversight + student-homework review.
 *
 * Invoked via RouteToSchoolAcademicsOpsSkillTool (custom Tool wrapper), not SDK Sub-Agent.
 * UsesToshiLlm: this skill calls prompt() itself (Batch 1 SchoolComms lesson).
 * No destroy / settings / promotion.
 */
#[MaxSteps(5)]
class SchoolAcademicsOpsSkill implements Agent, HasTools
{
    use Promptable;
    use UsesToshiLlm;

    public function instructions(): string
    {
        return 'You manage academic schedule, homework oversight, and per-student report cards: '
            .'timetable slots, admin homework (list/create/update/approve/reject), student-homework review '
            .'(list/show/mark checked), and generate student report-card PDFs. Never destroy, promote, or change Settings. '
            .'Homework mutations require homework-manage authorization; submission review requires '
            .'studentHomework-review. Ask for missing required fields before creating.';
    }

    public function tools(): iterable
    {
        return [
            new ListTimetableSlotsTool,
            new CreateTimetableSlotTool,
            new UpdateTimetableSlotTool,
            new ListHomeworkTool,
            new CreateHomeworkTool,
            new UpdateHomeworkTool,
            new ApproveHomeworkTool,
            new RejectHomeworkTool,
            new ListStudentHomeworkTool,
            new ShowStudentHomeworkTool,
            new UpdateStudentHomeworkTool,
            new GenerateStudentReportCardTool,
        ];
    }
}
