<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A change of one school's report type. Rows are inserted, never updated.
 */
class SchoolReportKindChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'school_id',
        'user_id',
        'from_kind',
        'to_kind',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
