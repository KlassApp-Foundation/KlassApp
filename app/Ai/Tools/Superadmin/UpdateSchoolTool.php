<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Services\Superadmin\SchoolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateSchoolTool implements Tool
{
    use AuthorizesPlatformAction;

    public function __construct(private readonly SchoolService $schools) {}

    public function description(): Stringable|string
    {
        return 'Update an existing school by id (superadmin form path). Required: id plus school fields.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'name' => $schema->string()->required(),
            'email' => $schema->string()->required(),
            'phone' => $schema->string()->description('10-digit phone')->required(),
            'address' => $schema->string()->required(),
            'city_id' => $schema->integer()->required(),
            'country_id' => $schema->integer()->required(),
            'pincode' => $schema->integer()->required(),
            'registration_country' => $schema->string()->nullable(),
            'student_size' => $schema->string()->nullable(),
            'ministry_code' => $schema->string()->nullable(),
            'curriculum' => $schema->string()->nullable(),
            'status' => $schema->integer()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $school = $this->schools->update((int) $request['id'], [
                'name' => $request['name'],
                'email' => $request['email'],
                'phone' => $request['phone'],
                'address' => $request['address'],
                'city_id' => $request['city_id'],
                'country_id' => $request['country_id'],
                'pincode' => $request['pincode'],
                'registration_country' => $request['registration_country'] ?? null,
                'student_size' => $request['student_size'] ?? null,
                'ministry_code' => $request['ministry_code'] ?? null,
                'curriculum' => $request['curriculum'] ?? 'uneb',
                'status' => $request['status'] ?? 1,
            ]);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return '❌ School not found.';
        }

        return "✅ School updated: {$school->name} (id={$school->id}).";
    }
}
