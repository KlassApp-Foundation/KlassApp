<?php

namespace App\Livewire\Superadmin\Setting;

use Livewire\Component;
use App\Models\Country;
use App\Models\State;
use Livewire\Attributes\Rule;
use App\Models\City;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class CityForm extends Component
{	
	use LivewireAlert;

	#[Rule('required')] 
	public $country;

	#[Rule('required')] 
	public $state;

	#[Rule('required')] 
	public $name;

	#[Rule('required')] 
	public $status = 1;
	
	public $cityEditId;

	public $statelist;

	public function mount($id)
	{
		$this->cityEditId = $id;

		if($this->cityEditId != '')
		{
			$city = City::where('id', $this->cityEditId)->first();
			$this->country = $city->country_id;
			$this->state = $city->state_id;
			$this->name = $city->name;
			$this->status = $city->status;
		}

		if($city->country_id != '')
		{
			$this->changeState();
		}
	}

	public function changeState()
	{
		$this->statelist = State::where('country_id', $this->country)->get();
	}

	public function submitCity()
	{
		$this->validate();

		$data = [
			'country_id' => $this->country,
			'state_id' => $this->state,
			'name' => $this->name,
			'status' => $this->status,
		];

		if($this->cityEditId == '')
		{
			City::create($data);

			//session()->flash('message', 'City added successfully');

			$this->alert('success', 'City added successfully');
		}
		else
		{
			City::where('id', $this->cityEditId)->update($data);

			//session()->flash('message', 'City updated successfully');

			$this->alert('success', 'City updated successfully');
		}

		return redirect(url('/superadmin/setting/cities'));
	}

    public function render()
    {	
    	$countries = Country::get(); //where('status', 1)

    	//$states = State::get(); //where('status', 1)

        return view('livewire.superadmin.setting.city-form',[
        	'countries' => $countries,
        	//'states' => $states,
        	'states' => $this->statelist,
        ]);
    }
}
