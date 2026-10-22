<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One user-initiated WhatsApp verification code. code_hash is an HMAC.
 * The plain code is not a column and must not be written here.
 */
class WhatsAppVerificationChallenge extends Model
{
    protected $table = 'whatsapp_verification_challenges';

    protected $fillable = [
        'school_id',
        'user_id',
        'phone',
        'phone_digits',
        'code_hash',
        'attempts',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'user_id' => 'integer',
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
