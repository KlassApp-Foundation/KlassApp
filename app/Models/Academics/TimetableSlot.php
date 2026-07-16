<?php

namespace App\Models\Academics;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Events;
use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TimetableSlot extends Model
{
    protected $table = 'timetable_slots';

    protected $fillable = [
        'school_id', 'academic_year_id', 'academic_term_id',
        'section_id', 'subject_id', 'teacher_id',
        'day_of_week', 'start_time', 'end_time', 'room',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    // ── Relationships ──

    public function school() { return $this->belongsTo(School::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function term() { return $this->belongsTo(AcademicTerm::class, 'academic_term_id'); }
    public function section() { return $this->belongsTo(Section::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }

    // ── Conflict Detection ──

    /**
     * Check for overlapping slots and return a conflict error message,
     * or null if no conflict exists.
     */
    public static function detectConflict(
        int $schoolId, int $sectionId, int $teacherId,
        int $dayOfWeek, string $startTime, string $endTime,
        ?int $excludeId = null,
    ): ?string {
        $baseQuery = function ($q) use ($dayOfWeek, $startTime, $endTime, $excludeId) {
            $q->where('day_of_week', $dayOfWeek)
              ->where('start_time', '<', $endTime)
              ->where('end_time', '>', $startTime);
            if ($excludeId) {
                $q->where('id', '!=', $excludeId);
            }
        };

        // Teacher overlap
        $teacherConflict = self::where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where($baseQuery)
            ->with('section', 'subject')
            ->first();

        if ($teacherConflict) {
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $day = $dayNames[$dayOfWeek] ?? 'Unknown';
            $otherDay = $dayNames[$teacherConflict->day_of_week] ?? 'Unknown';
            return "The teacher is already booked on {$otherDay} "
                . substr($teacherConflict->start_time, 0, 5) . "–" . substr($teacherConflict->end_time, 0, 5)
                . " for {$teacherConflict->subject?->name} in {$teacherConflict->section?->name}. "
                . "Please choose a different time slot.";
        }

        // Section/room overlap
        $sectionConflict = self::where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->where($baseQuery)
            ->with('subject')
            ->first();

        if ($sectionConflict) {
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $day = $dayNames[$dayOfWeek] ?? 'Unknown';
            $otherDay = $dayNames[$sectionConflict->day_of_week] ?? 'Unknown';
            return "This class already has {$sectionConflict->subject?->name} scheduled on {$otherDay} "
                . substr($sectionConflict->start_time, 0, 5) . "–" . substr($sectionConflict->end_time, 0, 5)
                . ". Please choose a different time slot.";
        }

        return null;
    }

    /**
     * Check if this slot's teacher+subject+section combination exists
     * in class_teacher_links. Returns null if OK, or a warning string.
     */
    public static function checkTeacherLink(int $schoolId, int $teacherId, int $subjectId, int $sectionId): ?string
    {
        $exists = \DB::table('class_teacher_links')
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->where('standardLink_id', function ($q) use ($sectionId) {
                $q->select('id')->from('standards_link')
                  ->where('section_id', $sectionId)
                  ->limit(1);
            })
            ->exists();

        if (!$exists) {
            return "Warning: This teacher-subject-class combination is not in the standard class-teacher assignments. Consider adding it in Settings → Grading & Subjects first.";
        }

        return null;
    }

    // ── Calendar Sync (delete+rebuild) ──

    /**
     * Sync a recurring calendar event for this slot.
     * Uses the same delete+rebuild pattern as the exam-calendar link.
     * Creates a single recurring event spanning the term's date range.
     */
    public function syncCalendarEvent(): void
    {
        // Remove any existing event for this slot
        Events::where('timetable_slot_id', $this->id)->delete();

        if (!$this->term) {
            return;
        }

        $term = $this->term;
        $startDate = $term->starts_on ? \Carbon\Carbon::parse($term->starts_on) : null;
        $endDate = $term->ends_on ? \Carbon\Carbon::parse($term->ends_on) : null;

        if (!$startDate || !$endDate) {
            return;
        }

        // Find the first occurrence of this day_of_week within the term
        $firstDate = clone $startDate;
        while ((int) $firstDate->format('w') !== $this->day_of_week) {
            $firstDate->addDay();
        }

        Events::create([
            'timetable_slot_id'  => $this->id,
            'school_id'          => $this->school_id,
            'academic_year_id'   => $this->academic_year_id,
            'batch'              => '',
            'select_type'        => 'school',
            'title'              => $this->subject?->name ?? 'Lesson',
            'category'           => 'education',
            'start_date'         => $firstDate->format('Y-m-d') . ' ' . $this->start_time,
            'end_date'           => $firstDate->format('Y-m-d') . ' ' . $this->end_time,
            'repeats'            => 1,
            'freq'               => 1,
            'freq_term'          => 'weekly',
            'location'           => $this->room,
            'allDay'             => 0,
            'status'             => 'active',
            'color'              => '#8B5CF6',
            'created_by'         => $this->teacher_id,
        ]);
    }
}
