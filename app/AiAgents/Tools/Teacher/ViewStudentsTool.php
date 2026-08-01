<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewStudentsTool implements Tool
{
    use AuthorizesTeacherToshiAction;

    public function description(): Stringable|string
    {
        return 'List students associated with your teaching context.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [];

        $result = TeacherActionService::viewStudents($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
