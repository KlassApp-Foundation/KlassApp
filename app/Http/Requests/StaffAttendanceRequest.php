<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\Attendance;
use Carbon\Carbon;

class StaffAttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        // Move custom validators here (better practice)
        $this->registerCustomValidators();

        $rules = [
            'date'      => 'required|date|after_or_equal:' . $this->getAcademicStartDate(),
            'session'   => 'required|check_session',
            'user_id'   => 'required|integer',
            'status'    => 'required|in:0,1',
            'reason_id' => 'required_if:status,0|nullable|integer',
            'remarks'   => 'nullable|string|max:20|check_remarks',
        ];

        return $rules;
    }

    protected function registerCustomValidators()
    {
        Validator::extend('check_remarks', function ($attribute, $value) {
            return preg_match('/^[A-Za-z_~\-!@#$%^&*.,:()\s]+$/', $value);
        });

        Validator::extend('check_session', function ($attribute, $value) {
            $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);
            $date = $this->input('date');

            $user_id = $this->input('user_id');
            if (empty($user_id) || empty($date)) {
                return true;
            }

            $attendance = Attendance::where([
                ['school_id', Auth::user()->school_id],
                ['academic_year_id', $academic_year->id],
                ['date', $date],                    // Now using YYYY-MM-DD
                ['session', $value],
                ['user_id', $user_id],
                ['standardLink_id', null]
            ])->exists();

            return !$attendance; // false if already exists
        });
    }

    protected function getAcademicStartDate()
    {
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        return Carbon::parse($academic_year->start_date)->format('Y-m-d');
        
    }

    public function messages()
    {
        $messages = [
            'date.required'         => 'Date is required',
            'date.date'             => 'Enter a valid date',
            'date.after_or_equal'   => 'Date cannot be before academic year start',
            'session.required'      => 'Session is required',
            'session.check_session' => 'Attendance already updated for this date & session',
            'user_id.required'      => 'Staff is required',
            'status.required'       => 'Attendance status is required',
            'status.in'             => 'Select a valid attendance status',
            'reason_id.required_if' => 'Reason is required when staff is absent',
        ];

        return $messages;
    }
}