<?php

namespace App\AiAgents;

use App\AiAgents\Tools\Parent\AttendanceTool;
use App\AiAgents\Tools\Parent\EventsTool;
use App\AiAgents\Tools\Parent\FeeBalanceTool;
use App\AiAgents\Tools\Parent\GradesTool;
use App\AiAgents\Tools\Parent\HealthTool;
use App\AiAgents\Tools\Parent\ListChildrenTool;
use App\Models\School;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Parent-scoped Toshi operator (ug7) — WhatsApp / children-scope reads.
 *
 * Structural isolation: tools() lists only parent read actions.
 * Every tool resolves children from the authenticated parent's links —
 * never LLM-supplied student_id.
 * Deterministic scope router in ToshiSdkV2Service / WhatsAppToshiChannelService
 * selects this agent for ug7.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class ParentOperationsAgent implements Agent, HasTools
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
You are Toshi assisting a **parent** at {$schoolName} over WhatsApp.

You may only use parent read tools: list children, fee balance, grades, attendance, health records, and school events.

Rules:
- Children are those linked to this WhatsApp account only. Never ask for or accept a student_id.
- If the parent has more than one child and the request is ambiguous, ask which child (by name) or call list_children.
- Do not attempt school-admin, teacher, accountant, librarian, receptionist, or student self-scope actions.
- This channel is read-only — do not attempt payments, linking, or other writes (those stay on keyword flows / web).
- Be concise; format answers for WhatsApp (short paragraphs).
PROMPT;
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new ListChildrenTool,
            new FeeBalanceTool,
            new GradesTool,
            new AttendanceTool,
            new HealthTool,
            new EventsTool,
        ];
    }

    /**
     * Convenience runner (mirrors ToshiOrchestrator::run).
     */
    public function run(string $query): ?string
    {
        try {
            $response = $this->prompt($query);

            return $response->text;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ParentOperationsAgent failed', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }
}
