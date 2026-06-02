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
        return true;
    }

    public function rules()
    {
        // Move custom validators here (better practice)
        $this->registerCustomValidators();

        $rules = [
            'date'    => 'required|date|after_or_equal:' . $this->getAcademicStartDate(),
            'session' => 'required|check_session',
        ];

        $absentCount = (int) $this->input('absentCount', 0);

        for ($i = 0; $i < $absentCount; $i++) {
            $rules["user_id{$i}"]   = 'required|integer';
            $rules["reason_id{$i}"] = 'required|integer';
            $rules["remarks{$i}"]   = 'nullable|string|max:20|check_remarks';
        }

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

            $attendance = Attendance::where([
                ['school_id', Auth::user()->school_id],
                ['academic_year_id', $academic_year->id],
                ['date', $date],                    // Now using YYYY-MM-DD
                ['session', $value],
                ['standardLink_id', null]
            ])->exists();

            return !$attendance; // false if already exists
        });
    }

    protected function getAcademicStartDate()
    {
        $academic_year = AcademicYear::getAcademicYear(Auth::user()->school_id);
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
        ];

        $absentCount = (int) $this->input('absentCount', 0);

        for ($i = 0; $i < $absentCount; $i++) {
            $messages["user_id{$i}.required"]   = 'Staff is required';
            $messages["reason_id{$i}.required"] = 'Reason is required';
            $messages["remarks{$i}.max"]        = 'Remarks may not be greater than 20 characters';
            $messages["remarks{$i}.check_remarks"] = 'Remarks contain invalid characters';
        }

        return $messages;
    }
}