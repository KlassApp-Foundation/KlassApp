<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\Userprofile;
use Livewire\WithPagination;

class UserprofileList extends Component
{	
	use WithPagination;

	public $userId;

	public function mount($id)
	{
		$this->userId = $id;
	}

    public function render()
    {	
    	$userprofiles = Userprofile::where('user_id', $this->userId)->paginate(10);

        return view('livewire.superadmin.academics.userprofile-list', [
        	'userprofiles' => $userprofiles,
        ]);
    }
}
