<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Services\Toshi\SchoolCommsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/** Read-only list of Events with category=holidays. */
class ListHolidaysTool implements Tool
{
    use AuthorizesToshiAction;

    public function description(): Stringable|string
    {
        return 'List school holidays (Events rows with category=holidays).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
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

        $result = SchoolCommsActionService::listHolidays($user, (int) ($request->get('limit') ?? 20));

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
