<?php

namespace App\Http\Requests;

use App\Models\Country;
use App\Models\Keyword;
use App\Models\School;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Name must be unique across OTHER schools (the school's own current name
        // is always allowed). The previous implementation only passed when the
        // submitted name was a substring of the school's *existing* name, which
        // silently blocked a manual admin from renaming the signup placeholder
        // ("{First}'s School") to a real name — Toshi's commitAll bypasses this
        // request entirely, so the manual path could never complete school_name.
        Validator::extend('checkunique_schoolname', function ($attribute, $value, $parameters, $validator) {
            return ! School::where('name', request('name'))
                ->where('id', '!=', Auth::user()->school_id)
                ->exists();
        });

        Validator::extend('check_keyword', function ($attribute, $value, $parameters, $validator) {
            $keyword = Keyword::where('name', 'LIKE', '%'.request('name').'%')->exists();
            if ($keyword) {
                return false;
            }

            return true;
        });

        Validator::extend('check_date', function ($attribute, $value, $parameters, $validator) {
            if ($value === null || $value === '') {
                return true;
            }

            return date('Y-m-d', strtotime((string) request('date_of_establishment')))
                <= date('Y-m-d', strtotime('-1 days', strtotime(date('Y-m-d'))));
        });

        Validator::extend('check_website', function ($attribute, $value, $parameters, $validator) {
            if ($value === null || $value === '') {
                return true;
            }

            return (bool) preg_match('/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/', (string) request('website'));
        });

        $rules = [
            'name' => ['required', 'max:30', 'checkunique_schoolname', 'check_keyword'],
            'moto' => ['nullable', 'max:50'],
            'date_of_establishment' => ['nullable', 'check_date'],
            'board' => ['nullable', 'string', 'max:50'],
            'about_us' => ['nullable', 'max:250'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'city_id' => ['nullable', 'integer'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'check_website'],
            'ministry_code' => [
                Rule::requiredIf(fn () => $this->selectedCountryIsUganda()),
                'nullable',
                'string',
                'max:50',
            ],
        ];

        if (Schema::hasColumn('schools', 'uneb_center_number')) {
            $rules['uneb_center_number'] = ['nullable', 'string', 'max:50'];
        }

        if (request('school_logo') != null) {
            $rules['school_logo'] = ['nullable', 'mimes:png,jpg,jpeg'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'School Name Is Required',
            'name.max' => 'School Name Should Be Atmost 30 Characters',
            'name.checkunique_schoolname' => 'School Name Already Exists. Try Different Name',
            'name.check_keyword' => 'Enter A Valid School Name',

            'moto.max' => 'Moto Should Not Exceed More Than 50 Characters',

            'date_of_establishment.check_date' => 'Select Valid Date',

            'school_logo.mimes' => 'Choose png or jpg File',

            'about_us.max' => 'About Us Should Not Exceed 250 Words',

            'country_id.required' => 'Country Is Required',
            'country_id.exists' => 'Select A Valid Country',

            'website.check_website' => 'Enter Valid Website',

            'ministry_code.required' => 'EMIS / Ministry code is required for Uganda schools',
            'ministry_code.max' => 'EMIS / Ministry code should not exceed 50 characters',

            'uneb_center_number.max' => 'UNEB centre number should not exceed 50 characters',
        ];
    }

    /**
     * Country selector writes both country_id and registration_country.
     * EMIS requiredness follows the selected countries.name (Toshi-aligned).
     */
    private function selectedCountryIsUganda(): bool
    {
        $countryId = $this->input('country_id');
        if (! $countryId) {
            return false;
        }

        $country = Country::query()->find($countryId);

        return $country !== null && OnboardingStepsService::isUganda($country->name);
    }
}
