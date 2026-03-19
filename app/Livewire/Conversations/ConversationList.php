<?php

namespace App\Livewire\Conversations;

use Livewire\Component;
use Illuminate\Support\Collection;
use App\Models\Conversation;
class ConversationList extends Component
{

	public $conversations;

	public function mount(Collection $conversations)
	{
		$this->conversations = $conversations;
	}

	public function getListeners()
	{
		return [

		'echo-private:users.'. auth()->id() .',Conversations\\ConversationCreated' => 'prependConversationFromBroadcast',


		];
	}

	public function prependConversation($id)
	{
		$this->conversations->prepend(Conversation::find($id));
	}

	public function prependConversationFromBroadcast($payload)
	{
		$this->prependConversation($payload['conversation']['id']);
	}
	
    public function render()
    {
        return view('livewire.conversations.conversation-list');
    }
}
