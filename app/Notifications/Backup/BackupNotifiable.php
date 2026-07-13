<?php

namespace App\Notifications\Backup;

use Spatie\Backup\Notifications\Notifiable;

class BackupNotifiable extends Notifiable
{
    public function routeNotificationForWhatsApp(): string
    {
        return config('backup.notifications.whatsapp.phone', '');
    }
}
