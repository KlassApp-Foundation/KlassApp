<?php

namespace App\Livewire\Superadmin\Reports;

use Livewire\Component;
use App\Models\Query;
use Livewire\WithPagination;

class Contact extends Component
{	
	use WithPagination;
	
    public function render()
    {	
    	$contacts = Query::orderBy('id', 'DESC')->paginate(10);

        return view('livewire.superadmin.reports.contact', [
        	'contacts' => $contacts,
        ]);
    }
}
