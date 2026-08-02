<?php

namespace App\AiAgents\Skills;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\MaxSteps;
use App\AiAgents\Tools\AddStudentTool;
use App\AiAgents\Tools\FindStudentTool;
use App\AiAgents\Tools\GetStudentCountTool;
use App\AiAgents\Tools\RecordAttendanceTool;
use App\AiAgents\Tools\RecordBulkAttendanceTool;
use App\AiAgents\Tools\EnterMarkTool;
use App\AiAgents\Tools\AddParentTool;
use App\AiAgents\Tools\ManagePreferencesTool;

#[MaxSteps(5)]
class StudentSkill implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You handle all student management tasks. You can add students, find students by name/email/ID, get enrollment counts, record attendance (single and bulk), enter exam marks, and add parents linked to students. Always ask for missing required parameters before creating records. Confirm with the user before writing data.';
    }

    public function tools(): iterable
    {
        return [
            new AddStudentTool,
            new FindStudentTool,
            new GetStudentCountTool,
            new RecordAttendanceTool,
            new RecordBulkAttendanceTool,
            new EnterMarkTool,
            new AddParentTool,
            new ManagePreferencesTool,
        ];
    }
}
