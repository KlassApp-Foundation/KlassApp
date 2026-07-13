<?php

namespace App\AiAgents\Skills;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\MaxSteps;
use App\AiAgents\Tools\GenerateReportTool;

#[MaxSteps(5)]
class ReportingSkill implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You handle report generation. You can generate academic, fee, attendance, and summary reports. Reports can be filtered by term and class. Ask the user what type of report they need and any filters before generating.';
    }

    public function tools(): iterable
    {
        return [
            new GenerateReportTool,
        ];
    }
}
