<?php

namespace App\Models;

use App\Models\Concerns\HasApprovals;
use App\Models\Contracts\Approvable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentLinkRequest extends Model implements Approvable
{
    use HasApprovals;

    protected $fillable = [
        'school_id',
        'phone',
        'parent_name',
        'child_name',
        'child_class',
        'suggested_student_id',
        'matched_student_id',
        'status',
        'flow_token',
        'candidate_student_ids',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'candidate_student_ids' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function suggestedStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_student_id');
    }

    public function matchedStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_student_id');
    }

    public function displayType(): string
    {
        return 'Parent link (WhatsApp)';
    }

    public function summaryLine(): string
    {
        return "{$this->parent_name} → {$this->child_name} ({$this->child_class})";
    }
}
