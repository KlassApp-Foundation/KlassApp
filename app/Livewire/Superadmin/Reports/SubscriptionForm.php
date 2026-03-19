<?php

namespace App\Livewire\Superadmin\Reports;

use Livewire\Component;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;
use App\Models\School;
use Livewire\Attributes\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class SubscriptionForm extends Component
{	
	use LivewireAlert;

	#[Rule('required')]
	public $user;

	#[Rule('required')]
	public $plan;

	#[Rule('required')]
	public $school;

	public $payment_details;

	public $plan_details;

	#[Rule('required')]
	public $status;

	public $subscriptionEditId;

	public function mount($id)
	{	
		$this->subscriptionEditId = $id;

		if($this->subscriptionEditId != '')
		{
			$subscription = Subscription::where('id', $this->subscriptionEditId)->first();
			$this->user = $subscription->user_id;
			$this->plan = $subscription->plan_id;
			$this->school = $subscription->school_id;
			$this->payment_details = $subscription->payment_details;
			$this->plan_details = $subscription->plan_details;
			$this->status = $subscription->status;
		}
	}

	public function submitSubscription()
	{	
		$this->validate();

		$data = [
			'user_id' => $this->user,
			'plan_id' => $this->plan,
			'school_id' => $this->school,
			'payment_details' => $this->payment_details,
			'plan_details' => $this->plan_details,
			'status' => $this->status,
		];

		if($this->subscriptionEditId == '')
		{
			Subscription::create($data);

			//session()->flash('message', 'Subscription added successfully');

			$this->alert('success', 'Subscription added successfully');
		}
		else
		{
			Subscription::where('id', $this->subscriptionEditId)->update($data);

			//session()->flash('message', 'Subscription updated successfully');

			$this->alert('success', 'Subscription updated successfully');
		}
		

		return redirect(url('/superadmin/reports/subscriptions'));

		return back();

	}

    public function render()
    {	
    	$users = User::where([['status', 'active'],['usergroup_id', 6]])->get();

    	$plans = Plan::where('is_active', 1)->get();

    	$schools = School::Where('status', 1)->get();

        return view('livewire.superadmin.reports.subscription-form',[
        	'users' => $users,
        	'plans' => $plans,
        	'schools' => $schools,
        ]);
    }
}
