<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\SchoolAcademicsOpsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateHomeworkTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Create school homework (admin) with pending HomeworkApproval. Uses homework-manage Gate.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'standardLink_id' => $schema->integer()->required(),
            'subject_id' => $schema->integer()->required(),
            'description' => $schema->string()->required(),
            'date' => $schema->string()->description('Y-m-d')->required(),
            'submission_date' => $schema->string()->description('Y-m-d')->required(),
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
            'standardLink_id' => $request->get('standardLink_id'),
            'subject_id' => $request->get('subject_id'),
            'description' => $request->get('description'),
            'date' => $request->get('date'),
            'submission_date' => $request->get('submission_date'),
            'teacher_id' => $request->get('teacher_id'),
        ];

        $confirm = $this->confirmOrExecute('toolCreateHomework', $args,
            fn () => "Create homework: ".$args['description']);
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolAcademicsOpsActionService::createHomework($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
