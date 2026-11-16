<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportDivisionRange extends Model
{
    protected $fillable = [
        'school_id',
        'standard_id',
        'label',
        'min_aggregate',
        'max_aggregate',
        'position',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }
}
