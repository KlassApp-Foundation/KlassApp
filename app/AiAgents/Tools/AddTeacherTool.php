<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\User;

class AddTeacherTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Add a new teacher to the school. Provide name (required), and optionally: email, phone, subjects (comma-separated), classes (comma-separated).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Teacher full name'),
            'email' => $schema->string()->description('Teacher email address')->nullable(),
            'phone' => $schema->string()->description('Phone number starting with +256')->nullable(),
            'subjects' => $schema->string()->description('Comma-separated subject names')->nullable(),
            'classes' => $schema->string()->description('Comma-separated class names')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = $request->all();

        $confirm = $this->confirmOrExecute('toolAddTeacher', $args,
            fn() => "Add teacher: {$args['name']}"
                . (!empty($args['email']) ? ", email: {$args['email']}" : '')
                . (!empty($args['phone']) ? ", phone: {$args['phone']}" : ''));
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::addTeacher($user, $args);
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
            return ['verified' => false, 'message' => 'Teacher email is required for verification.'];
        }

        $exists = User::where('school_id', $schoolId)
            ->where('usergroup_id', 5)
            ->where('email', $email)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Teacher record confirmed in database.'
                : 'Teacher was not found after creation.',
        ];
    }
}
