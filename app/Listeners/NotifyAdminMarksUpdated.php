<?php

namespace App\Listeners;

use App\Events\MarksUpdated;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class NotifyAdminMarksUpdated
{
    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    public function handle(MarksUpdated $event): void
    {
        $exam = $event->exam;
        $teacher = $event->teacher;

        $admins = User::where('school_id', $exam->school_id)
            ->where('usergroup_id', 3)
            ->whereNotNull('whatsapp_phone')
            ->get();

        if ($admins->isEmpty()) {
            Log::info("MarksUpdated: no admin with WhatsApp linked for school {$exam->school_id}");
            return;
        }

        $subjectName = $exam->subject->name ?? 'Unknown Subject';
        $className = $exam->standard->name ?? 'Unknown Class';
        $teacherName = $teacher->name;

        $message = "📝 Marks Update\n"
            . "Teacher: {$teacherName}\n"
            . "Subject: {$subjectName}\n"
            . "Class: {$className}\n"
            . "Exam: {$exam->id}\n\n"
            . "Marks have been entered/updated. Review them in the dashboard.";

        foreach ($admins as $admin) {
            try {
                $this->whatsApp->sendText(
                    $admin->whatsapp_phone,
                    $message,
                    'marks_updated',
                    $admin->id,
                );
                Log::info("MarksUpdated: notified admin {$admin->id} via WhatsApp");
            } catch (\Throwable $e) {
                Log::warning("MarksUpdated: failed to notify admin {$admin->id}: {$e->getMessage()}");
            }
        }
    }
}
