<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToshiPersona extends Model
{
    protected $fillable = [
        'user_id',
        'summary',
        'traits',
        'interaction_count',
        'last_interaction_at',
    ];

    protected $casts = [
        'traits' => 'array',
        'interaction_count' => 'integer',
        'last_interaction_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
