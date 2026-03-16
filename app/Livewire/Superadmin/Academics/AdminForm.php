<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\Usergroup;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Userprofile;

class AdminForm extends Component
{	
	use LivewireAlert;

	#[Rule('required')] 
	public $usergroup;

	#[Rule('required')] 
	public $name;

	#[Rule('required|email')] 
	public $email;

	#[Rule('required|numeric|digits:10')]
	public $mobile;

	public $password;

	public $confirm_password;

	public $school_id;

	//public $school_name;

	public $adminId;

	public $segment;

	public $admin;

	public function mount($id)
	{	
		//dd($id);
		//dd($school_id);
		//dd(\Request::segment ('5'));
		$this->school_id = $id;

		$this->adminId = $id;
		
		$this->segment = \Request::segment ('5');

		//if($this->adminId != '')
		if(\Request::segment ('5') == 'update')
		{	
			//dd($this->school_id);
			$this->admin = User::where('id', $this->adminId)->first();
			//dd('in');
			$this->school_id = $this->admin->school_id;
			$this->usergroup = $this->admin->usergroup_id;
			$this->name = $this->admin->name;
			$this->email = $this->admin->email;
			$this->mobile = $this->admin->mobile_no;
		}
	}

	public function submitAdmin()
	{
		//dd($this->segment);
		//dd($this->school_name);
		//dd($this->school_id);

		$this->validate();

		$data = [
			'school_id' => $this->school_id,
			'usergroup_id' => $this->usergroup,
			'name' => $this->name,
			'email' => $this->email,
			'mobile_no' => $this->mobile,
			'password' => Hash::make($this->password ?? ''),
		];
		
		//dd(\Request::segment ('5'));

		//if($this->adminId == '')
		//if(\Request::segment ('5') == 'create')
		if($this->segment == 'create')
		{//dd('in');

			$validatedData = $this->validate([
            	'email' => 'required|unique:'.School::class,
            	'password'=> 'required|min:8',
            	'confirm_password' => 'required|same:password'
            ]);

			$adminUser = User::create($data);

			Userprofile::create([
		                'school_id'     =>  $this->school_id,
		                'user_id'       =>  $adminUser->id,
		                'usergroup_id'  =>  3,
		                'firstname'     =>  $this->name,
		                'lastname'      =>  'school',
		                'profession'    =>  'admin',
		                // 'avatar'        =>  'uploads/male.png',
		            ]);

			//dd($adminUser);

			$this->alert('success', 'Admin created successfully');
		}
		else
		{//dd('else');
			$adminUser = User::where('id', $this->adminId)->update($data);
			//dd($adminUser);

			$this->alert('success', 'Admin updated successfully');
		}
		//dd($adminUser);
		
		return redirect(url('superadmin/academics/school/detail/'.$this->school_id));
		//return redirect(url('superadmin/academics/schools'));
	}
	
    public function render()
    {	
    	//dd($this->school_name);

    	$usergroups = Usergroup::get();

    	$schoolDetail = School::where('id', $this->school_id)->first();
//dd($this->school_id);
        return view('livewire.superadmin.academics.admin-form',[
        	'usergroups' => $usergroups,
        	'schoolDetail' => $schoolDetail,
        	'admin' => $this->admin
        ]);
    }
}
