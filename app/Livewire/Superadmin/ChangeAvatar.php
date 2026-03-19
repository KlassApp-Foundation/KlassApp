<?php

namespace App\Livewire\Superadmin;

use Livewire\Component;
use App\Models\Userprofile;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
//use Jantinnerezo\LivewireAlert\LivewireAlert;
use Auth;

class ChangeAvatar extends Component
{	
	use WithFileUploads;

	//use LivewireAlert;

	#[Rule('required')]
	public $image;

	public function submitAvatar()
	{	
		$this->validate();

		   if($this->image==null)
        {

            
            $this->addError('image','Image field is required');
            return false;
        }

		//$userprofile = Userprofile::where('user_id', Auth::id())->first();

		if($this->image){
			$photo = $this->image->store('avatars','uploads');
		}

		$data = [
			'avatar' => $photo,
		];

		Userprofile::where('user_id', Auth::id())->update($data);

		//$this->alert('success', 'Changed avatar successfully');

		//return back();
		return redirect(url('/admin/dashboard'));
	}
	
    public function render()
    {
        return view('livewire.superadmin.change-avatar');
    }
}
