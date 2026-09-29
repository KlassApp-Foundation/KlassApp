<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\CoAdminInvite;
use App\Models\User;

class AddCoAdminTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Add a new co-admin for the school. Provide a name and email. The co-admin will receive an invite link to set their own password.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Co-admin full name'),
            'email' => $schema->string()->description('Co-admin email address (must not already exist)'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        // Owner-level governance (creating ug3 co-admins) — school admin only, not deputies.
        $error = $this->authorizeSchoolAdminOrMessage($user);
        if ($error) return $error;

        $args = [
            'name' => $request->get('name'),
            'email' => $request->get('email'),
        ];

        $confirm = $this->confirmOrExecute('toolAddCoAdmin', $args,
            fn() => "Add co-admin: {$args['name']}, email: {$args['email']}");
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::addCoAdmin($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $email = trim($request->get('email', ''));
        if ($email === '') {
            return ['verified' => false, 'message' => 'Co-admin email is required for verification.'];
        }

        $userExists = User::where('school_id', $schoolId)
            ->where('usergroup_id', 3)
            ->where('email', $email)
            ->exists();

        $inviteExists = CoAdminInvite::where('school_id', $schoolId)
            ->where('email', mb_strtolower(trim($email)))
            ->whereNull('claimed_at')
            ->exists();

        return [
            'verified' => $userExists || $inviteExists,
            'message' => $userExists
                ? 'Co-admin record confirmed in database.'
                : ($inviteExists
                    ? 'Co-admin invite confirmed — awaiting password setup.'
                    : 'Co-admin was not found after creation.'),
        ];
    }
}
