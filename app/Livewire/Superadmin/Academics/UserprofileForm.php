<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\Usergroup;
use App\Models\School;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use App\Models\Userprofile;
use Livewire\Attributes\Rule;

class UserprofileForm extends Component
{
	#[Rule('required')]
	public $school;
	#[Rule('required')]
	//public $user;
	#[Rule('required')]
	public $usergroup;
	#[Rule('required')]
	public $firstname;
	public $lastname;
	public $alternate_no;
	#[Rule('required')]
	public $gender;
	// Optional — matches Track B Admin/Teacher DOB (birthday features tolerate null)
	public $dob;
	#[Rule('required')]
	public $address;
	#[Rule('required')]
	public $city;
	#[Rule('required')]
	public $country;
	#[Rule('required')]
	public $pincode;
	#[Rule(['required', 'regex:/^KLS\d{7}$/i'])]
	public $registration_number;
	#[Rule('required')]
	public $LIN;
	#[Rule('required')]
	public $joining_date;
	public $notes;
	#[Rule('required')]
	public $status;

	public $userId;

	public $segment;

	public function mount($id)
	{	//dd($id);
		$this->userId = $id;

		$this->segment = \Request::segment ('5');

		// create/{userId}: leave fields blank. update/{userprofileId}: hydrate.
		if($this->segment === 'update' && $this->userId != '')
		{
			$userprofile = Userprofile::where('id', $this->userId)->first();
			$this->school = $userprofile->school_id;
			$this->userId = $userprofile->user_id;
			$this->usergroup = $userprofile->usergroup_id;
			$this->firstname = $userprofile->firstname;
			$this->lastname = $userprofile->lastname;
			$this->alternate_no = $userprofile->alternate_no;
			$this->gender = $userprofile->gender;
			$this->dob = $userprofile->date_of_birth;
			$this->address = $userprofile->address;
			$this->city = $userprofile->city_id;
			$this->country = $userprofile->country_id;
			$this->pincode = $userprofile->pincode;
			$this->registration_number = $userprofile->registration_number;
			$this->LIN = $userprofile->LIN;
			$this->joining_date = $userprofile->joining_date;
			$this->notes = $userprofile->notes;
			$this->status = $userprofile->status;
		}
	}

	public function submitUserprofile()
	{
		$this->validate();

		$data = [
			'school_id' => $this->school,
			'user_id' => $this->userId,
			'usergroup_id' => $this->usergroup,
			'firstname' => $this->firstname,
			'lastname' => $this->lastname,
			'alternate_no' => $this->alternate_no,
			'gender' => $this->gender,
			'date_of_birth' => blank($this->dob) ? null : $this->dob,
			'address' => $this->address,
			'city_id' => $this->city,
			'country_id' => $this->country,
			'pincode' => $this->pincode,
			'registration_number' => $this->registration_number,
			'LIN' => $this->LIN,
			'joining_date' => $this->joining_date,
			'notes' => $this->notes,
			'status' => $this->status,
		];
		//dd($this->userId);
		if($this->segment == 'create')
		{
			Userprofile::create($data);
		}
		else
		{	//dd($this->userId);

			Userprofile::where('user_id', $this->userId)->update($data);
		}

		return redirect(url('superadmin/academics/schools'));
	}

    public function render()
    {
    	$usergroups = Usergroup::get();

    	$schools = School::where('status', '1')->get();

    	$cities = City::get(); //where('status', 1)

    	$countries = Country::get(); //where('status', 1)

    	$user = User::where('id', $this->userId)->first();
		//dd($user);

        return view('livewire.superadmin.academics.userprofile-form', [
        	'usergroups' => $usergroups,
        	'schools' => $schools,
        	'cities' => $cities,
        	'countries' => $countries,
        	'user' => $user,
        ]);
    }
}
