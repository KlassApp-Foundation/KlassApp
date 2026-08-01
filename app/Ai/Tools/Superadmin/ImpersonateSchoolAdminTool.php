<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Models\User;
use App\Services\Superadmin\ImpersonationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Start school-admin impersonation for the authenticated superadmin.
 * Approvable is mandatory — never withoutApproval(). Reason names the target account.
 */
class ImpersonateSchoolAdminTool implements Approvable, Tool
{
    use AuthorizesPlatformAction;
    use InteractsWithApprovals;

    public function __construct(private readonly ImpersonationService $impersonation) {}

    public function description(): Stringable|string
    {
        return 'Impersonate a school admin (usergroup_id=3) by user id. Requires human approval. Starts a session impersonation (session key impersonate).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('School admin user id to impersonate')->required(),
        ];
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        $id = $request['user_id'] ?? '?';
        $target = is_numeric($id) ? User::find((int) $id) : null;
        $label = $target
            ? "{$target->name} <{$target->email}> (id={$target->id}, school_id=".($target->school_id ?? 'null').')'
            : "user id={$id}";

        return Approval::required(
            "Impersonate school admin account {$label}. This starts a live impersonation session as that user."
        );
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $target = $this->impersonation->startSchoolAdminImpersonation(
                $user,
                (int) $request['user_id']
            );
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        }

        return "✅ Impersonation started for school admin id={$target->id} ({$target->email}). Session key impersonate={$target->id}. Navigate to /admin/dashboard; stop via /teacher/impersonate/stop.";
    }
}
