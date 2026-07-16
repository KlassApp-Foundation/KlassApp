<?php

namespace App\Models\Academics;

use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamMarksSubmission extends Model
{
    use HasFactory;

    protected $table = 'exam_marks_submissions';

    protected $fillable = [
        'school_id',
        'exam_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'status',
        'deadline',
        'submitted_at',
        'locked_at',
        'reopened_at',
        'reopened_by',
        'reopen_reason',
        'approval_status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts = [
        'deadline'     => 'datetime',
        'submitted_at' => 'datetime',
        'locked_at'    => 'datetime',
        'reopened_at'  => 'datetime',
        'approved_at'  => 'datetime',
        'rejected_at'  => 'datetime',
    ];

    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function exam(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function class(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Section::class, 'class_id');
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function reopenedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function approvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // ── Scopes ──

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeLockable($query)
    {
        return $query->whereIn('status', ['draft', 'submitted'])
            ->where('deadline', '<=', now());
    }

    // ── Helpers ──

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'reopened']);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function isApprovalPending(): bool
    {
        return $this->approval_status === 'pending' || $this->approval_status === null;
    }

    public function isApprovable(): bool
    {
        return $this->isLocked() && $this->isApprovalPending();
    }

    public function scopeApprovable($query)
    {
        return $query->where('status', 'locked')
            ->where(function ($q) {
                $q->where('approval_status', 'pending')
                  ->orWhereNull('approval_status');
            });
    }
}
