<?php

namespace App\AiAgents\Tools\Librarian;

use App\AiAgents\Concerns\AuthorizesLibrarianToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\LibrarianActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ManageLendingTool implements Tool
{
    use AuthorizesLibrarianToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Issue (check out) a book lend using library_card_no and book_code_no. Optional issue_date (Y-m-d).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'library_card_no' => $schema->string()->required(),
            'book_code_no' => $schema->string()->required(),
            'issue_date' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeLibrarianOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'library_card_no' => $request->get('library_card_no'),
            'book_code_no' => $request->get('book_code_no'),
            'issue_date' => $request->get('issue_date'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolLibrarianManageLending',
            $args,
            fn () => "Issue book {$args['book_code_no']} on card {$args['library_card_no']}"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = LibrarianActionService::manageLending($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
