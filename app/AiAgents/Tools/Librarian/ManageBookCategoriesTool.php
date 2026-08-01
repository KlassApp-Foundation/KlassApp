<?php

namespace App\AiAgents\Tools\Librarian;

use App\AiAgents\Concerns\AuthorizesLibrarianToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\LibrarianActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ManageBookCategoriesTool implements Tool
{
    use AuthorizesLibrarianToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Add a book category to the school library.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()->required(),
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
            'category' => $request->get('category'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolLibrarianManageBookCategories',
            $args,
            fn () => "Add book category: {$args['category']}"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = LibrarianActionService::manageBookCategories($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
