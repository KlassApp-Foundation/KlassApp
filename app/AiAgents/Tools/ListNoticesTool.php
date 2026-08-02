<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Services\Toshi\SchoolCommsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Read-only list of NoticeBoard rows (same notice_board table as Receptionist ViewNoticeboardTool).
 */
class ListNoticesTool implements Tool
{
    use AuthorizesToshiAction;

    public function description(): Stringable|string
    {
        return 'List school noticeboard items (NoticeBoard / notice_board — same source Receptionist view_noticeboard reads). Create/update via create/update notice tools.';
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

        $limit = (int) ($request->get('limit') ?? 20);
        $result = SchoolCommsActionService::listNotices($user, $limit);

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
