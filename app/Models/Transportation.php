<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transportation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'vehicle_number',
        'start_time',
        'end_time',
        'stops',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'string',
            'end_time' => 'string',
            'status' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeWhereSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
