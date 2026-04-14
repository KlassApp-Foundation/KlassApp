<?php
// added for Ugandan Schools *Elicom Elijah*
namespace App\Models\Academics;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Exam extends Model
{
    use HasFactory;
    protected $fillable=[
         "standard_id", "school_id", "section_id", "academic_year_id", "academic_term_id", "subject_id", "teacher_id", "exam_type", "status","scheduled_at"
        ];
      protected $dates = ["deleted_at"];  
        public function marks(){
           return $this->hasMany(Marks::class);
       }
       public function academicTerm(){
        return $this->belongsTo(AcademicTerm::class, "academic_term_id");
       }
        public function section(){
           return $this->belongsTo(Section::class, "section_id");
       }
        public function standard(){
            return $this->belongsTo(Standard::class, "standard_id");
        }
        //  public function examType(){
        //     return $this->belongsTo(ExamType::class, "exam_type_id");
        // }
       
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

        public function officialTeacher(){
            return $this->belongsTo(Userprofile::class, "teacher_id");
        }

        // toggle exam status
        public function changeExamStatus(){
            $this->status = match ($this->status){
                "undone" => "done",
                "postponed" => "undone"
            };
            $this->save();
        }

        // ============= SCOPES ==========
    public function scopeForSchool($query, $val) {
        return $query->where("school_id", $val);
    }

}
