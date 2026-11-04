<?php

namespace App\Services;

use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * One place that records a fee payment for the admin and bursar desks.
 *
 * A missing fee category is a general payment against the student's total.
 * An allocated payment still stores the category. The balance is the fees
 * due minus every payment, so both kinds reduce the same balance.
 */
class FeePaymentRecorder
{
    /**
     * @return array<string, mixed>
     */
    public function rules(int $schoolId): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'fee_category_id' => [
                'nullable',
                'exists:fees_categories,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($schoolId): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    if (! FeesCategories::query()->where('school_id', $schoolId)->where('id', $value)->exists()) {
                        $fail('The selected fee category does not belong to your school.');
                    }
                },
            ],
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:255',
            'paid_on' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function record(int $schoolId, int $recordedBy, array $validated): FeePayment
    {
        $student = User::query()
            ->where('school_id', $schoolId)
            ->where('id', $validated['user_id'])
            ->first();
        if (! $student) {
            throw ValidationException::withMessages([
                'user_id' => 'Student not found in your school.',
            ]);
        }

        $categoryId = $validated['fee_category_id'] ?? null;
        if ($categoryId !== null && $categoryId !== '') {
            $ownsCategory = FeesCategories::query()
                ->where('school_id', $schoolId)
                ->where('id', $categoryId)
                ->exists();
            if (! $ownsCategory) {
                throw ValidationException::withMessages([
                    'fee_category_id' => 'The selected fee category does not belong to your school.',
                ]);
            }
        } else {
            $categoryId = null;
        }

        return FeePayment::create([
            'school_id' => $schoolId,
            'fee_category_id' => $categoryId,
            'user_id' => $validated['user_id'],
            'amount' => $validated['amount'],
            'paid_on' => $validated['paid_on'] ?? now()->toDateString(),
            'payment_method' => $validated['payment_method'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => $recordedBy,
            'status' => 'paid',
        ]);
    }
}
