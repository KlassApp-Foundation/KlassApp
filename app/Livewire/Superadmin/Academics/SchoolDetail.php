<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\School;

class SchoolDetail extends Component
{	
	public $schoolDetailId;

	public function mount($id)
	{
		$this->schoolDetailId = $id;
	}

    public function render()
    {	
    	$schoolDetail = School::where('id', $this->schoolDetailId)->first();

        return view('livewire.superadmin.academics.school-detail',[
        	'schoolDetail' => $schoolDetail,
        ]);
    }
}
