<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use App\Models\Academics\Exam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One release of an exam's marks to parents on WhatsApp (Task 9 Tier 2).
 *
 * scope "school" covers every class with this exam; "class" covers one class
 * and stream (standard_link_id). scheduled_at and released_at are UTC.
 */
class MarksRelease extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING = 'sending';
    public const STATUS_RELEASED = 'released';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'marks_releases';

    protected $fillable = [
        'school_id', 'exam_id', 'scope', 'standard_link_id', 'scheduled_at', 'released_at',
        'status', 'report_kind', 'scoring_rule', 'progressive_rules', 'report_composition', 'created_by', 'timezone', 'result', 'whatsapp_send', 'release_reason', 'pilot_recipients',
    ];

    protected $casts = [
        'scheduled_at' => UtcDateTime::class,
        'released_at' => UtcDateTime::class,
        'result' => 'array',
        'progressive_rules' => 'array',
        'report_composition' => 'array',
        'pilot_recipients' => 'array',
        'whatsapp_send' => 'boolean',
        'report_composition' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function standardLink(): BelongsTo
    {
        return $this->belongsTo(StandardLink::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
