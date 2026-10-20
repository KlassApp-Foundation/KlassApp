<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolInvite extends Model
{
    protected $fillable = [
        'email',
        'school_name',
        'contact_name',
        'country',
        'approx_students',
        'token_hash',
        'expires_at',
        'claimed_at',
        'revoked_at',
        'email_failed_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'claimed_at' => 'datetime',
        'revoked_at' => 'datetime',
        'email_failed_at' => 'datetime',
        'approx_students' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function emailFailed(): bool
    {
        return $this->email_failed_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isClaimed() && ! $this->isRevoked() && ! $this->isExpired();
    }

    public function status(): string
    {
        if ($this->isClaimed()) {
            return 'claimed';
        }
        if ($this->isRevoked()) {
            return 'revoked';
        }
        if ($this->isExpired()) {
            return 'expired';
        }

        return 'pending';
    }
}
