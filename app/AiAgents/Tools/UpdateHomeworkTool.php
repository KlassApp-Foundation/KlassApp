<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\SchoolAcademicsOpsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateHomeworkTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Update school homework by id. Requires homework-manage Gate. No destroy.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'homework_id' => $schema->integer()->required(),
            'description' => $schema->string()->nullable(),
            'date' => $schema->string()->nullable(),
            'submission_date' => $schema->string()->nullable(),
            'standardLink_id' => $schema->integer()->nullable(),
            'subject_id' => $schema->integer()->nullable(),
            'teacher_id' => $schema->integer()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'homework_id' => $request->get('homework_id'),
            'description' => $request->get('description'),
            'date' => $request->get('date'),
            'submission_date' => $request->get('submission_date'),
            'standardLink_id' => $request->get('standardLink_id'),
            'subject_id' => $request->get('subject_id'),
            'teacher_id' => $request->get('teacher_id'),
        ];

        $confirm = $this->confirmOrExecute('toolUpdateHomework', $args,
            fn () => "Update homework #".$args['homework_id']);
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolAcademicsOpsActionService::updateHomework($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
