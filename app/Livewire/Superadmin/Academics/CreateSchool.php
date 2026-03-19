<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\City;
use App\Models\State;
use App\Models\Country;
use App\Models\School;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Validate;
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
	public $state;

	#[Rule('required')]
	public $country;

	#[Rule('required|numeric')]
	public $pincode;

	public $status = 1;

	public $schoolId;

	public $statelist;

	public $citylist;
    
	public function mount($id)
	{
		$this->schoolId = $id;

		if($this->schoolId != '')
		{
			$school = School::where('id', $this->schoolId)->first();
			$this->name = $school->name;
			$this->email = $school->email;
			$this->phone = $school->phone;
			$this->address = $school->address;
			$this->city = $school->city_id;
			$this->state = $school->state_id;
			$this->country = $school->country_id;
			$this->pincode = $school->pincode;
			$this->status = $school->status;

			if($school->country_id != '')
			{
				$this->changeState();
			}

			if($school->state_id != '')
			{
				$this->changeCity();
			}
		}
	}

	public function submitSchool()
	{	
		//dd('test');

		$this->validate();

		$data = [
			'name' => $this->name,
			'email' => $this->email,
			'phone' => $this->phone,
			'address' => $this->address,
			'city_id' => $this->city,
			'state_id' => $this->state,
			'country_id' => $this->country,
			'pincode' => $this->pincode,
			'status' => $this->status,
		];

		if($this->schoolId == '')
		{	
			$validatedData = $this->validate([
            	'email' => 'required|unique:'.School::class
            ]);

			School::create($data);

			$this->alert('success', 'School created successfully');

			//session()->flash('message', 'School created successfully');
		}
		else
		{
			School::where('id', $this->schoolId)->update($data);

			$this->alert('success', 'School updated successfully');

			//session()->flash('message', 'School updated successfully');
		}

		return redirect(url('superadmin/academics/schools'));
	}

	public function changeState()
	{
		$this->statelist = State::where('country_id', $this->country)->get();
	}

	public function changeCity()
	{
		$this->citylist = City::where('state_id', $this->state)->get();
	}

    public function render()
    {	
    	//$cities = City::get(); //where('status', 1)

    	//$states = State::get(); //where('status', 1)

    	$countries = Country::get(); //where('status', 1)

        return view('livewire.superadmin.academics.create-school',[
        	//'cities' => $cities,
        	'cities' => $this->citylist,
        	//'states' => $states,
        	'states' => $this->statelist,
        	'countries' => $countries,
        ]);
    }
}
