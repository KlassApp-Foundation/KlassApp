<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property string $email
 * @property int|null $user_id Set when the invite is claimed
 * @property string $token_hash SHA-256 of the one-time token
 * @property string|null $name Pre-filled co-admin display name
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $claimed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class CoAdminInvite extends Model
{
    protected $fillable = [
        'school_id',
        'email',
        'user_id',
        'token_hash',
        'name',
        'expires_at',
        'claimed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function claimedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }
}
