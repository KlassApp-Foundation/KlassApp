<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\User;

class AddCoAdminTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Add a new co-admin for the school. Provide a name and email. The co-admin will get a login with a default password.';
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
        $error = $this->authorizeOrMessage($user);
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

        $exists = User::where('school_id', $schoolId)
            ->where('usergroup_id', 3)
            ->where('email', $email)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Co-admin record confirmed in database.'
                : 'Co-admin was not found after creation.',
        ];
    }
}
