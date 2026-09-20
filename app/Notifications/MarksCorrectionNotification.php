<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * School-admin notification: a teacher corrected marks on a submission the admin
 * had reopened. Delivered to the in-app notification bell (database channel),
 * matching the shape Admin\NotificationController@showList expects.
 */
class MarksCorrectionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $message,
        public int $examId,
        public string $subjectName = '',
        public string $teacherName = '',
        public ?string $reason = null,
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
                'type' => 'marks_correction',
            ],
            'exam_id'      => $this->examId,
            'subject'      => $this->subjectName,
            'teacher'      => $this->teacherName,
            'reason'       => $this->reason,
        ];
    }
}
