<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class RecordPaymentTool implements Tool
{
    public function description(): string
    {
        return 'Record a fee payment for a student. Provide student ID, amount, and optionally method (cash, cheque, mobile_money, bank_transfer). Default method is cash.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'studentId' => $schema->integer()->description('Student user ID'),
            'amount' => $schema->number()->min(0)->description('Amount paid in UGX'),
            'payment_method' => $schema->string()->enum(['cash', 'cheque', 'mobile_money', 'bank_transfer'])->description('Payment method')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::recordPayment($user, [
            'student_id' => $request->get('studentId'),
            'amount' => $request->get('amount'),
            'payment_method' => $request->get('payment_method', 'cash'),
        ]);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
