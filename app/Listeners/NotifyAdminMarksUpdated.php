<?php

namespace App\Listeners;

use App\Events\MarksUpdated;
use App\Models\User;
use App\Notifications\MarksCorrectionNotification;
use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifyAdminMarksUpdated
{
    public function __construct(
        protected WhatsAppBusinessService $whatsApp,
    ) {}

    public function handle(MarksUpdated $event): void
    {
        $exam = $event->exam;
        $teacher = $event->teacher;

        $admins = User::where('school_id', $exam->school_id)
            ->where('usergroup_id', 3)
            ->get();

        if ($admins->isEmpty()) {
            Log::info("MarksUpdated: no school admin for school {$exam->school_id}");

            return;
        }

        $subjectName = $exam->subject->name ?? 'Unknown Subject';
        $className = $exam->standard->name ?? 'Unknown Class';
        $teacherName = $teacher->name;

        if ($event->afterReopen) {
            $message = "⚠️ Marks corrected after reopen\n"
                . "Teacher: {$teacherName}\n"
                . "Subject: {$subjectName}\n"
                . "Class: {$className}\n"
                . "Exam: {$exam->id}\n"
                . "Reason: ".($event->reason ?: 'not given')."\n\n"
                . "You reopened this submission; a teacher has since edited the marks. Review them in the dashboard.";
        } else {
            $message = "📝 Marks Update\n"
                . "Teacher: {$teacherName}\n"
                . "Subject: {$subjectName}\n"
                . "Class: {$className}\n"
                . "Exam: {$exam->id}\n\n"
                . "Marks have been entered/updated. Review them in the dashboard.";
        }

        // In-app notification (the admin bell). Sent for every admin so a school
        // admin without a linked WhatsApp number still gets a real notification;
        // reserved for corrections after a reopen so the bell is not spammed by
        // routine marks entry.
        if ($event->afterReopen) {
            try {
                Notification::send($admins, new MarksCorrectionNotification(
                    message: $message,
                    examId: (int) $exam->id,
                    subjectName: (string) $subjectName,
                    teacherName: (string) $teacherName,
                    reason: $event->reason,
                ));
                Log::info("MarksUpdated: in-app notification sent to ".$admins->count()." admin(s) for exam {$exam->id}");
            } catch (\Throwable $e) {
                Log::warning("MarksUpdated: in-app notification failed: {$e->getMessage()}");
            }
        }

        // WhatsApp (unchanged behaviour) — only admins who linked a number.
        foreach ($admins->whereNotNull('whatsapp_phone') as $admin) {
            try {
                $this->whatsApp->sendText(
                    $admin->whatsapp_phone,
                    $message,
                    $event->afterReopen ? 'marks_corrected_after_reopen' : 'marks_updated',
                    $admin->id,
                );
                Log::info("MarksUpdated: notified admin {$admin->id} via WhatsApp");
            } catch (\Throwable $e) {
                Log::warning("MarksUpdated: failed to notify admin {$admin->id}: {$e->getMessage()}");
            }
        }
    }
}
