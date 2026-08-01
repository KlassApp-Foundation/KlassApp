<?php

namespace App\AiAgents\WhatsApp;

use App\AiAgents\Tools\FindStudentTool;
use App\AiAgents\Tools\GenerateReportTool;
use App\AiAgents\Tools\GetFeeBalanceTool;
use App\AiAgents\Tools\GetStudentCountTool;
use App\AiAgents\Tools\ListClassesTool;
use App\AiAgents\Tools\ListSectionsTool;
use App\AiAgents\Tools\ListTeachersTool;
use App\AiAgents\Tools\ViewGradingScaleTool;
use App\Models\School;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * School-admin (ug3) WhatsApp read surface.
 *
 * ToshiOrchestrator skill routers embed write tools, so WhatsApp uses this
 * flat read-only agent instead of the orchestrator for structural write exclusion.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class SchoolAdminWhatsAppReadAgent implements Agent, HasTools
{
    use Promptable;

    public function provider(): string
    {
        return 'openai-compatible';
    }

    public function model(): string
    {
        return config('toshi.model', 'nvidia/llama-3.3-nemotron-super-49b-v1');
    }

    public function instructions(): string
    {
        $user = auth()->user() ?? request()->user();
        $schoolName = 'your school';
        if ($user?->school_id) {
            $schoolName = School::find($user->school_id)?->name ?? $schoolName;
        }

        return <<<PROMPT
You are Toshi assisting a **school administrator** at {$schoolName} over WhatsApp.

You may only use read tools: find students, count students, list classes/sections/teachers, fee balance lookups, view grading scales, and generate reports.

Rules:
- This WhatsApp channel is read-only — do not attempt creates, payments, attendance writes, or co-admin changes.
- Be concise and format for WhatsApp.
PROMPT;
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new FindStudentTool,
            new GetStudentCountTool,
            new ListClassesTool,
            new ListSectionsTool,
            new ListTeachersTool,
            new GetFeeBalanceTool,
            new ViewGradingScaleTool,
            new GenerateReportTool,
        ];
    }

    public function run(string $query): ?string
    {
        try {
            return $this->prompt($query)->text;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SchoolAdminWhatsAppReadAgent failed', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }
}
