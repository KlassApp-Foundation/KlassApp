<?php

namespace App\AiAgents\Tools\Librarian;

use App\AiAgents\Concerns\AuthorizesLibrarianToshiAction;
use App\Services\Toshi\LibrarianActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewDashboardTool implements Tool
{
    use AuthorizesLibrarianToshiAction;

    public function description(): Stringable|string
    {
        return 'Show a librarian dashboard summary (books, categories, card holders, active lends, pending tasks).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeLibrarianOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = LibrarianActionService::viewDashboard($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
