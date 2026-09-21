<?php

namespace App\AiAgents;

use App\AiAgents\Tools\Alumni\ViewAcademicSummaryTool;
use App\AiAgents\Tools\Alumni\ViewAlumniDirectoryTool;
use App\AiAgents\Tools\Alumni\ViewAlumniProfileTool;
use App\AiAgents\Tools\Alumni\ViewExamRecordsTool;
use App\Models\School;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use App\AiAgents\Concerns\UsesToshiLlm;
use Laravel\Ai\Promptable;

/**
 * Alumni-scoped Toshi operator (ug9) — self-scope reads of their own records.
 *
 * Structural isolation: tools() lists only the four alumni read actions.
 * Every tool resolves identity from auth()->user() — never an LLM-supplied
 * student/alumni id. Deterministic scope router in ToshiSdkV2Service selects
 * this agent for ug9.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class AlumniOperationsAgent implements Agent, HasTools
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
You are Toshi assisting a **former student (alumni)** at {$schoolName}.

You may only use alumni read tools: their own exam records, their own academic summary, their own profile, and the alumni directory of their own school.

Rules:
- All records are for the signed-in alumni only. Never ask for or accept a student id, alumni id or another person's name to look up records.
- The directory lists other graduates of the same school (names only) — never return anyone else's marks, fees, contact details or any private record.
- Do not attempt school-admin, teacher, accountant, librarian, receptionist, parent or student actions.
- This scope is read-only — no writes.
- Be concise; format answers for a short dashboard chat panel.
PROMPT;
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new ViewExamRecordsTool,
            new ViewAcademicSummaryTool,
            new ViewAlumniProfileTool,
            new ViewAlumniDirectoryTool,
        ];
    }

    /** Convenience runner (mirrors ParentOperationsAgent). */
    public function run(string $query): ?string
    {
        try {
            return $this->prompt($query)->text;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AlumniOperationsAgent failed', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }
}
