<?php

namespace App\Traits;

use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\OutboundWhatsAppService;
use Illuminate\Support\Facades\Log;

trait NotifyOnStatusChange
{
    protected static function bootNotifyOnStatusChange(): void
    {
        static::updated(function ($model) {
            if (! $model->isDirty('state')) {
                return;
            }

            $original = $model->getOriginal('state');
            $current  = $model->state;

            $fromLabel = method_exists($original, 'label') ? $original->label() : (string) $original;
            $toLabel   = method_exists($current, 'label') ? $current->label() : (string) $current;

            $approvable = $model->approvable;
            $typeName   = $approvable ? class_basename($approvable) : 'Unknown';
            $summary    = is_string($approvable) ? $approvable : "{$typeName} #{$model->approvable_id}";

            $message = "{$summary} status changed from {$fromLabel} to {$toLabel}.";

            if ($model->comments) {
                $message .= " Comment: {$model->comments}";
            }

            $causedBy = null;
            if ($model->approved_by) {
                $causedBy = User::find($model->approved_by);
            }

            activity()
                ->performedOn($model->approvable ?? $model)
                ->causedBy($causedBy)
                ->withProperties([
                    'approval_id'  => $model->id,
                    'from'         => $fromLabel,
                    'to'           => $toLabel,
                    'comments'     => $model->comments,
                ])
                ->useLog('approval')
                ->log($message);

            self::sendWhatsAppNotification($model, $message, $causedBy);
        });
    }

    protected static function sendWhatsAppNotification($model, string $message, ?User $causedBy): void
    {
        try {
            $approvable = $model->approvable;

            if (! $approvable || ! method_exists($approvable, 'teacher')) {
                return;
            }

            $teacher = $approvable->teacher;
            if (! $teacher) {
                return;
            }

            $whatsAppUser = WhatsAppUser::where('user_id', $teacher->id)
                ->where('opted_in', true)
                ->first();

            if (! $whatsAppUser || ! $whatsAppUser->phone) {
                return;
            }

            $whatsApp = app(OutboundWhatsAppService::class);
            $whatsApp->sendText($whatsAppUser->phone, $message, 'approval_status');
        } catch (\Throwable $e) {
            Log::warning("NotifyOnStatusChange: WhatsApp send failed: {$e->getMessage()}");
        }
    }
}
