<?php

namespace App\Services\StudentProfile;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Record one day's attendance for one student, with an optional comment, from the profile.
 *
 * One row per student and day (session "forenoon", the session Toshi and the bulk tools use),
 * so recording the same day again changes that row instead of adding a second one. Parents
 * see absences in their weekly summary; nothing is sent from here.
 */
class StudentAttendanceRecorder
{
    use AuthorizesStudentProfileActions;

    public const SESSION = 'forenoon';

    public function __construct(private StudentEnrollment $enrollment) {}

    /**
     * @param  array{date?: mixed, status?: mixed, comment?: mixed}  $input  status is "present" or "absent"
     *
     * @throws ValidationException
     */
    public function record(User $actor, User $student, array $input): Attendance
    {
        $this->authorizeProfileAction($actor, $student);

        $data = Validator::make($input, [
            'date' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'status' => ['required', 'in:present,absent'],
            'comment' => ['nullable', 'string', 'max:500'],
        ], [
            'date.required' => 'Choose the day.',
            'date.before_or_equal' => 'The day cannot be in the future.',
            'status.required' => 'Choose present or absent.',
            'status.in' => 'Choose present or absent.',
            'comment.max' => 'Keep the comment under 500 characters.',
        ])->validate();

        $schoolId = (int) $student->school_id;
        $year = $this->enrollment->year($schoolId);
        $academic = $this->enrollment->current($student, $year);
        if (! $year || ! $academic || ! $academic->standardLink_id) {
            throw ValidationException::withMessages(['date' => 'This student is not in a class this year, so attendance cannot be recorded.']);
        }

        $day = \Illuminate\Support\Carbon::parse($data['date'])->toDateString();
        $comment = trim((string) ($data['comment'] ?? ''));

        return DB::transaction(function () use ($actor, $student, $schoolId, $year, $academic, $day, $data, $comment) {
            $row = Attendance::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $student->id)
                ->whereDate('date', $day)
                ->where('session', self::SESSION)
                ->lockForUpdate()
                ->first();

            $attributes = [
                'academic_year_id' => $year->id,
                'standardLink_id' => $academic->standardLink_id,
                'status' => $data['status'] === 'present' ? 1 : 0,
                'remarks' => $comment === '' ? null : $comment,
                'recorded_by' => $actor->id,
            ];

            if ($row) {
                $row->forceFill($attributes)->save();
            } else {
                $row = new Attendance;
                $row->forceFill($attributes + [
                    'school_id' => $schoolId,
                    'user_id' => $student->id,
                    'date' => $day,
                    'session' => self::SESSION,
                ])->save();
            }

            activity()
                ->performedOn($row)
                ->causedBy($actor)
                ->withProperties([
                    'school_id' => $schoolId,
                    'student_id' => (int) $student->id,
                    'date' => $day,
                    'status' => $data['status'],
                    'source' => 'student_profile',
                ])
                ->useLog('attendance')
                ->log('attendance.recorded');

            return $row;
        });
    }
}
