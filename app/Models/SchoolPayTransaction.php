<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolPayTransaction extends Model
{
    protected $table = 'schoolpay_transactions';

    protected $fillable = [
        'school_id',
        'student_id',
        'student_payment_code',
        'schoolpay_receipt',
        'amount',
        'channel',
        'student_name',
        'student_class',
        'source',
        'raw_payload',
        'notification_status',
        'matched_fee_category_id',
        'reconciled_at',
        'paid_at',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'raw_payload'   => 'json',
        'paid_at'       => 'datetime',
        'reconciled_at'  => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function matchedFeeCategory(): BelongsTo
    {
        return $this->belongsTo(FeesCategories::class, 'matched_fee_category_id');
    }

    public function scopeUnreconciled($query)
    {
        return $query->whereNull('matched_fee_category_id');
    }

    public function scopeReconciled($query)
    {
        return $query->whereNotNull('matched_fee_category_id');
    }
}
