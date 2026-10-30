<?php

namespace App\Http\Requests;

use App\Support\PhoneCountry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ParentAddRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        Validator::extend('check_firstname', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[A-Za-z\s]+$/', request('firstname'));
        });

        Validator::extend('checkunique_mobile', function ($attribute, $value, $parameters, $validator) {
            $schoolId = Auth::user()?->school_id;
            $user = User::matchingPhone(
                (string) request('mobile_no'),
                PhoneCountry::countryFromAuth(),
                $schoolId ? (int) $schoolId : null,
            )->where('usergroup_id', 7)->exists();
            return !$user;
        });

        // Only validate these fields for "add" mode
        if (request('parent') === 'add') {
            return [
                'firstname' => 'required|check_firstname|max:255',
                'lastname'  => 'nullable|max:255',
                'mobile_no' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                    if (PhoneCountry::normalise((string) $value, PhoneCountry::countryFromAuth()) === null) {
                        $fail('Enter a valid phone number. The school country only fills in the dialling code.');
                    }
                }, 'checkunique_mobile'],
                'relation'  => 'nullable|max:50',
            ];
        }

        // "select" mode — only the multiselect is needed
        return [
            'select_id'  => 'required',
            'standardLink_id' => 'required_with:select_id',
        ];
    }

    public function messages()
    {
        return [
            'firstname.required'      => 'Parent first name is required.',
            'firstname.check_firstname'=> 'First name should only contain letters and spaces.',
            'mobile_no.required'      => 'Phone number is required.',
            'mobile_no.digits'        => 'Phone number must be exactly 10 digits.',
            'mobile_no.numeric'       => 'Phone number must be a valid number.',
            'mobile_no.checkunique_mobile' => 'A parent with this phone number already exists.',
            'select_id.required'      => 'Please select at least one parent.',
        ];
    }
}
