<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Services\Superadmin\SystemSettingsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Update global system settings (settings table).
 *
 * Approvable when touching maintenance / login_status / register_status.
 * Purely cosmetic keys (sitetitle, sitename, sitelogo, favicon) skip HITL.
 */
class UpdateSystemSettingsTool implements Approvable, Tool
{
    use AuthorizesPlatformAction;
    use InteractsWithApprovals;

    public function __construct(private readonly SystemSettingsService $settings) {}

    public function description(): Stringable|string
    {
        return 'Update global platform system settings. Pass only keys to change: sitetitle, sitename, sitelogo, favicon (display — no approval), or maintenance, login_status, register_status (access — requires approval).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sitetitle' => $schema->string()->nullable(),
            'sitename' => $schema->string()->nullable(),
            'sitelogo' => $schema->string()->nullable(),
            'favicon' => $schema->string()->nullable(),
            'maintenance' => $schema->string()->description('0=off, 1=on')->nullable(),
            'login_status' => $schema->string()->description('1=enabled, 0=disabled')->nullable(),
            'register_status' => $schema->string()->description('1=open, 0=closed')->nullable(),
        ];
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        $data = $this->requestPayload($request);

        if (! $this->settings->touchesAccessControls($data)) {
            return false;
        }

        $touched = collect(SystemSettingsService::ACCESS_KEYS)
            ->filter(fn (string $key) => array_key_exists($key, $data) && $data[$key] !== null)
            ->map(fn (string $key) => "{$key}={$data[$key]}")
            ->implode(', ');

        return Approval::required(
            "Update access/public settings ({$touched}). Affects login, registration, or maintenance mode."
        );
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $written = $this->settings->save($this->requestPayload($request));
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        }

        $summary = collect($written)->map(fn ($v, $k) => "{$k}={$v}")->implode(', ');

        return "✅ System settings updated: {$summary}.";
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(Request $request): array
    {
        $data = [];
        foreach (SystemSettingsService::KEYS as $key) {
            if ($request->offsetExists($key) && $request[$key] !== null) {
                $data[$key] = $request[$key];
            }
        }

        return $data;
    }
}
