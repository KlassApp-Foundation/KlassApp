<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingSession extends Model
{
    protected $fillable = [
        'user_id', 'school_id', 'step', 'substep', 'data', 'status',
    ];

    protected $casts = [
        'data' => 'array',
        'step' => 'integer',
        'substep' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
