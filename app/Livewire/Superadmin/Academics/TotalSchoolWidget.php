<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\School;

class TotalSchoolWidget extends Component
{
    public function render()
    {	
    	$totalSchools = School::count();

        return view('livewire.superadmin.academics.total-school-widget',[
        	'totalSchools' => $totalSchools,
        ]);
    }
}
