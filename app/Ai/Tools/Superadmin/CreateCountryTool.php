<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Services\Superadmin\CountryService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateCountryTool implements Tool
{
    use AuthorizesPlatformAction;

    public function __construct(private readonly CountryService $countries) {}

    public function description(): Stringable|string
    {
        return 'Create a platform country (geo). Required: name, short_name, iso_code, tel_prefix. Optional status (default 1).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Country name')->required(),
            'short_name' => $schema->string()->description('Short name')->required(),
            'iso_code' => $schema->string()->description('ISO code')->required(),
            'tel_prefix' => $schema->string()->description('Telephone prefix, e.g. +256')->required(),
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
            $country = $this->countries->create([
                'name' => $request['name'],
                'short_name' => $request['short_name'],
                'iso_code' => $request['iso_code'],
                'tel_prefix' => $request['tel_prefix'],
                'status' => $request['status'] ?? 1,
            ]);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        }

        return "✅ Country created: {$country->name} (id={$country->id}).";
    }
}
