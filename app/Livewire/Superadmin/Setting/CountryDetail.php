<?php

namespace App\Livewire\Superadmin\Setting;

use Livewire\Component;
use App\Models\Country;

class CountryDetail extends Component
{	
	public $countryDetailId;

	public function mount($id)
	{
		$this->countryDetailId = $id;
	}

    public function render()
    {	
    	$countryDetail = Country::where('id', $this->countryDetailId)->first();
    	//dd($countryDetail);
    	
        return view('livewire.superadmin.setting.country-detail',[
        	'countryDetail' => $countryDetail,
        ]);
    }
}
