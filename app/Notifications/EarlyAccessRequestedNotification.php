<?php

namespace App\Notifications;

use App\Models\EarlyAccessRequest;
use Illuminate\Notifications\Notification;

/**
 * In-app (database) notification for platform admins when a school asks
 * for early access. Carries the request id and school name only.
 */
class EarlyAccessRequestedNotification extends Notification
{
    public function __construct(public EarlyAccessRequest $request) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'data' => [
                'data' => 'New early access request: ' . $this->request->school_name,
                'type' => 'early_access_request',
            ],
            'early_access_request_id' => $this->request->id,
        ];
    }
}
