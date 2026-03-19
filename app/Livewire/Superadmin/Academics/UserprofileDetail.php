<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\Userprofile;

class UserprofileDetail extends Component
{	
	public $userprofileDetailId;

	public function mount($id)
	{
		$this->userprofileDetailId = $id;
	}

    public function render()
    {	
    	$userprofileDetail = Userprofile::where('id', $this->userprofileDetailId)->first();
//dd($this->userprofileDetailId);
        return view('livewire.superadmin.academics.userprofile-detail', [
        	'userprofileDetail' => $userprofileDetail,
        ]);
    }
}
