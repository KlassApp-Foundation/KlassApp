<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\User;

class UserDetail extends Component
{	
	public $userDetailId;

	public function mount($id)
	{
		$this->userDetailId = $id;
	}

    public function render()
    {	
    	$userDetail = User::where('id', $this->userDetailId)->first();

        return view('livewire.superadmin.academics.user-detail', [
        	'userDetail' => $userDetail,
        ]);
    }
}
