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
 * Creates a NoticeBoard row — same notice_board table Receptionist ViewNoticeboardTool lists.
 * Tier-2: non-destructive create; reversible via update/status; no destroy in this batch.
 */
class CreateNoticeTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Create a school notice on the noticeboard (NoticeBoard / notice_board — shared with Receptionist view). Types: school, class, teacher.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Notice title')->required(),
            'description' => $schema->string()->description('Notice body')->required(),
            'type' => $schema->string()->description('school|class|teacher')->nullable(),
            'publish_date' => $schema->string()->description('Y-m-d')->nullable(),
            'expire_date' => $schema->string()->description('Y-m-d')->nullable(),
            'standardLink_id' => $schema->integer()->description('Required when type=class')->nullable(),
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
            'type' => $request->get('type') ?? 'school',
            'publish_date' => $request->get('publish_date'),
            'expire_date' => $request->get('expire_date'),
            'standardLink_id' => $request->get('standardLink_id'),
        ];

        $confirm = $this->confirmOrExecute('toolCreateNotice', $args,
            fn () => "Create notice: {$args['title']} (type={$args['type']})");
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolCommsActionService::createNotice($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
