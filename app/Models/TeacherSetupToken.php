<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSetupToken extends Model
{
    protected $fillable = ['school_id', 'user_id', 'token_hash', 'expires_at', 'used_at', 'created_by'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at !== null && $this->expires_at->isFuture();
    }
}
