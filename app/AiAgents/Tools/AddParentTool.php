<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\User;

class AddParentTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Add a parent/guardian and link them to a student. Provide parent name, phone number starting with +256, and optionally the student ID to link to.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Parent full name'),
            'phone' => $schema->string()->description('Phone number starting with +256'),
            'studentId' => $schema->integer()->description('Student ID to link this parent to')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = [
            'name' => $request->get('name'),
            'phone' => $request->get('phone'),
            'student_id' => $request->get('studentId', 0),
        ];

        $confirm = $this->confirmOrExecute('toolAddParent', $args,
            fn() => "Add parent: {$args['name']}, phone: {$args['phone']}"
                . ($args['student_id'] ? ", student ID: {$args['student_id']}" : ''));
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::addParent($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $name = trim($request->get('name', ''));
        $phone = trim($request->get('phone', ''));
        if ($name === '' || $phone === '') {
            return ['verified' => false, 'message' => 'Parent name and phone are required for verification.'];
        }

        $exists = User::where('school_id', $schoolId)
            ->where('usergroup_id', 7)
            ->where('name', $name)
            ->where(function ($q) use ($phone) {
                $q->where('mobile_no', $phone)
                  ->orWhere('phone', $phone);
            })
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Parent record confirmed in database.'
                : 'Parent was not found after creation.',
        ];
    }
}
