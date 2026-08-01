<?php

namespace App\AiAgents\Tools\Librarian;

use App\AiAgents\Concerns\AuthorizesLibrarianToshiAction;
use App\Services\Toshi\LibrarianActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/** Read-only library card lookup (status / limit / expiry). No issue/return/create/update. */
class ViewLibraryCardsTool implements Tool
{
    use AuthorizesLibrarianToshiAction;

    public function description(): Stringable|string
    {
        return 'Look up a school library card by student_id or library_card_no. Returns status, book limit, expiry, and active lend count. View-only — cannot create or edit cards.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'student_id' => $schema->integer()->nullable(),
            'library_card_no' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeLibrarianOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = LibrarianActionService::viewLibraryCards($user, [
            'student_id' => $request->get('student_id'),
            'library_card_no' => $request->get('library_card_no'),
        ]);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
