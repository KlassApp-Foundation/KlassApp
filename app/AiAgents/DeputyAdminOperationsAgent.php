<?php

namespace App\AiAgents;

use App\AiAgents\Tools\AddParentTool;
use App\AiAgents\Tools\AddStudentTool;
use App\AiAgents\Tools\AddTeacherTool;
use App\AiAgents\Tools\AssignTeacherTool;
use App\AiAgents\Tools\CreateEventTool;
use App\AiAgents\Tools\CreateExamTool;
use App\AiAgents\Tools\CreateFeeTool;
use App\AiAgents\Tools\CreateHolidayTool;
use App\AiAgents\Tools\CreateNoticeTool;
use App\AiAgents\Tools\CreateSubjectTool;
use App\AiAgents\Tools\CreateTermTool;
use App\AiAgents\Tools\EnterMarkTool;
use App\AiAgents\Tools\FindStudentTool;
use App\AiAgents\Tools\GenerateReportTool;
use App\AiAgents\Tools\GetFeeBalanceTool;
use App\AiAgents\Tools\GetStudentCountTool;
use App\AiAgents\Tools\ListClassesTool;
use App\AiAgents\Tools\ListEventsTool;
use App\AiAgents\Tools\ListHolidaysTool;
use App\AiAgents\Tools\ListNoticesTool;
use App\AiAgents\Tools\ListSectionsTool;
use App\AiAgents\Tools\ListTeachersTool;
use App\AiAgents\Tools\RecordAttendanceTool;
use App\AiAgents\Tools\RecordBulkAttendanceTool;
use App\AiAgents\Tools\RecordPaymentTool;
use App\AiAgents\Tools\SeedDefaultGradingTool;
use App\AiAgents\Tools\SetGradingScaleTool;
use App\AiAgents\Tools\UpdateEventTool;
use App\AiAgents\Tools\UpdateHolidayTool;
use App\AiAgents\Tools\UpdateNoticeTool;
use App\AiAgents\Tools\ViewGradingScaleTool;
use App\Models\School;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use App\AiAgents\Concerns\UsesToshiLlm;
use Laravel\Ai\Promptable;

/**
 * Deputy Admin–scoped Toshi operator (ug4 / SchoolSubadmin).
 *
 * Tool surface matches ToshiOrchestrator → skill agents (Student/Teacher/Academic/
 * Fee/Grading/Reporting) minus owner-level exclusions:
 *
 * - SetCurriculumTool — academic-year / curriculum Settings (owner config).
 * - AddCoAdminTool — not a Settings field (name/logo/plan/academic-year), but
 *   excluded as **owner-level governance**: creating ug3 co-admins is something
 *   a deputy should not do under “deputy shouldn’t make owner-level changes.”
 *
 * Structural isolation: those two tools are never registered here. TeacherSkill
 * on the ug3 path still includes AddCoAdminTool; SetCurriculumTool remains on
 * the AgentToshi confirm map for ug3. Shared tools dual-allow via
 * authorizeOrMessage() (toshi-school-action OR toshi-deputy-action); owner tools
 * use authorizeSchoolAdminOrMessage() (ug3-only).
 *
 * Deterministic scope router in ToshiSdkV2Service selects this agent for ug4 —
 * this is not Laravel AI Sub-Agents / CanActAsTool.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class DeputyAdminOperationsAgent implements Agent, HasTools
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
You are Toshi assisting a **deputy school administrator** at {$schoolName}.

You have school-operations tools: students, parents, teachers (add/assign/list — not co-admins), academics (classes, sections, terms, subjects, exams), fees, grading scales, attendance, marks, reports, and school communications (notices, events, holidays — list/create/update only).

Rules:
- Do **not** attempt owner-level changes: school Settings (curriculum / academic-year config), or creating co-admins (ug3).
- Prefer clear confirmations before writes.
- Be concise and confirm outcomes clearly.
PROMPT;
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            // Student skill surface
            new AddStudentTool,
            new FindStudentTool,
            new GetStudentCountTool,
            new RecordAttendanceTool,
            new RecordBulkAttendanceTool,
            new EnterMarkTool,
            new AddParentTool,
            // Teacher skill surface (minus AddCoAdminTool)
            new AddTeacherTool,
            new AssignTeacherTool,
            new ListTeachersTool,
            // Academic skill surface
            new ListClassesTool,
            new ListSectionsTool,
            new CreateTermTool,
            new CreateSubjectTool,
            new CreateExamTool,
            // Fee skill surface
            new CreateFeeTool,
            new RecordPaymentTool,
            new GetFeeBalanceTool,
            // Grading skill surface (SetCurriculumTool is orphan / Settings — excluded)
            new SetGradingScaleTool,
            new ViewGradingScaleTool,
            new SeedDefaultGradingTool,
            // Reporting skill surface
            new GenerateReportTool,
            // School Comms skill surface (Batch 1 — not Settings-adjacent)
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
