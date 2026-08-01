<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Services\FeatureToggleService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Toggle a per-school feature (toshi / whatsapp / schoolpay).
 * Approvable — features affect access control and paid integrations.
 */
class ToggleSchoolFeatureTool implements Approvable, Tool
{
    use AuthorizesPlatformAction;
    use InteractsWithApprovals;

    public function description(): Stringable|string
    {
        $features = implode(', ', array_keys(FeatureToggleService::availableFeatures()));

        return "Enable or disable a school feature toggle ({$features}). Requires human approval — affects school access and integrations.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'school_id' => $schema->integer()->description('School id')->required(),
            'feature' => $schema->string()->description('toshi|whatsapp|schoolpay')->required(),
            'enabled' => $schema->boolean()->description('true to enable, false to disable')->required(),
        ];
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        $schoolId = $request['school_id'] ?? '?';
        $feature = $request['feature'] ?? '?';
        $enabled = ($request['enabled'] ?? false) ? 'enable' : 'disable';
        $label = FeatureToggleService::availableFeatures()[$feature] ?? $feature;

        return Approval::required(
            "{$enabled} feature \"{$label}\" ({$feature}) for school_id={$schoolId}. Affects school access/integrations."
        );
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        $result = FeatureToggleService::setEnabled(
            (int) $request['school_id'],
            (string) $request['feature'],
            (bool) $request['enabled']
        );

        if (! ($result['success'] ?? false)) {
            return '❌ '.($result['message'] ?? 'Feature toggle failed.');
        }

        $state = $request['enabled'] ? 'enabled' : 'disabled';

        return "✅ Feature {$request['feature']} {$state} for school_id={$request['school_id']}.";
    }
}
