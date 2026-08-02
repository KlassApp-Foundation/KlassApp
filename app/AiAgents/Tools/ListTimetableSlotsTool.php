<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Services\Toshi\SchoolAcademicsOpsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListTimetableSlotsTool implements Tool
{
    use AuthorizesToshiAction;

    public function description(): Stringable|string
    {
        return 'List timetable slots for the school (optional section filter). No destroy.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->description('Optional section filter')->nullable(),
            'limit' => $schema->integer()->description('Max rows (1–50)')->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = SchoolAcademicsOpsActionService::listTimetableSlots($user, [
            'section_id' => $request->get('section_id'),
            'limit' => $request->get('limit') ?? 30,
        ]);

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
