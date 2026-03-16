<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\School;

class SchoolList extends Component
{	
	use WithPagination;

    public function render()
    {	
    	$schools = School::orderBy('id', 'DESC')->paginate(10); //where('status', 1)

        return view('livewire.superadmin.academics.school-list', [
        	'schools' => $schools,
        ]);
    }
}
