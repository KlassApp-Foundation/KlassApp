<?php

namespace App\Livewire\Conversations;

use Livewire\Component;
use Illuminate\Support\Collection;
class ConversationUsers extends Component
{
	public $users;

	public function mount(Collection $users)
	{
		$this->users = $users;
	}

    public function render()
    {
        return view('livewire.conversations.conversation-users');
    }
}
