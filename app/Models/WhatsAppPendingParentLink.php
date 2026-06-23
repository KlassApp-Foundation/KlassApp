<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppPendingParentLink extends Model
{
    protected $table = 'whatsapp_pending_parent_links';

    protected $fillable = [
        'phone',
        'school_id',
        'student_id',
        'student_payment_code',
        'student_name',
        'status',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
