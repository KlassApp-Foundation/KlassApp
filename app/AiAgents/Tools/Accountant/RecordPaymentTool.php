<?php

namespace App\AiAgents\Tools\Accountant;

use App\AiAgents\Concerns\AuthorizesAccountantToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\FeePayment;
use App\Services\Toshi\AccountantActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RecordPaymentTool implements Tool, VerifiableTool
{
    use AuthorizesAccountantToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Record a fee payment for a student. Provide student_id, amount, fee_category_id, and optionally payment_method (cash, cheque, mobile_money, bank_transfer).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'student_id' => $schema->integer()->required(),
            'amount' => $schema->number()->min(1)->required(),
            'fee_category_id' => $schema->integer()->required(),
            'payment_method' => $schema->string()->enum(['cash', 'cheque', 'mobile_money', 'bank_transfer'])->nullable(),
            'reference' => $schema->string()->nullable(),
            'paid_on' => $schema->string()->nullable(),
            'notes' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeAccountantOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'student_id' => $request->get('student_id'),
            'amount' => $request->get('amount'),
            'payment_method' => $request->get('payment_method', 'cash'),
            'fee_category_id' => $request->get('fee_category_id'),
            'reference' => $request->get('reference'),
            'paid_on' => $request->get('paid_on'),
            'notes' => $request->get('notes'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolAccountantRecordPayment',
            $args,
            fn () => "Record payment: UGX {$args['amount']} for student ID {$args['student_id']} via {$args['payment_method']}"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = AccountantActionService::recordPayment($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user?->school_id;
        if (! $schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $studentId = $request->get('student_id');
        $amount = $request->get('amount');
        if (! $studentId || ! $amount) {
            return ['verified' => false, 'message' => 'Student ID and amount are required for verification.'];
        }

        $exists = FeePayment::where('school_id', $schoolId)
            ->where('user_id', $studentId)
            ->where('amount', (float) $amount)
            ->where('recorded_by', $user->id)
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
