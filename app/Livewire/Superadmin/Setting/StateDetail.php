<?php

namespace App\Livewire\Superadmin\Setting;

use Livewire\Component;
use App\Models\State;

class StateDetail extends Component
{	
	public $StateDetailId;

	public function mount($id)
	{
		$this->StateDetailId = $id;
	}

    public function render()
    {	
    	$stateDetail = State::where('id', $this->StateDetailId)->first();

        return view('livewire.superadmin.setting.state-detail',[
        	'stateDetail' => $stateDetail,
        ]);
    }
}
