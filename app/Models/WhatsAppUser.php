<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppUser extends Model
{
    protected $table = 'whatsapp_users';

    protected $fillable = [
        'phone',
        'user_id',
        'school_id',
        'verified_at',
        'opted_in',
        'last_inbound_at',
    ];

    protected $casts = [
        'verified_at'     => 'datetime',
        'opted_in'        => 'boolean',
        'last_inbound_at' => 'datetime',
    ];

    /**
     * The KlassApp user this WhatsApp number belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The school this WhatsApp user is associated with.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * Scope: only users who have opted in to WhatsApp notifications.
     */
    public function scopeOptedIn($query)
    {
        return $query->where('opted_in', true);
    }

    /**
     * Find a WhatsApp user by phone number (E.164).
     */
    public static function findByPhone(string $phone): ?self
    {
        return self::where('phone', $phone)->first();
    }

    /**
     * Check if this user is verified (has completed OTP/linking flow).
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
