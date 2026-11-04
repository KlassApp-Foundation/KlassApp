<?php

namespace App\Services\StudentProfile;

use App\Models\FeePayment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Record a payment against one student's term total, from the student profile.
 *
 * No fee allocation: the payment is a general one (fee_category_id null), like the rest of
 * the fees screens it counts toward the student's total (StudentProfileService::fees). It uses
 * the existing fee_payments table and model; the demo-fees work (#1270) is not in this base.
 * Nothing is sent to parents from here.
 */
class StudentPaymentRecorder
{
    use AuthorizesStudentProfileActions;

    /** Stored value => label shown in the form. */
    public const METHODS = [
        'mobile_money' => 'Mobile money',
        'bank_transfer' => 'Bank',
        'cash' => 'Cash',
    ];

    public const MAX_AMOUNT = 1000000000;

    /**
     * @param  array{amount?: mixed, payment_method?: mixed, paid_on?: mixed, reference?: mixed}  $input
     * @param  string|null  $requestId  one-time key from the form: submitting the same form twice records one payment
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function record(User $actor, User $student, array $input, ?string $requestId = null): FeePayment
    {
        $this->authorizeProfileAction($actor, $student);

        if (isset($input['amount']) && is_string($input['amount'])) {
            $input['amount'] = str_replace([',', ' '], '', $input['amount']);
        }

        $data = Validator::make($input, [
            'amount' => ['required', 'numeric', 'gt:0', 'max:'.self::MAX_AMOUNT],
            'payment_method' => ['required', 'in:'.implode(',', array_keys(self::METHODS))],
            'paid_on' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'reference' => ['nullable', 'string', 'max:255'],
        ], [
            'amount.required' => 'Enter the amount paid.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.gt' => 'The amount must be more than zero.',
            'amount.max' => 'That amount is too large.',
            'payment_method.required' => 'Choose how it was paid.',
            'payment_method.in' => 'Choose mobile money, bank or cash.',
            'paid_on.before_or_equal' => 'The date cannot be in the future.',
        ])->validate();

        $key = $requestId !== null && $requestId !== ''
            ? 'student-payment:'.$student->id.':'.sha1($requestId)
            : null;

        if ($key !== null && ($existing = Cache::get($key)) !== null) {
            $payment = FeePayment::query()->where('school_id', $student->school_id)->whereKey($existing)->first();
            if ($payment) {
                return $payment;
            }
        }

        $payment = DB::transaction(function () use ($actor, $student, $data) {
            $payment = FeePayment::create([
                'school_id' => $student->school_id,
                'fee_category_id' => null,
                'user_id' => $student->id,
                'amount' => $data['amount'],
                'paid_on' => $data['paid_on'],
                'payment_method' => $data['payment_method'],
                'reference' => ($data['reference'] ?? null) ?: null,
                'recorded_by' => $actor->id,
                'status' => 'paid',
            ]);

            activity()
                ->performedOn($payment)
                ->causedBy($actor)
                ->withProperties([
                    'school_id' => (int) $student->school_id,
                    'student_id' => (int) $student->id,
                    'amount' => (float) $data['amount'],
                    'payment_method' => $data['payment_method'],
                    'source' => 'student_profile',
                ])
                ->useLog('fees')
                ->log('fees.payment_recorded');

            return $payment;
        });

        if ($key !== null) {
            Cache::put($key, $payment->id, 600);
        }

        return $payment;
    }
}
