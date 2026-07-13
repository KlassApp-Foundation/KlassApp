<?php

namespace App\AiAgents\Skills;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\MaxSteps;
use App\AiAgents\Tools\ListClassesTool;
use App\AiAgents\Tools\ListSectionsTool;
use App\AiAgents\Tools\CreateTermTool;
use App\AiAgents\Tools\CreateSubjectTool;
use App\AiAgents\Tools\CreateExamTool;

#[MaxSteps(5)]
class AcademicSkill implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You handle all academic structure management. You can list classes and sections, create academic terms, create subjects, and create exams. The academic structure follows the UNEB Uganda curriculum — classes include Nursery, Primary (P1-P7), O-Level (S1-S4), and A-Level (S5-S6). Always ask for missing required parameters before creating records.';
    }

    public function tools(): iterable
    {
        return [
            new ListClassesTool,
            new ListSectionsTool,
            new CreateTermTool,
            new CreateSubjectTool,
            new CreateExamTool,
        ];
    }
}
