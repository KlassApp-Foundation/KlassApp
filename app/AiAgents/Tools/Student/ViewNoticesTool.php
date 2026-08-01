<?php

namespace App\AiAgents\Tools\Student;

use App\AiAgents\Concerns\AuthorizesStudentToshiAction;
use App\Services\Toshi\StudentActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewNoticesTool implements Tool
{
    use AuthorizesStudentToshiAction;

    public function description(): Stringable|string
    {
        return 'List notices for the signed-in student school and class.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeStudentOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = StudentActionService::viewNotices($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
