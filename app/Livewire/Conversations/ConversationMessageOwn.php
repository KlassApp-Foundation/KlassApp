<?php

namespace App\Livewire\Conversations;

use Livewire\Component;
use App\Models\Message;

class ConversationMessageOwn extends Component
{

	public $message;

	public function mount(Message $message)
	{
		$this->message=$message;
	}

    public function render()
    {
        return view('livewire.conversations.conversation-message-own');
    }
}
