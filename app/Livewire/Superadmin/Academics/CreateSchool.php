<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\City;
use App\Models\Country;
use App\Models\EmisSchool;
use App\Models\School;
use App\Services\Superadmin\SchoolService;
use Livewire\Attributes\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class CreateSchool extends Component
{
    use LivewireAlert;

    #[Rule('required')]
    public $name;

    #[Rule('required|email')]
    public $email;

    #[Rule('required|numeric|digits:10')]
    public $phone;

    #[Rule('required')]
    public $address;

    #[Rule('required')]
    public $city;

    #[Rule('required')]
    public $country;

    #[Rule('required|numeric')]
    public $pincode;

    public $registration_country;
    public $student_size;

    #[Rule('nullable|exists:emis_schools,emis_code')]
    public $ministry_code;

    public $curriculum = 'uneb';
    public $status = 1;

    public $schoolId;

    public $citylist;

    public $emisSchoolName;
    public $emisDistrict;

    public function mount($id)
    {
        $this->schoolId = $id;

        if ($this->schoolId != '') {
            $school = School::where('id', $this->schoolId)->first();
            $this->name = $school->name;
            $this->email = $school->email;
            $this->phone = $school->phone;
            $this->address = $school->address;
            $this->city = $school->city_id;
            $this->country = $school->country_id;
            $this->pincode = $school->pincode;
            $this->registration_country = $school->registration_country;
            $this->student_size = $school->student_size;
            $this->ministry_code = $school->ministry_code;
            $this->curriculum = $school->curriculum ?? 'uneb';
            $this->status = $school->status;

            if ($school->country_id != '') {
                $this->changeCity();
            }

            if ($school->ministry_code) {
                $this->lookupEmis($school->ministry_code);
            }
        }
    }

    public function updatedMinistryCode($value)
    {
        $this->reset('emisSchoolName', 'emisDistrict');

        if (empty($value)) {
            return;
        }

        $this->lookupEmis($value);
    }

    private function lookupEmis($code)
    {
        $match = EmisSchool::where('emis_code', $code)->first();

        if ($match) {
            $this->emisSchoolName = $match->school_name;
            $this->emisDistrict = $match->district;
        }
    }

    public function submitSchool()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city_id' => $this->city,
            'country_id' => $this->country,
            'pincode' => $this->pincode,
            'registration_country' => $this->registration_country,
            'student_size' => $this->student_size,
            'ministry_code' => $this->ministry_code,
            'curriculum' => $this->curriculum,
            'status' => $this->status,
        ];

        $service = app(SchoolService::class);

        if ($this->schoolId == '') {
            $this->validate([
                'email' => 'required|unique:' . School::class,
            ]);

            $service->create($data);

            $this->alert('success', 'School created successfully');
        } else {
            $service->update((int) $this->schoolId, $data);

            $this->alert('success', 'School updated successfully');
        }

        return redirect(url('superadmin/academics/schools'));
    }

    public function changeCity()
    {
        $this->citylist = City::where('country_id', $this->country)->get();
    }

    public function render()
    {
        $countries = Country::get();

        return view('livewire.superadmin.academics.create-school', [
            'cities' => $this->citylist,
            'countries' => $countries,
        ]);
    }
}
