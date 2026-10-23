<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppLinkCode extends Model
{
    protected $table = 'whatsapp_link_codes';

    protected $fillable = [
        'school_id', 'user_id', 'code_hash', 'expires_at', 'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
