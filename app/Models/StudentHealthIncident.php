<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentHealthIncident extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'incident_date',
        'description',
        'action_taken',
        'recorded_by',
        'severity',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeWhereSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
