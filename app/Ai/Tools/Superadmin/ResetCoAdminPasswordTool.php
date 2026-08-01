<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Models\User;
use App\Services\Superadmin\CoAdminService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Resets a co-admin password. Approvable — credential change for another account.
 */
class ResetCoAdminPasswordTool implements Approvable, Tool
{
    use AuthorizesPlatformAction;
    use InteractsWithApprovals;

    public function __construct(private readonly CoAdminService $coAdmins) {}

    public function description(): Stringable|string
    {
        return 'Reset the password for a platform co-admin (usergroup_id=2) by user id. Requires human approval.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Co-admin user id')->required(),
            'password' => $schema->string()->description('New password (min 6)')->required(),
        ];
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        $id = $request['id'] ?? '?';
        $target = is_numeric($id) ? User::find((int) $id) : null;
        $label = $target
            ? "{$target->name} <{$target->email}> (id={$target->id})"
            : "user id={$id}";

        return Approval::required(
            "Reset password for co-admin {$label}. This changes login credentials for that account."
        );
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $target = $this->coAdmins->resetPassword((int) $request['id'], (string) $request['password']);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        }

        return "✅ Co-admin password reset: id={$target->id}, email={$target->email}.";
    }
}
