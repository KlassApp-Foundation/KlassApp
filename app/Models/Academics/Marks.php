<?php
// added for Ugandan Schools *Elicom Elijah*

namespace App\Models\Academics;

use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marks extends Model
{
    use HasFactory;
    protected $fillable=[
        "student_id", "subject_id", "exam_id", "teacher_id", "school_id", "marks", "comment"
    ];
    public function student(){
        return $this->belongsTo(User::class, "student_id");
    }
     public function subject(){
        return $this->belongsTo(Subject::class, "subject_id");
    }
     public function exam(){
        return $this->belongsTo(Exam::class, "exam_id");
    }
     public function teacher(){
        return $this->belongsTo(User::class, "teacher_id");
    }
     public function school(){
        return $this->belongsTo(School::class, "school_id");
    }
}
