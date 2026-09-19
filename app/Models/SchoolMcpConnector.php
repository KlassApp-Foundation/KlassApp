<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SchoolMcpConnector extends Model
{
    protected $fillable = [
        'school_id',
        'connector_type',
        'external_team_id',
        'external_team_name',
        'credentials',
        'token_expires_at',
        'auth_mode',
        'status',
        'trust_level',
        'write_mode',
        'tool_allowlist',
        'tool_denylist',
        'connected_by',
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
            'tool_allowlist' => 'array',
            'tool_denylist' => 'array',
            'token_expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at !== null
            && $this->token_expires_at->isPast();
    }

    public function accessToken(): ?string
    {
        $credentials = $this->credentials;

        return $credentials['access_token'] ?? null;
    }

    public function refreshToken(): ?string
    {
        $credentials = $this->credentials;

        return $credentials['refresh_token'] ?? null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('connector_type', $type);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public static function resolveTokenForRequest(string $connectorType, ?int $schoolId = null): ?string
    {
        $schoolId = $schoolId ?? auth()->user()?->school_id;

        if ($schoolId === null) {
            return null;
        }

        $connector = static::query()
            ->active()
            ->forType($connectorType)
            ->forSchool($schoolId)
            ->first();

        if ($connector === null) {
            return null;
        }

        if ($connector->isTokenExpired()) {
            $refreshed = app(McpConnectorTokenRefreshService::class)->refresh($connector);

            if (! $refreshed) {
                return null;
            }

            $connector->refresh();
        }

        $connector->update(['last_used_at' => now()]);

        return $connector->accessToken();
    }
}
