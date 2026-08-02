<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\SchoolCommsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Creates a holiday as Events.category=holidays (Admin HolidaysController pattern).
 * Tier-2: create only; uses event Gate for school_id after insert.
 */
class CreateHolidayTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Create a school holiday (stored as Events with category=holidays).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'date' => $schema->string()->description('Holiday date Y-m-d')->required(),
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
            'title' => $request->get('title'),
            'date' => $request->get('date'),
        ];

        $confirm = $this->confirmOrExecute('toolCreateHoliday', $args,
            fn () => "Create holiday: {$args['title']} on {$args['date']}");
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolCommsActionService::createHoliday($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
