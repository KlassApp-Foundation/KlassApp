<?php

namespace App\AiAgents\Skills;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\MaxSteps;
use App\AiAgents\Tools\AddTeacherTool;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\AssignTeacherTool;
use App\AiAgents\Tools\ListTeachersTool;
use App\AiAgents\Tools\ManagePreferencesTool;

#[MaxSteps(5)]
class TeacherSkill implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You handle all teacher and staff management tasks. You can add teachers, assign teachers to subjects and classes, promote teachers to co-admins, and list all teachers with their assignments. Always ask for missing required parameters before creating records.';
    }

    public function tools(): iterable
    {
        return [
            new AddTeacherTool,
            new AddCoAdminTool,
            new AssignTeacherTool,
            new ListTeachersTool,
            new ManagePreferencesTool,
        ];
    }
}
