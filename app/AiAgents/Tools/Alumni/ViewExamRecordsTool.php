<?php

namespace App\AiAgents\Tools\Alumni;

use App\AiAgents\Concerns\AuthorizesAlumniToshiAction;
use App\Services\Toshi\AlumniActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewExamRecordsTool implements Tool
{
    use AuthorizesAlumniToshiAction;

    public function description(): Stringable|string
    {
        return "The signed-in alumni's own exam records (subject, exam, mark, grade). Read-only; never accepts a student id.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeAlumniOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = AlumniActionService::examRecords($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
