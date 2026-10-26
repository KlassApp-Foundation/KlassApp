<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * In-app notice for a marks submission change. The database channel is the
 * bell the admin and teacher already have. This notice does not send WhatsApp.
 */
class MarksSubmissionNotification extends Notification
{
    public function __construct(
        public string $message,
        public int $examId,
        public string $kind,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'data' => [
                'data' => $this->message,
                'type' => 'marks_submission',
            ],
            'exam_id' => $this->examId,
            'kind' => $this->kind,
        ];
    }
}
