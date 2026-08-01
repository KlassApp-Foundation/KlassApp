<?php

namespace App\Livewire\Superadmin\Settings;

use App\Services\Superadmin\CoAdminService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

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
        $service = app(CoAdminService::class);

        try {
            if ($this->editingId) {
                $service->update((int) $this->editingId, [
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => $this->password ?: null,
                ]);
                session()->flash('message', 'Co-admin updated.');
            } else {
                $service->create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => $this->password,
                ]);
                session()->flash('message', 'Co-admin created.');
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
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
        try {
            app(CoAdminService::class)->delete((int) $id, auth()->user());
            session()->flash('message', 'Co-admin removed.');
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first() ?? 'Unable to remove co-admin.');
        }
    }

    public function render()
    {
        $coAdmins = User::whereIn('usergroup_id', [2])->paginate(20);
        return view('livewire.superadmin.settings.co-admins', ['coAdmins' => $coAdmins]);
    }
}
