<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\User;
use Livewire\WithPagination;

class AdminList extends Component
{	
	use WithPagination;

	public $school_id;

	public function mount($id)
	{	//dd($id);
		$this->school_id = $id;
	}

    public function render()
    {	
    	$admins = User::where([['school_id', $this->school_id], ['usergroup_id', '3']])->paginate(10);

        return view('livewire.superadmin.academics.admin-list',[
        	'admins' => $admins,
        ]);
    }
}
