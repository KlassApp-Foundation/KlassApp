<?php
// added for Ugandan Schools *Elicom Elijah*

namespace App\Models\Academics;

use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marks extends Model
{
    use HasFactory;
    protected $fillable=[
        "student_id", "subject_id", "section_id", "exam_id", "teacher_id", "school_id", "marks", "grade", "absent"
    ];

    protected $casts = [
        'absent' => 'boolean',
    ];

    public function isAbsent(): bool
    {
        $grade = strtoupper(trim((string) $this->grade));

        return (bool) $this->absent || in_array($grade, ['AB', 'ABS'], true);
    }

    /**
     * What a report shows. A missing mark and a legacy absent row both print a
     * dash: ABS belongs to attendance, not to marks. The stored grade stays AB.
     */
    public function displayMark(): string
    {
        if ($this->isAbsent()) {
            return \App\Services\Results\ResultCell::DASH;
        }
        if ($this->marks === null) {
            return \App\Services\Results\ResultCell::DASH;
        }

        return \App\Services\Results\ResultsPolicy::printWhole(
            \App\Services\Results\ResultsPolicy::roundTo((float) $this->marks)
        );
    }
    public function student(){
        return $this->belongsTo(User::class, "student_id");
    }

     public function section(){
        return $this->belongsTo(Section::class, "section_id");
    }

//  public function remark(){
//         return $this->belongsTo(Remarks::class, "remark_id");
//     }
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
