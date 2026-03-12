<?php
// added for Ugandan Schools *Elicom Elijah*
namespace App\Models\Academics;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;
    protected $fillable=[
        "name", "standard_id", "school_id", "academic_year_id", "term", "subject_id", "teacher_id"
        ];
        public function standard(){
            return $this->belongsTo(Standard::class, "standard_id");
        }
        public function school(){
            return $this->belongsTo(School::class, "school_id");
        }
        public function academicYear(){
            return $this->belongsTo(AcademicYear::class, "academic_year_id");
        }
        public function subject(){
            return $this->belongsTo(Subject::class, "subject_id");
        }
        public function teacher(){
            return $this->belongsTo(User::class, "teacher_id");
        }
}
