<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\Usergroup;
use App\Models\School;
use App\Models\City;
use App\Models\State;
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
	#[Rule('required')] 
	public $dob;
	#[Rule('required')] 
	public $blood_group;
	#[Rule('required')] 
	public $birth_place;
	#[Rule('required')] 
	public $native_place;
	#[Rule('required')] 
	public $mother_tongue;
	#[Rule('required')] 
	public $caste;
	#[Rule('required')] 
	public $address;
	#[Rule('required')] 
	public $city;
	#[Rule('required')] 
	public $state;
	#[Rule('required')] 
	public $country;
	#[Rule('required')] 
	public $pincode;
	#[Rule('required')] 
	public $aadhar_number;
	#[Rule('required')] 
	public $registration_number;
	#[Rule('required')] 
	public $emis_number;
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

		if($this->userId != '')
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
			$this->blood_group = $userprofile->blood_group;
			$this->birth_place = $userprofile->birth_place;
			$this->native_place = $userprofile->native_place;
			$this->mother_tongue = $userprofile->mother_tongue;
			$this->caste = $userprofile->caste;
			$this->address = $userprofile->address;
			$this->city = $userprofile->city_id;
			$this->state = $userprofile->state_id;
			$this->country = $userprofile->country_id;
			$this->pincode = $userprofile->pincode;
			$this->aadhar_number = $userprofile->aadhar_number;
			$this->registration_number = $userprofile->registration_number;
			$this->emis_number = $userprofile->EMIS_number;
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
			'date_of_birth' => $this->dob,
			'blood_group' => $this->blood_group,
			'birth_place' => $this->birth_place,
			'native_place' => $this->native_place,
			'mother_tongue' => $this->mother_tongue,
			'caste' => $this->caste,
			'address' => $this->address,
			'city_id' => $this->city,
			'state_id' => $this->state,
			'country_id' => $this->country,
			'pincode' => $this->pincode,
			'aadhar_number' => $this->aadhar_number,
			'registration_number' => $this->registration_number,
			'EMIS_number' => $this->emis_number,
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

    	$states = State::get(); //where('status', 1)

    	$countries = Country::get(); //where('status', 1)

    	$user = User::where('id', $this->userId)->first();
		//dd($user);

        return view('livewire.superadmin.academics.userprofile-form', [
        	'usergroups' => $usergroups,
        	'schools' => $schools,
        	'cities' => $cities,
        	'states' => $states,
        	'countries' => $countries,
        	'user' => $user,
        ]);
    }
}
