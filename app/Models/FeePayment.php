<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    protected $fillable = [
        'school_id',
        'fee_category_id',
        'user_id',
        'amount',
        'paid_on',
        'payment_method',
        'reference',
        'notes',
        'recorded_by',
        'status',
    ];

    protected $casts = [
        'paid_on' => 'date',
        'amount'  => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeesCategories::class, 'fee_category_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Parent-facing reminder text for this payment record.
     *
     * The pre-V2 Fee model (and its dates) was removed with the old fees system,
     * so the message uses only live data: the fee category name and, when set,
     * its due date.
     */
    public function reminderMessage(): string
    {
        $category = $this->feeCategory;
        $name     = $category->name ?? 'School fees';

        return $category && $category->due_date
            ? $name.' fee payment is pending. Last date for payment - '.$category->due_date->format('d-m-Y').'.'
            : $name.' fee payment is pending. Please contact the school office.';
    }
}
