<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Services\Superadmin\CityService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateCityTool implements Tool
{
    use AuthorizesPlatformAction;

    public function __construct(private readonly CityService $cities) {}

    public function description(): Stringable|string
    {
        return 'Create a platform city under a country. Required: country_id, name. Optional status (default 1).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'country_id' => $schema->integer()->description('Parent country id')->required(),
            'name' => $schema->string()->description('City name')->required(),
            'status' => $schema->integer()->description('1=active, 0=inactive')->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $city = $this->cities->create([
                'country_id' => $request['country_id'],
                'name' => $request['name'],
                'status' => $request['status'] ?? 1,
            ]);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        }

        return "✅ City created: {$city->name} (id={$city->id}).";
    }
}
