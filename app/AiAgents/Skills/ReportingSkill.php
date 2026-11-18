<?php

namespace App\AiAgents\Skills;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\MaxSteps;
use App\AiAgents\Tools\GenerateReportTool;
use App\AiAgents\Tools\Marks\LearnerReportStatusTool;
use App\AiAgents\Tools\Marks\MissingMarksTool;
use App\AiAgents\Tools\Marks\PrepareClassTeacherReminderTool;

#[MaxSteps(5)]
class ReportingSkill implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You handle report generation and marks reads. You can generate academic, fee, attendance, and summary reports. '
            .'You can read missing or unapproved marks for a class and exam set, and a learner\'s report status (released or not, which sets are included, what is not released yet). '
            .'If several learners match, ask which one. You can prepare a draft reminder for the class teacher; confirming it does not send anything. '
            .'Never calculate or restate a mark, total, aggregate, or division. Pass through the tool values. '
            .'Reports can be filtered by term and class. Ask the user what type of report they need and any filters before generating.';
    }

    public function tools(): iterable
    {
        return [
            new GenerateReportTool,
            new MissingMarksTool,
            new LearnerReportStatusTool,
            new PrepareClassTeacherReminderTool,
        ];
    }
}
