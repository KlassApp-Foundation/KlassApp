<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'preferred_language',
        'notification_channel',
        'digest_enabled',
        'digest_frequency',
        'digest_weekday',
        'timezone',
    ];

    protected $attributes = [
        'preferred_language' => 'en',
        'notification_channel' => 'whatsapp',
        'digest_enabled' => false,
        'digest_frequency' => 'none',
    ];

    protected function casts(): array
    {
        return [
            'digest_enabled' => 'boolean',
            'digest_weekday' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
