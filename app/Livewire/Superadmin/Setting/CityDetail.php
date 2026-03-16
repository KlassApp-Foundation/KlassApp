<?php

namespace App\Livewire\Superadmin\Setting;

use Livewire\Component;
use App\Models\City;

class CityDetail extends Component
{	
	public $cityDetailId;

	public function mount($id)
	{
		$this->cityDetailId = $id;
	}

    public function render()
    {	
    	$cityDetail = City::where('id', $this->cityDetailId)->first();

        return view('livewire.superadmin.setting.city-detail', [
        	'cityDetail' => $cityDetail,
        ]);
    }
}
