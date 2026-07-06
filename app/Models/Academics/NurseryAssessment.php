<?php

namespace App\Models\Academics;

use App\Models\User;
use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NurseryAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_term_id',
        'exam_id',
        'domain',
        'rating',
        'remarks',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
