<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamType extends Model
{
    use HasFactory;
    protected $fillable = ["school_id", "name", "code", "contributes_to_report_total"];

    protected $casts = [
        'contributes_to_report_total' => 'boolean',
    ];

    /**
     * The shared catalog (school_id NULL) plus this school's own types.
     */
    public function scopeVisibleToSchool($query, ?int $schoolId)
    {
        return $query->where(function ($q) use ($schoolId) {
            $q->whereNull('school_id');
            if ($schoolId !== null) {
                $q->orWhere('school_id', $schoolId);
            }
        });
    }

    public function isShared(): bool
    {
        return $this->school_id === null;
    }
}
