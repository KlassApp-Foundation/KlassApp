<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Subject;

class CreateSubjectTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Create a new subject. Provide name (required) and optionally a subject code.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Subject name, e.g. "Mathematics"'),
            'code' => $schema->string()->description('Subject code, e.g. "MATH"')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = $request->all();

        $confirm = $this->confirmOrExecute('toolCreateSubject', $args,
            fn() => "Create subject: {$args['name']}"
                . (!empty($args['code']) ? " ({$args['code']})" : ''));
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::createSubject($user, $args);
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
            return ['verified' => false, 'message' => 'Subject name is required for verification.'];
        }

        $exists = Subject::where('school_id', $schoolId)
            ->where('name', $name)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Subject confirmed in database.'
                : 'Subject was not found after creation.',
        ];
    }
}
