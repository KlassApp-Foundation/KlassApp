<?php

namespace App\AiAgents\Skills;

use App\AiAgents\Tools\CreateEventTool;
use App\AiAgents\Tools\CreateHolidayTool;
use App\AiAgents\Tools\CreateNoticeTool;
use App\AiAgents\Tools\ListEventsTool;
use App\AiAgents\Tools\ListHolidaysTool;
use App\AiAgents\Tools\ListNoticesTool;
use App\AiAgents\Tools\UpdateEventTool;
use App\AiAgents\Tools\UpdateHolidayTool;
use App\AiAgents\Tools\UpdateNoticeTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * School Admin Batch 1 — notices / events / holidays (create/list/update only).
 *
 * Notices share notice_board with Receptionist ViewNoticeboardTool.
 * Holidays are Events.category=holidays. No destroy/promote/settings.
 *
 * Invoked via RouteToSchoolCommsSkillTool (custom Tool wrapper), not as an
 * SDK Sub-Agent (does not implement CanActAsTool; Orchestrator does not return
 * this Agent from tools() for AgentTool wrapping).
 */
#[MaxSteps(5)]
class SchoolCommsSkill implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You manage school communications calendars: noticeboard (NoticeBoard), events, and holidays. '
            .'You can list, create, and update — never destroy, promote, or change Settings. '
            .'Notices use the same notice_board table Receptionists read via view noticeboard. '
            .'Holidays are Events with category=holidays; other events exclude that category. '
            .'Ask for missing required fields before creating.';
    }

    public function tools(): iterable
    {
        return [
            new ListNoticesTool,
            new CreateNoticeTool,
            new UpdateNoticeTool,
            new ListEventsTool,
            new CreateEventTool,
            new UpdateEventTool,
            new ListHolidaysTool,
            new CreateHolidayTool,
            new UpdateHolidayTool,
        ];
    }
}
