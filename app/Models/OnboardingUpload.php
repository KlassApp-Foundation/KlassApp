<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A roster file (teachers, class teachers, students) being imported for a
 * school's onboarding. Rows are counted as the queued job works through the
 * file, so the wizard can show progress.
 */
class OnboardingUpload extends Model
{
    public const KIND_TEACHERS = 'teachers';

    public const KIND_CLASS_TEACHERS = 'class_teachers';

    public const KIND_STUDENTS = 'students';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'school_id', 'user_id', 'kind', 'original_name', 'path', 'file_hash',
        'status', 'total', 'processed', 'added', 'skipped', 'report',
        'rows', 'skips', 'notes', 'missing_streams', 'error', 'finished_at',
    ];

    protected $casts = [
        'rows' => 'array',
        'skips' => 'array',
        'notes' => 'array',
        'missing_streams' => 'array',
        'finished_at' => 'datetime',
    ];

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_DONE, self::STATUS_FAILED], true);
    }

    /** @return array{id: int, kind: string, status: string, total: int, processed: int, added: int, skipped: int, report: ?string, error: ?string} */
    public function progress(): array
    {
        return [
            'id' => (int) $this->id,
            'kind' => (string) $this->kind,
            'status' => (string) $this->status,
            'total' => (int) $this->total,
            'processed' => (int) $this->processed,
            'added' => (int) $this->added,
            'skipped' => (int) $this->skipped,
            'report' => $this->report,
            'error' => $this->error,
        ];
    }
}
