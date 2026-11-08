<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGoogleConnection extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'google_sub',
        'google_email',
        'credentials',
        'granted_scopes',
        'attention_scopes',
        'last_error',
        'token_expires_at',
        'status',
        'last_used_at',
        'last_refreshed_at',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'granted_scopes' => 'array',
            'attention_scopes' => 'array',
            'token_expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at !== null
            && $this->token_expires_at->lte(now()->addSeconds(60));
    }

    public function accessToken(): ?string
    {
        return $this->credentials['access_token'] ?? null;
    }

    public function refreshToken(): ?string
    {
        return $this->credentials['refresh_token'] ?? null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
