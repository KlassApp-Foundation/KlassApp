<?php
// added for Ugandan Schools *Elicom Elijah*
namespace App\Models\Academics;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Events;
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
         "standard_id", "school_id", "section_id", "academic_year_id", "academic_term_id", "subject_id", "teacher_id", "exam_type_id", "status","scheduled_at", "default_deadline"
        ];
      protected $dates = ["deleted_at"];

      protected $casts = [
          'default_deadline' => 'datetime',
          'scheduled_at' => 'datetime',
      ];

    protected static function booted()
    {
        // When an exam is created with a scheduled_at, create corresponding
        // calendar events for both the exam date and the marks deadline.
        static::created(function (Exam $exam) {
            $exam->syncCalendarEvents();
        });

        // When an exam is updated (rescheduled, deadline changed), refresh
        // the linked calendar events.
        static::updated(function (Exam $exam) {
            $exam->syncCalendarEvents();
        });

        // When an exam is deleted, remove its calendar events so they
        // aren't orphaned.
        static::deleted(function (Exam $exam) {
            Events::where('exam_id', $exam->id)->delete();
        });
    }

    /**
     * Create or update calendar events for this exam's scheduled date
     * and marks deadline. Creates two events:
     *   1. "Exam: {Subject} — {ExamType}" on scheduled_at
     *   2. "📝 Marks Due: {Subject} — {ExamType}" on default_deadline
     *
     * Only creates events when the respective date is set.
     */
    public function syncCalendarEvents(): void
    {
        if (!$this->school_id) return;

        $subjectName = $this->subject?->name ?? 'Subject';
        $examTypeName = $this->examType?->name ?? 'Exam';
        $baseTitle = "{$subjectName} — {$examTypeName}";

        // Remove existing events for this exam so we can rebuild cleanly
        Events::where('exam_id', $this->id)->delete();

        // Event 1: exam date
        if ($this->scheduled_at) {
            Events::create([
                'exam_id'          => $this->id,
                'school_id'        => $this->school_id,
                'academic_year_id' => $this->academic_year_id,
                'batch'            => '',
                'select_type'      => 'school',
                'title'            => "Exam: {$baseTitle}",
                'category'         => 'exam',
                'start_date'       => $this->scheduled_at,
                'end_date'         => $this->scheduled_at,
                'allDay'           => 1,
                'status'           => 'active',
                'color'            => '#2563EB',
                'created_by'       => $this->teacher_id,
            ]);
        }

        // Event 2: marks deadline
        if ($this->default_deadline) {
            Events::create([
                'exam_id'          => $this->id,
                'school_id'        => $this->school_id,
                'academic_year_id' => $this->academic_year_id,
                'batch'            => '',
                'select_type'      => 'school',
                'title'            => "📝 Marks Due: {$baseTitle}",
                'category'         => 'exam',
                'start_date'       => $this->default_deadline,
                'end_date'         => $this->default_deadline,
                'allDay'           => 1,
                'status'           => 'active',
                'color'            => '#DC2626',
                'created_by'       => $this->teacher_id,
            ]);
        }
    }

    public function calendarEvents()
    {
        return $this->hasMany(\App\Models\Events::class, 'exam_id');
    }

    public function marks(){
           return $this->hasMany(Marks::class);
       }
       public function academicTerm(){
        return $this->belongsTo(AcademicTerm::class);
       }
        public function section(){
           return $this->belongsTo(Section::class, "section_id");
       }
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

        public function officialTeacher(){
            return $this->belongsTo(Userprofile::class, "teacher_id");
        }

        // toggle exam status
        public function changeExamStatus(){
            $this->status = match ($this->status){
                "undone" => "done",
                "done" => "submitted",
                "submitted" => "submitted",
            };
            $this->save();
        }

    public function submission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ExamMarksSubmission::class, 'exam_id')
            ->whereColumn('class_id', 'section_id')
            ->whereColumn('subject_id', 'subject_id');
    }

    // ============= SCOPES ==========
    public function scopeForSchool($query, $val) {
        return $query->where("school_id", $val);
    }

}
