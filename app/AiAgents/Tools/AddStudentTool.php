<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\User;

class AddStudentTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Add a new student to the school. Provide name (required), class/standard (required), and optionally stream, type, parent name, parent phone.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Student full name'),
            'class' => $schema->string()->description('Class/standard name, e.g. "Primary Four" or "Senior One"'),
            'stream' => $schema->string()->description('Stream/section name, e.g. "A" or "Science"')->nullable(),
            'type' => $schema->string()->description('Student type (regular, boarder, day)')->nullable(),
            'parent' => $schema->string()->description('Parent/guardian full name')->nullable(),
            'parentPhone' => $schema->string()->description('Parent phone number starting with +256')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = $request->all();

        $confirm = $this->confirmOrExecute('toolAddStudent', $args,
            fn() => "Add student: {$args['name']} to {$args['class']}"
                . (!empty($args['stream']) ? ", stream: {$args['stream']}" : '')
                . (!empty($args['parent']) ? ", parent: {$args['parent']}" : ''));
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::addStudent($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $name = trim($request->get('name', ''));
        if ($name === '') {
            return ['verified' => false, 'message' => 'Student name is required for verification.'];
        }

        $exists = User::where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('name', $name)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Student record confirmed in database.'
                : 'Student was not found after creation.',
        ];
    }
}
