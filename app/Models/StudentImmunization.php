<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentImmunization extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'vaccine_name',
        'administered_date',
        'administered_by',
        'next_due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'administered_date' => 'date',
            'next_due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeWhereSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
