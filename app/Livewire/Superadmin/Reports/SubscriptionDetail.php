<?php

namespace App\Livewire\Superadmin\Reports;

use Livewire\Component;
use App\Models\Subscription;

class SubscriptionDetail extends Component
{	
	public $subscriptionDetailId;

	public function mount($id)
	{
		$this->subscriptionDetailId = $id;
	}

    public function render()
    {	
    	$subscriptionDetail = Subscription::where('id', $this->subscriptionDetailId)->first();

        return view('livewire.superadmin.reports.subscription-detail',[
        	'subscriptionDetail' => $subscriptionDetail,
        ]);
    }
}
