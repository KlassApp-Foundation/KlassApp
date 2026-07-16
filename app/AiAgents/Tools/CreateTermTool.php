<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\AcademicTerm;

class CreateTermTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Create a new academic term. Provide name (e.g. "Term 1"), start date (Y-m-d), and end date (Y-m-d).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Term name, e.g. "Term 1"'),
            'start_date' => $schema->string()->description('Start date in Y-m-d format'),
            'end_date' => $schema->string()->description('End date in Y-m-d format'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = [
            'name' => $request->get('name'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $confirm = $this->confirmOrExecute('toolCreateTerm', $args,
            fn() => "Create term: {$args['name']} ({$args['start_date']} to {$args['end_date']})");
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::createTerm($user, $args);
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
            return ['verified' => false, 'message' => 'Term name is required for verification.'];
        }

        $exists = AcademicTerm::where('school_id', $schoolId)
            ->where('name', $name)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Term confirmed in database.'
                : 'Term was not found after creation.',
        ];
    }
}
