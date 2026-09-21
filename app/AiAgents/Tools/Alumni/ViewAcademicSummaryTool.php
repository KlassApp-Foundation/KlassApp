<?php

namespace App\AiAgents\Tools\Alumni;

use App\AiAgents\Concerns\AuthorizesAlumniToshiAction;
use App\Services\Toshi\AlumniActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewAcademicSummaryTool implements Tool
{
    use AuthorizesAlumniToshiAction;

    public function description(): Stringable|string
    {
        return "The signed-in alumni's own academic summary: exam record count, subjects taken, graduation year.";
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

        $result = AlumniActionService::academicSummary($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
