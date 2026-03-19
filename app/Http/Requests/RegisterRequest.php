<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use App\Rules\ValidRecaptcha;
use App\Models\Keyword;
use App\Models\School;
use App\Models\User;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        Validator::extend('checkunique_schoolname',function($attribute,$value,$parameters,$validator)
        {
            $school = School::where('name','LIKE','%'.request('school_name').'%')->exists();
            if($school)
            {
                return false;
            }
            return true;
        });

        Validator::extend('check_keyword',function($attribute,$value,$parameters,$validator)
        {
            $keyword = Keyword::where('name','LIKE','%'.request('school_name').'%')->exists();
            if($keyword)
            {
                return false;
            }
            return true;
        });

        /*Validator::extend('checkaddress',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z0-9_~\-!@#\$%\^&*.,:(\)\s]+$/', request('address')) ;
        });*/

        Validator::extend('checkunique_email',function($attribute,$value,$parameters,$validator)
        {
            $user = User::where('email','LIKE','%'.request('email').'%')->exists();
            if($user)
            {
                return false;
            }
            return true;
        });

        Validator::extend('checkunique_mobile',function($attribute,$value,$parameters,$validator)
        {
            $normalizedMobileNo = $this->normalizeMobileNumber(request('mobile_no'), request('country'));

            if (!$normalizedMobileNo) {
                return true;
            }

            $user = User::where('mobile_no', '=', $normalizedMobileNo)->exists();
            if($user)
            {
                return false;
            }
            return true;
        });

        $rules = [
            'school_name'           =>  'required|max:30|checkunique_schoolname|check_keyword',
            /*'address'               =>  'required|checkaddress',
            'city'                  =>  'required',
            'state'                 =>  'required',
            'pincode'               =>  'required',*/
            'name'                  =>  ['required', 'string', 'min:2', 'max:100', "regex:/^[\pL\s'\-]+$/u"],
            'role'                  =>  'nullable|string|max:60',
            'mobile_no'             =>  ['required', 'regex:/^0?\d{9,10}$/', 'checkunique_mobile'],
            'country'               =>  'nullable|string|max:100',
            'student_size'          =>  'nullable|string|max:50',
            'email'                 =>  'required|email|checkunique_email',
            'password'              =>  'required|min:8|confirmed',
            'termsandcondn'         =>  'required',
            //
        ];

        // $rules['g-recaptcha-response']=['required', new ValidRecaptcha];

        return $rules;
    }

    public function messages()
    {
        return[
            'school_name.required'                  =>  'School Name is required',
            'school_name.max:15'                    =>  'School Name should be atmost 30 characters',
            'school_name.checkunique_schoolname'    =>  'School Name already exists. Try different Name',
            'school_name.check_keyword'             =>  'Enter a Valid School Name',

            /*'address.required'                      =>  'Address is required',
            'address.checkaddress'                  =>  'Enter a Valid Address',

            'city.required'                         =>  'City is required',

            'state.required'                        =>  'State is required',

            'pincode.required'                      =>  'Pincode is required',*/

            'name.required'                         =>  'User Name is required',
            'name.regex'                            =>  'Please enter your full name using letters, spaces, hyphens, and apostrophes only.',
            'name.min:2'                            =>  'Your Full Name must be at least 2 characters.',
            'name.max:100'                          =>  'Your Full Name should be at most 100 characters.',

            'role.max:60'                           =>  'Role should be atmost 60 characters',

            'mobile_no.required'                    =>  'Mobile Number is required.OTP will be sent',
            'mobile_no.regex'                       =>  'Please enter your local number only, without the leading zero — between 9 and 10 digits.',
            'mobile_no.checkunique_mobile'          =>  'Mobile Number already exists. Enter different Mobile Number.OTP will be sent',

            'country.max:100'                       =>  'Country should be atmost 100 characters',

            'student_size.max:50'                   =>  'School size should be atmost 50 characters',

            'email.required'                        =>  'Email ID is required.Verification Mail will be sent',
            'email.email'                           =>  'Enter a valid Email ID.Verification Mail will be sent',
            'email.checkunique_email'               =>  'Email ID already exists. Enter different Email ID.Verification Mail will be sent',

            'password.required'                     =>  'Password is required',
            'password.min:8'                        =>  'Password should be atleast 8 digits',

            'termsandcondn.required'                =>  'Terms and Condition is required',

            'g-recaptcha-response.required'         =>  'Captcha Required',
        ];
    }

    private function normalizeMobileNumber($mobileNo, $country)
    {
        if (!is_string($mobileNo)) {
            return null;
        }

        $digitsOnly = preg_replace('/\D+/', '', $mobileNo);

        if ($digitsOnly === '') {
            return null;
        }

        if (strpos($digitsOnly, '0') === 0) {
            $digitsOnly = substr($digitsOnly, 1);
        }

        if (!preg_match('/^\d{9,10}$/', $digitsOnly)) {
            return null;
        }

        $countryCode = $this->resolveCountryCode($country);

        if (!$countryCode) {
            return null;
        }

        return $countryCode . $digitsOnly;
    }

    private function resolveCountryCode($country)
    {
        $countryCodeMap = [
            'Uganda' => '+256',
            'Kenya' => '+254',
            'Tanzania' => '+255',
            'Rwanda' => '+250',
            'Nigeria' => '+234',
            'Ghana' => '+233',
            'South Africa' => '+27',
            'United Kingdom' => '+44',
            'United States' => '+1',
        ];

        return $countryCodeMap[$country] ?? null;
    }
}
