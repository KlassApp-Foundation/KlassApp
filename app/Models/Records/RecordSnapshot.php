<?php

namespace App\Models\Records;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One learner's released term record, as a family received it.
 *
 * Append-only: a correction inserts version N+1 and flips this row to
 * superseded. Nothing in the app updates payload, and nothing deletes a row.
 */
class RecordSnapshot extends Model
{
    public const STATE_CURRENT = 'current';
    public const STATE_SUPERSEDED = 'superseded';
    public const STATE_REVOKED = 'revoked';

    protected $fillable = [
        'school_id', 'student_id', 'academic_year_id', 'academic_term_id',
        'version', 'supersedes_id', 'reason', 'payload', 'released_at',
        'released_by', 'release_id', 'state',
    ];

    protected $casts = [
        'payload' => 'array',
        'released_at' => 'datetime',
        'version' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(MarksRelease::class, 'release_id');
    }
}
