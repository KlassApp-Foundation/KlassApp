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
 * Updates an Events row via post-#131 Gate::allows('event') school_id check.
 * Does not use event-destroy; no destroy in this batch. Tier-2: metadata edit.
 */
class UpdateEventTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Update a school calendar event by id. Authorization uses the event Gate (school_id match, post-#131). Cannot update holidays — use update holiday.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'event_id' => $schema->integer()->required(),
            'title' => $schema->string()->nullable(),
            'description' => $schema->string()->nullable(),
            'start_date' => $schema->string()->nullable(),
            'end_date' => $schema->string()->nullable(),
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
            'event_id' => $request->get('event_id'),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $confirm = $this->confirmOrExecute('toolUpdateEvent', $args,
            fn () => "Update event #{$args['event_id']}");
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolCommsActionService::updateEvent($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
