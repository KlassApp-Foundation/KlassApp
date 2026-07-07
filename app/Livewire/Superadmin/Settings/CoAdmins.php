<?php

namespace App\Livewire\Superadmin\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CoAdmins extends Component
{
    use WithPagination;

    public $showForm = false;
    public $name = '';
    public $email = '';
    public $password = '';
    public $editingId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => 'required|min:6',
    ];

    public function updatedEditingId($id)
    {
        if ($id) {
            $user = User::find($id);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->password = '';
        }
    }

    public function save()
    {
        if ($this->editingId) {
            $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $this->editingId,
                'password' => 'nullable|min:6',
            ]);
            $data = ['name' => $this->name, 'email' => $this->email];
            if ($this->password) $data['password'] = Hash::make($this->password);
            User::find($this->editingId)->update($data);
            session()->flash('message', 'Co-admin updated.');
        } else {
            $this->validate();
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'usergroup_id' => 2,
                'status' => 'active',
            ]);
            session()->flash('message', 'Co-admin created.');
        }
        $this->reset(['showForm', 'name', 'email', 'password', 'editingId']);
    }

    public function edit($id)
    {
        $this->editingId = $id;
        $this->updatedEditingId($id);
        $this->showForm = true;
    }

    public function delete($id)
    {
        User::find($id)?->delete();
        session()->flash('message', 'Co-admin removed.');
    }

    public function render()
    {
        $coAdmins = User::whereIn('usergroup_id', [2])->paginate(20);
        return view('livewire.superadmin.settings.co-admins', ['coAdmins' => $coAdmins]);
    }
}
