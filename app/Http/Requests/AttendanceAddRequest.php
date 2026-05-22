<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SiteHelper;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceAddRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // ✅ Validate date is within academic year and not in the future
        Validator::extend('check_date', function ($attribute, $value, $parameters, $validator) {
            $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);

            $start_date = Carbon::parse($academic_year->start_date);
            $input_date = Carbon::createFromFormat('m/d/Y', $value); // US format
            $today = Carbon::today();

            return $input_date->between($start_date, $today);
        });

        // ✅ Ensure no duplicate attendance for same class/session/date
        Validator::extend('check_session', function ($attribute, $value, $parameters, $validator) {
            $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);

            $date = Carbon::createFromFormat('m/d/Y', request('date'))->format('Y-m-d'); // normalize for DB
            $standardLink_id = (int) request('standardLink_id');

            $attendance = Attendance::where([
                ['school_id', Auth::user()->school_id],
                ['academic_year_id', $academic_year->id],
                ['date', '=', $date],
                ['session', request('session')],
                ['standardLink_id', $standardLink_id],
            ])->exists();

            return !$attendance;
        });

        // ✅ Prevent duplicate student entries
        Validator::extend('check_user', function ($attribute, $value, $parameters, $validator) {
            $count = 0;
            for ($i = 0; $i < request('absentCount'); $i++) {
                if ($value == request('user_id' . $i)) {
                    $count++;
                }
            }
            return $count <= 1;
        });

        $rules = [
            'standardLink_id' => 'required',
            'date'            => 'required|date_format:m/d/Y|check_date',
            'session'         => 'required|check_session',
        ];

        // ✅ Remarks validation per absent student
        for ($i = 0; $i < request('absentCount'); $i++) {
            Validator::extend('check_remarks', function ($attribute, $value, $parameters, $validator) {
                return preg_match('/^[A-Za-z_~\-!@#\$%\^&*.,:(\)\s]+$/', $value);
            });

            $rules['user_id' . $i]   = 'required|check_user';
            $rules['reason_id' . $i] = 'required';
            $rules['remarks' . $i]   = 'nullable|max:20|check_remarks';
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'standardLink_id.required' => 'Class is required',
            'date.required'            => 'Date is required',
            'date.date_format'         => 'Date must be in mm/dd/yyyy format',
            'date.check_date'          => 'Enter valid Date',
            'session.required'         => 'Session is required',
            'session.check_session'    => 'Attendance already updated',
        ];

        for ($i = 0; $i < request('absentCount'); $i++) {
            $messages['user_id' . $i . '.required']       = 'Student is required';
            $messages['user_id' . $i . '.check_user']     = 'Student already exists';
            $messages['reason_id' . $i . '.required']     = 'Reason is required';
            $messages['remarks' . $i . '.check_remarks']  = 'Enter Valid Remarks';
            $messages['remarks' . $i . '.max']            = 'Remarks may not be greater than 20 letters';
        }

        return $messages;
    }
}
