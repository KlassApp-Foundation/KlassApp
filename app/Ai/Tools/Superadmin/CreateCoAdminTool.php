<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Services\Superadmin\CoAdminService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Creates a platform co-admin (usergroup_id=2). Not Approvable — mirrors Batch A CoAdmins.save create.
 */
class CreateCoAdminTool implements Tool
{
    use AuthorizesPlatformAction;

    public function __construct(private readonly CoAdminService $coAdmins) {}

    public function description(): Stringable|string
    {
        return 'Create a platform co-admin account (usergroup_id=2). Required: name, email, password (min 6).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Display name')->required(),
            'email' => $schema->string()->description('Unique login email')->required(),
            'password' => $schema->string()->description('Initial password (min 6)')->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $coAdmin = $this->coAdmins->create([
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => $request['password'],
            ]);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        }

        return "✅ Co-admin created: id={$coAdmin->id}, email={$coAdmin->email}, usergroup_id=2.";
    }
}
