<?php

namespace App\AiAgents\Tools\Librarian;

use App\AiAgents\Concerns\AuthorizesLibrarianToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\LibrarianActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ManageBooksTool implements Tool
{
    use AuthorizesLibrarianToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Add a book to the school library catalogue (title, category_id, book_code; optional author, isbn_number, quantity).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'category_id' => $schema->integer()->required(),
            'book_code' => $schema->string()->required(),
            'author' => $schema->string()->nullable(),
            'isbn_number' => $schema->string()->nullable(),
            'quantity' => $schema->integer()->nullable(),
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
            'title' => $request->get('title'),
            'category_id' => $request->get('category_id'),
            'book_code' => $request->get('book_code'),
            'author' => $request->get('author'),
            'isbn_number' => $request->get('isbn_number'),
            'quantity' => $request->get('quantity'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolLibrarianManageBooks',
            $args,
            fn () => "Add book: {$args['title']} (code {$args['book_code']})"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = LibrarianActionService::manageBooks($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
