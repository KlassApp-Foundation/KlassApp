<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentHealthProfile extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'allergies',
        'chronic_conditions',
        'emergency_contact_name',
        'emergency_contact_phone',
        'blood_type',
        'medical_notes',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeWhereSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
