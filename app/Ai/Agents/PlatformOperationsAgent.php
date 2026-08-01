<?php

namespace App\Ai\Agents;

use App\Ai\Tools\Superadmin\ApproveSubscriptionTool;
use App\Ai\Tools\Superadmin\CancelSubscriptionTool;
use App\Ai\Tools\Superadmin\CreateCityTool;
use App\Ai\Tools\Superadmin\CreateCountryTool;
use App\Ai\Tools\Superadmin\CreatePlanTool;
use App\Ai\Tools\Superadmin\CreateSchoolTool;
use App\Ai\Tools\Superadmin\CreateSubscriptionTool;
use App\Ai\Tools\Superadmin\ToggleSchoolFeatureTool;
use App\Ai\Tools\Superadmin\UpdateCityTool;
use App\Ai\Tools\Superadmin\UpdateCountryTool;
use App\Ai\Tools\Superadmin\UpdatePlanTool;
use App\Ai\Tools\Superadmin\UpdateSchoolTool;
use App\Ai\Tools\Superadmin\UpdateSystemSettingsTool;
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
 * Selected by ToshiSdkV2Service scope router when ToshiScope::Platform — not an
 * SDK Sub-Agent (CanActAsTool). Conversational + RemembersConversations required
 * for native laravel/ai HITL approval pause/resume.
 *
 * Phase 1: Geo + Plans + Schools + Subscriptions + FeatureToggles + SystemSettings.
 * Stopped before CoAdmins / Impersonation.
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
2. **Plans** — create/update billing plans (require human approval)
3. **Schools** — create/update schools (superadmin form path, not full onboarding)
4. **Subscriptions** — create (immediate); approve/cancel (require human approval)
5. **Feature toggles** — enable/disable toshi/whatsapp/schoolpay per school (require approval)
6. **System settings** — update display or access settings (access keys require approval)

Rules:
- Use tools for mutations; do not invent IDs.
- Ask for missing required fields before calling a tool.
- Approvable tools pause for human approval — tell the user approval is required.
- Do NOT attempt co-admins or impersonation — those are not available yet.
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
            app(CreateSubscriptionTool::class),
            app(ApproveSubscriptionTool::class),
            app(CancelSubscriptionTool::class),
            app(ToggleSchoolFeatureTool::class),
            app(UpdateSystemSettingsTool::class),
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
