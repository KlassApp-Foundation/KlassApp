<?php

namespace App\Livewire\Superadmin\Setting;

use Livewire\Component;
use App\Models\Plan;
use Livewire\Attributes\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class PlanForm extends Component
{	
	use LivewireAlert;

	#[Rule('required')]
	public $cycle;

	#[Rule('required')]
	public $name;

	#[Rule('required')]
	public $order;

	#[Rule('required')]
	public $status = 1;

	#[Rule('required')]
	public $amount;

	#[Rule('required')]
	public $no_of_members;

	#[Rule('required')]
	public $no_of_events;

	#[Rule('required')]
	public $no_of_folders;

	#[Rule('required')]
	public $no_of_files;

	#[Rule('required')]
	public $no_of_videos;

	#[Rule('required')]
	public $no_of_audios;

	#[Rule('required')]
	public $no_of_bulletins;

	#[Rule('required')]
	public $no_of_groups;

	public $planEditId;

	public function mount($id)
	{
		$this->planEditId = $id;

		if($this->planEditId != '')
		{
			$planEdit = Plan::where('id', $this->planEditId)->first();
			$this->cycle = $planEdit->cycle;
			$this->name = $planEdit->name;
			$this->order = $planEdit->order;
			$this->status = $planEdit->is_active;
			$this->amount = $planEdit->amount;
			$this->no_of_members = $planEdit->no_of_members;
			$this->no_of_events = $planEdit->no_of_events;
			$this->no_of_folders = $planEdit->no_of_folders;
			$this->no_of_files = $planEdit->no_of_files;
			$this->no_of_videos = $planEdit->no_of_videos;
			$this->no_of_audios = $planEdit->no_of_audios;
			$this->no_of_bulletins = $planEdit->no_of_bulletins;
			$this->no_of_groups = $planEdit->no_of_groups;
		}
	}

	public function submitPlan()
	{	
		$this->validate();
		
		$data = [
			'cycle' => $this->cycle,
			'name' => $this->name,
			'order' => $this->order,
			'is_active' => $this->status,
			'amount' => $this->amount,
			'no_of_members' => $this->no_of_members,
			'no_of_events' => $this->no_of_events,
			'no_of_folders' => $this->no_of_folders,
			'no_of_files' => $this->no_of_files,
			'no_of_videos' => $this->no_of_videos,
			'no_of_audios' => $this->no_of_audios,
			'no_of_bulletins' => $this->no_of_bulletins,
			'no_of_groups' => $this->no_of_groups,
		];

		if($this->planEditId == '')
		{
			Plan::create($data);

			//session()->flash('message', 'Plan added successfully');

			$this->alert('success', 'Plan added successfully');
		}
		else
		{
			Plan::where('id', $this->planEditId)->update($data);

			//session()->flash('message', 'Plan updated successfully');

			$this->alert('success', 'Plan updated successfully');
		}

		return redirect(url('/superadmin/setting/plan/detail/'.$this->planEditId));
	}

    public function render()
    {
        return view('livewire.superadmin.setting.plan-form');
    }
}
