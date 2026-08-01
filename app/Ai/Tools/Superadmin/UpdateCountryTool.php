<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Services\Superadmin\CountryService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateCountryTool implements Tool
{
    use AuthorizesPlatformAction;

    public function __construct(private readonly CountryService $countries) {}

    public function description(): Stringable|string
    {
        return 'Update an existing platform country by id. Required: id, name, short_name, iso_code, tel_prefix, status.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Country id')->required(),
            'name' => $schema->string()->description('Country name')->required(),
            'short_name' => $schema->string()->description('Short name')->required(),
            'iso_code' => $schema->string()->description('ISO code')->required(),
            'tel_prefix' => $schema->string()->description('Telephone prefix')->required(),
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
            $country = $this->countries->update((int) $request['id'], [
                'name' => $request['name'],
                'short_name' => $request['short_name'],
                'iso_code' => $request['iso_code'],
                'tel_prefix' => $request['tel_prefix'],
                'status' => $request['status'],
            ]);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return '❌ Country not found.';
        }

        return "✅ Country updated: {$country->name} (id={$country->id}).";
    }
}
