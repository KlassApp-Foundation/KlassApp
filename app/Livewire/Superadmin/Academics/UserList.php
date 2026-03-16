<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\User;
use Livewire\WithPagination;

class UserList extends Component
{	
	use WithPagination;

	public $school_id;

	public function mount($id)
	{	
		$this->school_id = $id;
	}

    public function render()
    {	
    	$users = User::where([['school_id', $this->school_id],['usergroup_id', '6']])->paginate(10);

        return view('livewire.superadmin.academics.user-list',[
        	'users' => $users,
        ]);
    }
}
