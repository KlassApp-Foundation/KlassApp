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
 * Updates a holiday Events row via Gate::allows('event') school_id (post-#131).
 * Tier-2: metadata edit; no destroy.
 */
class UpdateHolidayTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Update a school holiday (Events category=holidays) by id. Uses event Gate for school_id authorization.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'holiday_id' => $schema->integer()->required(),
            'title' => $schema->string()->nullable(),
            'date' => $schema->string()->description('Y-m-d')->nullable(),
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
            'holiday_id' => $request->get('holiday_id'),
            'title' => $request->get('title'),
            'date' => $request->get('date'),
        ];

        $confirm = $this->confirmOrExecute('toolUpdateHoliday', $args,
            fn () => "Update holiday #{$args['holiday_id']}");
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolCommsActionService::updateHoliday($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
