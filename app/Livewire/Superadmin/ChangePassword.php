<?php

namespace App\Livewire\Superadmin;

use Livewire\Component;
use App\Models\User;
use Hash;
use Auth;
use App\Traits\LogActivity;
use App\Traits\Common;
use Livewire\Attributes\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ChangePassword extends Component
{	
	use LogActivity, Common;

	use LivewireAlert;

	#[Rule('required')]
	public $old_password;

	#[Rule('required|min:6')]
	public $new_password;

	#[Rule('required|min:6|same:password')]
	public $confirm_password;

	public function submitPassword()
	{	
		$this->validate();

		$user = User::find(Auth::id());
        $hashedPassword = $user->password;

        if (!Hash::check($this->old_password, $hashedPassword))
		{
			$this->addError('old_password','Old Password is wrong');
            return false;
		}

        if($hashedPassword!='')
        { 
            $user->password = Hash::make($this->new_password);
            $user->save();

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip,'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_CHANGE_PASSWORD,
                'Changed Profile Password.'                        
            );

            $this->alert('success', 'Password changed successfully');       
        } 
        else
        {
        	$this->alert('error', 'Failed to change password. Try again');    
        }

        $this->old_password = '';
        $this->new_password = '';
        $this->confirm_password = '';

        return back();
	}

    public function render()
    {
        return view('livewire.superadmin.change-password');
    }
}
