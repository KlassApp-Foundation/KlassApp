<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppPendingNotification extends Model
{
    protected $table = 'whatsapp_pending_notifications';

    public $timestamps = false;

    protected $fillable = [
        'whatsapp_user_id',
        'flow_type',
        'message',
        'template_name',
        'template_variables',
        'send_after',
    ];

    protected $casts = [
        'template_variables' => 'array',
        'send_after'         => 'datetime',
    ];

    public function whatsappUser(): BelongsTo
    {
        return $this->belongsTo(WhatsAppUser::class);
    }
}
