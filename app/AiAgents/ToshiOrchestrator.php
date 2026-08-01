<?php

namespace App\AiAgents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Enums\Lab;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\AiAgents\Tools\RouteToStudentSkillTool;
use App\AiAgents\Tools\RouteToTeacherSkillTool;
use App\AiAgents\Tools\RouteToAcademicSkillTool;
use App\AiAgents\Tools\RouteToFeeSkillTool;
use App\AiAgents\Tools\RouteToGradingSkillTool;
use App\AiAgents\Tools\RouteToReportingSkillTool;

#[MaxSteps(3)]
#[Timeout(120)]
class ToshiOrchestrator implements Agent, HasTools
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
        $context = $this->buildSchoolContext();

        $countryLabel = $this->getSchoolCountryLabel();

        return <<<PROMPT
You are Toshi, the intelligent AI assistant for KlassApp — a school management system for {$countryLabel} schools.

{$context}

Your job is to classify the user query into EXACTLY ONE domain and route it to the right skill.

**Available skills:**

1. **student** — Adding/finding students, attendance (single & bulk), exam marks, parents, enrollment counts
2. **teacher** — Adding teachers, assigning to subjects/classes, promoting to co-admin, listing teachers
3. **academic** — Listing classes & sections, creating terms, subjects, and exams
4. **fee** — Creating fee categories, recording payments, checking fee balances
5. **grading** — Setting/viewing grading scales, seeding default UNEB scales
6. **report** — Generating academic, fee, attendance, and summary reports

**Rules:**
- If the query clearly matches a domain, route it immediately using the corresponding tool.
- If the query could match multiple domains, pick the most relevant ONE. Do not route to multiple skills.
- If you truly cannot determine the domain, ask ONE clarifying question.
- Do NOT answer the query directly — always route it.
- Pass the **user's full original query** as the "query" parameter to the routing tool.

Example: "add a student named John in Primary Four" → routeToStudentSkill
Example: "create a term called Term 1" → routeToAcademicSkill
Example: "record a payment of 50k for student 42" → routeToFeeSkill
PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new RouteToStudentSkillTool,
            new RouteToTeacherSkillTool,
            new RouteToAcademicSkillTool,
            new RouteToFeeSkillTool,
            new RouteToGradingSkillTool,
            new RouteToReportingSkillTool,
        ];
    }

    /**
     * Build minimal school context for the orchestrator's system prompt.
     */
    private function buildSchoolContext(): string
    {
        $user = auth()->user() ?? request()->user();
        if (!$user) {
            return 'You are helping a platform administrator.';
        }

        $schoolId = $user->school_id;
        if (!$schoolId) {
            return 'You are helping a platform-level administrator with no specific school context.';
        }

        $school = School::find($schoolId);
        if (!$school) {
            return 'Current school: Unknown (ID: ' . $schoolId . '). User role: ' . $this->getUserRole($user) . '.';
        }

        $parts = [];
        $parts[] = 'School: **' . $school->name . '** (ID: ' . $schoolId . ')';
        $parts[] = 'Country: **' . ($school->registration_country ?: 'Uganda') . '**';
        $parts[] = 'Curriculum: **' . ($school->curriculum ?: 'UNEB') . '**';
        $parts[] = 'User role: **' . $this->getUserRole($user) . '**';

        // Add grading-scale context if configured
        $standardCount = SchoolGradingSystem::where('school_id', $schoolId)
            ->distinct('standard_id')
            ->count('standard_id');
        if ($standardCount > 0) {
            $parts[] = 'Grading scales configured for **' . $standardCount . '** class(es). Use the grading skill to view or modify them.';
        } else {
            $parts[] = 'No grading scales configured yet. Use the grading skill to seed default UNEB scales.';
        }

        return implode(' | ', $parts) . '.';
    }

    /**
     * Get a human-readable country label for the system prompt.
     */
    private function getSchoolCountryLabel(): string
    {
        $user = auth()->user() ?? request()->user();
        if (!$user || !$user->school_id) {
            return 'Ugandan';
        }

        $school = School::find($user->school_id);
        $country = $school ? $school->registration_country : null;

        if (!$country) {
            return 'Ugandan';
        }

        // Map common country names to adjective form
        $adjectives = [
            'Uganda'       => 'Ugandan',
            'Kenya'        => 'Kenyan',
            'Tanzania'     => 'Tanzanian',
            'Rwanda'       => 'Rwandan',
            'Burundi'      => 'Burundian',
            'South Sudan'  => 'South Sudanese',
            'Nigeria'      => 'Nigerian',
            'Ghana'        => 'Ghanaian',
            'South Africa' => 'South African',
        ];

        return $adjectives[$country] ?? $country;
    }

    private function getUserRole(\App\Models\User $user): string
    {
        return match ($user->usergroup_id) {
            1 => 'Super Admin',
            3 => 'School Admin',
            4 => 'Deputy Admin',
            5 => 'Teacher',
            6 => 'Student',
            7 => 'Parent',
            default => 'User',
        };
    }

    /**
     * Convenience method to run the orchestrator from the Livewire component.
     * Returns the skill's response text or a fallback message.
     */
    public function run(string $query): ?string
    {
        try {
            $response = $this->prompt($query);
            return $response->text;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ToshiOrchestrator failed', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);
            return null;
        }
    }
}
