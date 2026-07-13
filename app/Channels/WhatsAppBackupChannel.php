<?php

namespace App\Channels;

use App\Services\WhatsAppBusinessService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Spatie\Backup\Notifications\BaseNotification;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;

class WhatsAppBackupChannel
{
    public function __construct(
        protected WhatsAppBusinessService $whatsapp,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->routeNotificationForWhatsApp();
        if (empty($phone)) {
            Log::warning('WhatsApp backup channel: no recipient phone configured.');
            return;
        }

        $message = $this->formatMessage($notification);
        if ($message === null) {
            return;
        }

        $result = $this->whatsapp->sendText(
            phone: $phone,
            message: $message,
            flowType: 'backup_alert',
        );

        if (! ($result['success'] ?? false)) {
            Log::error('WhatsApp backup channel: send failed', [
                'phone' => $phone,
                'error' => $result['error'] ?? 'unknown',
            ]);
        }
    }

    protected function formatMessage(Notification $notification): ?string
    {
        if (! $notification instanceof BaseNotification) {
            return null;
        }

        $appName = $notification->applicationName();

        return match (true) {
            $notification instanceof BackupHasFailedNotification => $this->formatFailure($notification, $appName),
            $notification instanceof CleanupHasFailedNotification => $this->formatCleanupFailure($notification, $appName),
            $notification instanceof UnhealthyBackupWasFoundNotification => $this->formatUnhealthy($notification, $appName),
            default => $this->formatGeneric($notification, $appName),
        };
    }

    protected function formatFailure(BackupHasFailedNotification $notification, string $appName): string
    {
        $exception = $notification->event->exception;
        $message = $exception->getMessage();
        $lines = ["\u{274C} Backup FAILED \u{2014} {$appName}"];
        $lines[] = '';
        $lines[] = "Error: {$message}";
        $lines[] = '';
        $this->appendDestinationProperties($notification, $lines);

        return implode("\n", $lines);
    }

    protected function formatCleanupFailure(CleanupHasFailedNotification $notification, string $appName): string
    {
        $exception = $notification->event->exception;
        $message = $exception->getMessage();
        $lines = ["\u{26A0}\u{FE0F} Backup Cleanup FAILED \u{2014} {$appName}"];
        $lines[] = '';
        $lines[] = "Error: {$message}";

        return implode("\n", $lines);
    }

    protected function formatUnhealthy(UnhealthyBackupWasFoundNotification $notification, string $appName): string
    {
        $lines = ["\u{26A0}\u{FE0F} Backup UNHEALTHY \u{2014} {$appName}"];
        $lines[] = '';
        foreach ($notification->event->failureMessages as $failure) {
            $lines[] = "- [{$failure['check']}] {$failure['message']}";
        }
        $lines[] = '';
        $this->appendDestinationProperties($notification, $lines);

        return implode("\n", $lines);
    }

    protected function formatGeneric(BaseNotification $notification, string $appName): string
    {
        $class = class_basename($notification);
        $lines = ["\u{2139}\u{FE0F} Backup Notification \u{2014} {$appName}"];
        $lines[] = '';
        $lines[] = "Event: {$class}";
        $lines[] = '';
        $this->appendDestinationProperties($notification, $lines);

        return implode("\n", $lines);
    }

    protected function appendDestinationProperties(BaseNotification $notification, array &$lines): void
    {
        $props = $notification->backupDestinationProperties();
        foreach ($props as $name => $value) {
            $lines[] = "{$name}: {$value}";
        }
    }
}
