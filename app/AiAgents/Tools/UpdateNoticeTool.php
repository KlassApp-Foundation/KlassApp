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
 * Updates a NoticeBoard row (school_id scoped). Same table as Receptionist view_noticeboard.
 * Tier-2: metadata edit only; no destroy in this batch.
 */
class UpdateNoticeTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Update an existing noticeboard item (NoticeBoard / notice_board) by id within the school.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'notice_id' => $schema->integer()->description('NoticeBoard id')->required(),
            'title' => $schema->string()->nullable(),
            'description' => $schema->string()->nullable(),
            'expire_date' => $schema->string()->description('Y-m-d')->nullable(),
            'status' => $schema->integer()->description('1=active, 0=inactive')->nullable(),
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
            'notice_id' => $request->get('notice_id'),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'expire_date' => $request->get('expire_date'),
            'status' => $request->get('status'),
        ];

        $confirm = $this->confirmOrExecute('toolUpdateNotice', $args,
            fn () => "Update notice #{$args['notice_id']}");
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolCommsActionService::updateNotice($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
