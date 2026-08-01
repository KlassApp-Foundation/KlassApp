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
 * Soft-deletes a co-admin. Approvable — destructive account removal.
 */
class DeleteCoAdminTool implements Approvable, Tool
{
    use AuthorizesPlatformAction;
    use InteractsWithApprovals;

    public function __construct(private readonly CoAdminService $coAdmins) {}

    public function description(): Stringable|string
    {
        return 'Soft-delete a platform co-admin by user id. Requires human approval — permanently removes the account from active lists.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Co-admin user id')->required(),
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
            "Soft-delete co-admin {$label}. This removes the account from active co-admin lists."
        );
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $target = $this->coAdmins->delete((int) $request['id'], $user);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        }

        return "✅ Co-admin soft-deleted: id={$target->id}, email={$target->email}.";
    }
}
