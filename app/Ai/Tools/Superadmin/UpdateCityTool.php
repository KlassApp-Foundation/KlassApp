<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Services\Superadmin\CityService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateCityTool implements Tool
{
    use AuthorizesPlatformAction;

    public function __construct(private readonly CityService $cities) {}

    public function description(): Stringable|string
    {
        return 'Update an existing platform city by id. Required: id, country_id, name, status.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('City id')->required(),
            'country_id' => $schema->integer()->description('Parent country id')->required(),
            'name' => $schema->string()->description('City name')->required(),
            'status' => $schema->integer()->description('1=active, 0=inactive')->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $city = $this->cities->update((int) $request['id'], [
                'country_id' => $request['country_id'],
                'name' => $request['name'],
                'status' => $request['status'],
            ]);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return '❌ City not found.';
        }

        return "✅ City updated: {$city->name} (id={$city->id}).";
    }
}
