<?php

namespace App\Livewire\Superadmin\Setting;

use Livewire\Component;
use App\Models\Country;
use Livewire\Attributes\Rule;
use App\Models\City;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class CityForm extends Component
{	
	use LivewireAlert;

	#[Rule('required')] 
	public $country;

	#[Rule('required')] 
	public $name;

	#[Rule('required')] 
	public $status = 1;
	
	public $cityEditId;

	public function mount($id)
	{
		$this->cityEditId = $id;

		if($this->cityEditId != '')
		{
			$city = City::where('id', $this->cityEditId)->first();
			$this->country = $city->country_id;
			$this->name = $city->name;
			$this->status = $city->status;
		}
	}

	public function submitCity()
	{
		$this->validate();

		$data = [
			'country_id' => $this->country,
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
        $countries = Country::get();

        return view('livewire.superadmin.setting.city-form',[
            'countries' => $countries,
        ]);
    }
}
