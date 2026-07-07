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
    	$schoolDetail = School::with(['subscription.plan', 'user', 'subscription.plan'])->where('id', $this->schoolDetailId)->first();
        $admins = $schoolDetail ? $schoolDetail->user()->whereIn('usergroup_id', [2,3])->get() : collect();

        return view('livewire.superadmin.academics.school-detail',[
        	'schoolDetail' => $schoolDetail,
            'admins' => $admins,
        ]);
    }
}
