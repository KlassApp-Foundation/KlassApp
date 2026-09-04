<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\FeePayment;

class RecordPaymentTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Record a fee payment for a student. Provide student ID, amount, fee category ID, and optionally method (cash, cheque, mobile_money, bank_transfer). Default method is cash.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'studentId' => $schema->integer()->description('Student user ID'),
            'amount' => $schema->number()->min(0)->description('Amount paid in UGX'),
            'fee_category_id' => $schema->integer()->description('Fee category ID to attribute the payment to'),
            'payment_method' => $schema->string()->enum(['cash', 'cheque', 'mobile_money', 'bank_transfer'])->description('Payment method')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = [
            'student_id' => $request->get('studentId'),
            'amount' => $request->get('amount'),
            'fee_category_id' => $request->get('fee_category_id'),
            'payment_method' => $request->get('payment_method', 'cash'),
        ];

        $confirm = $this->confirmOrExecute('toolRecordPayment', $args,
            fn() => "Record payment: UGX {$args['amount']} for student ID {$args['student_id']} via {$args['payment_method']}");
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::recordPayment($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $studentId = $request->get('studentId') ?? $request->get('student_id');
        $amount = $request->get('amount');

        if (!$studentId || !$amount) {
            return ['verified' => false, 'message' => 'Student ID and amount are required for verification.'];
        }

        $exists = FeePayment::where('school_id', $schoolId)
            ->where('user_id', $studentId)
            ->where('amount', (float) $amount)
            ->where('recorded_by', auth()->id())
            ->latest()
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Payment record confirmed in database.'
                : 'Payment record was not found after writing.',
        ];
    }
}
