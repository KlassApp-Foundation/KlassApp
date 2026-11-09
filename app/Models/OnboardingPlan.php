<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingPlan extends Model
{
    protected $fillable = [
        'school_id',
        'created_by',
        'source_sha256',
        'source_name',
        'schema',
        'status',
        'dpa_confirmed_at',
        'expires_at',
        'payload',
    ];

    protected $hidden = [
        'payload',
    ];

    protected $casts = [
        'payload' => 'encrypted:array',
        'dpa_confirmed_at' => 'datetime',
        'expires_at' => 'datetime',
        'schema' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
