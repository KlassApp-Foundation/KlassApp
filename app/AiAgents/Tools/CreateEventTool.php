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
 * Creates an Events row (non-holiday). Post-create checks Gate::allows('event') school_id.
 * Tier-2: create only; destroy stays portal event-destroy (post-#131).
 */
class CreateEventTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Create a school calendar event (not a holiday). Uses Events model with school_id scoping.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'description' => $schema->string()->nullable(),
            'start_date' => $schema->string()->description('Y-m-d or datetime')->required(),
            'end_date' => $schema->string()->description('Y-m-d or datetime')->required(),
            'category' => $schema->string()->description('culturals|education|exam|meeting — not holidays')->nullable(),
            'select_type' => $schema->string()->description('school|class|alumni')->nullable(),
            'standard_id' => $schema->integer()->description('Required when select_type=class')->nullable(),
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
            'description' => $request->get('description'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'category' => $request->get('category') ?? 'meeting',
            'select_type' => $request->get('select_type') ?? 'school',
            'standard_id' => $request->get('standard_id'),
        ];

        $confirm = $this->confirmOrExecute('toolCreateEvent', $args,
            fn () => "Create event: {$args['title']} ({$args['start_date']}–{$args['end_date']})");
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolCommsActionService::createEvent($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
