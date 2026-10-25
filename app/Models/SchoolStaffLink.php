<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolStaffLink extends Model
{
    protected $fillable = ['school_id', 'token_hash', 'expires_at', 'revoked_at', 'created_by'];

    protected $casts = ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function isUsable(): bool
    {
        return $this->revoked_at === null && $this->expires_at !== null && $this->expires_at->isFuture();
    }
}
