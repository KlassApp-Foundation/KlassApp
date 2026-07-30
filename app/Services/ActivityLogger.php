<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Spatie-compatible activity logger that writes to the app's activity_log table.
 *
 * Restores the fluent activity() API after spatie/laravel-activitylog was removed
 * during the Laravel 10→11 upgrade, without reintroducing the package dependency.
 */
class ActivityLogger
{
    protected ?string $logName;

    protected ?Model $performedOn = null;

    protected ?Model $causedBy = null;

    /** @var array<string, mixed> */
    protected array $properties = [];

    public function __construct(?string $logName = null)
    {
        $this->logName = $logName ?? config('activitylog.default_log_name', 'default');
    }

    public function useLog(?string $logName): self
    {
        $this->logName = $logName;

        return $this;
    }

    public function performedOn(?Model $model): self
    {
        $this->performedOn = $model;

        return $this;
    }

    public function causedBy(?Model $model): self
    {
        $this->causedBy = $model;

        return $this;
    }

    /**
     * @param  array<string, mixed>|mixed  $properties
     */
    public function withProperties(mixed $properties): self
    {
        $this->properties = is_array($properties) ? $properties : (array) $properties;

        return $this;
    }

    public function log(string $description): ?ActivityLog
    {
        if (! config('activitylog.enabled', true)) {
            return null;
        }

        return ActivityLog::create([
            'log_name' => $this->logName,
            'description' => $description,
            'subject_id' => $this->performedOn?->getKey(),
            'subject_type' => $this->performedOn?->getMorphClass(),
            'causer_id' => $this->causedBy?->getKey(),
            'causer_type' => $this->causedBy?->getMorphClass(),
            'properties' => $this->properties,
            'school_id' => $this->resolveSchoolId(),
            'batch_uuid' => null,
        ]);
    }

    protected function resolveSchoolId(): ?int
    {
        foreach ([$this->causedBy, $this->performedOn] as $model) {
            if ($model !== null && isset($model->school_id) && $model->school_id !== null) {
                return (int) $model->school_id;
            }
        }

        return null;
    }
}
