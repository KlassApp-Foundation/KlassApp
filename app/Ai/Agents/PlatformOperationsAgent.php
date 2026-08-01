<?php

namespace App\Ai\Agents;

use App\Ai\Tools\Superadmin\CreateCityTool;
use App\Ai\Tools\Superadmin\CreateCountryTool;
use App\Ai\Tools\Superadmin\CreatePlanTool;
use App\Ai\Tools\Superadmin\CreateSchoolTool;
use App\Ai\Tools\Superadmin\UpdateCityTool;
use App\Ai\Tools\Superadmin\UpdateCountryTool;
use App\Ai\Tools\Superadmin\UpdatePlanTool;
use App\Ai\Tools\Superadmin\UpdateSchoolTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Platform-scope Toshi agent (siteadmin).
 *
 * Phase 1 partial: Geo + Plans + Schools only.
 * Stopped before Subscriptions, FeatureToggles, SystemSettings, CoAdmins, Impersonation.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class PlatformOperationsAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function provider(): string
    {
        return 'openai-compatible';
    }

    public function model(): string
    {
        return config('toshi.model', 'deepseek-chat');
    }

    public function instructions(): string
    {
        return <<<'PROMPT'
You are Toshi operating in **platform** scope for KlassApp superadmins.

You can manage:
1. **Geo** — create/update countries and cities
2. **Plans** — create/update billing plans (these require human approval before write)
3. **Schools** — create/update schools (superadmin form path, not full onboarding)

Rules:
- Use tools for mutations; do not invent IDs.
- Ask for missing required fields before calling a tool.
- Plan create/update will pause for human approval — tell the user approval is required.
- Do NOT attempt subscriptions, feature toggles, system settings, co-admins, or impersonation — those are not available yet.
PROMPT;
    }

    public function tools(): iterable
    {
        return [
            app(CreateCountryTool::class),
            app(UpdateCountryTool::class),
            app(CreateCityTool::class),
            app(UpdateCityTool::class),
            app(CreatePlanTool::class),
            app(UpdatePlanTool::class),
            app(CreateSchoolTool::class),
            app(UpdateSchoolTool::class),
        ];
    }

    /**
     * Convenience for ToshiSdkV2Service (mirrors ToshiOrchestrator::run).
     */
    public function run(string $query): ?string
    {
        try {
            return $this->prompt($query)->text;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PlatformOperationsAgent failed', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }
}
