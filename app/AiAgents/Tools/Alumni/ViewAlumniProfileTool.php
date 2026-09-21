<?php

namespace App\AiAgents\Tools\Alumni;

use App\AiAgents\Concerns\AuthorizesAlumniToshiAction;
use App\Services\Toshi\AlumniActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewAlumniProfileTool implements Tool
{
    use AuthorizesAlumniToshiAction;

    public function description(): Stringable|string
    {
        return "The signed-in alumni's own profile details (name, contact, LIN, school).";
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

        $result = AlumniActionService::profile($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
