<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
Use App\Models\User;

class AdminDetail extends Component
{	
	public $adminDetailId;

	public function mount($id)
	{
		$this->adminDetailId = $id;
	}

    public function render()
    {	
    	$adminDetail = User::where('id', $this->adminDetailId)->first();
    	//dd($this->adminDetailId);
//dd($adminDetail);
        return view('livewire.superadmin.academics.admin-detail',[
        	'adminDetail' => $adminDetail,
        ]);
    }
}
