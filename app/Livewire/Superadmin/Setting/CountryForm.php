<?php

namespace App\Livewire\Superadmin\Setting;

use Livewire\Component;
use App\Models\Country;
use Livewire\Attributes\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class CountryForm extends Component
{	
	use LivewireAlert;

	#[Rule('required')]
	public $name;

	#[Rule('required')]
	public $short_name;

	#[Rule('required')]
	public $iso_code;

	#[Rule('required')]
	public $tel_prefix;

	#[Rule('required')]
	public $status = 1;

	public $countryEditId;

	public function mount($id)
	{
		$this->countryEditId = $id;

		$countryEdit = Country::where('id', $this->countryEditId)->first();
		$this->name = $countryEdit->name;
		$this->short_name = $countryEdit->short_name;
		$this->iso_code = $countryEdit->iso_code;
		$this->tel_prefix = $countryEdit->tel_prefix;
		$this->status = $countryEdit->status;
	}

	public function submitCountry()
	{	
		$this->validate();

		$data = [
			'name' => $this->name,
			'short_name' => $this->short_name,
			'iso_code' => $this->iso_code,
			'tel_prefix' => $this->tel_prefix,
			'status' => $this->status,
		];

		Country::where('id', $this->countryEditId)->update($data);

		//session()->flash('message', 'Country updated successfully');

		$this->alert('success', 'Country updated successfully');

		return redirect(url('/superadmin/setting/countries'));
	}

    public function render()
    {
        return view('livewire.superadmin.setting.country-form');
    }
}
