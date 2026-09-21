<?php

namespace App\AiAgents\Tools\Alumni;

use App\AiAgents\Concerns\AuthorizesAlumniToshiAction;
use App\Services\Toshi\AlumniActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewAlumniDirectoryTool implements Tool
{
    use AuthorizesAlumniToshiAction;

    public function description(): Stringable|string
    {
        return "The alumni directory for the signed-in alumni's own school (names of other graduates). Never returns another graduate's records, marks, fees or contact details.";
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

        $result = AlumniActionService::directory($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
