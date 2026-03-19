<?php

namespace App\Livewire\Conversations;

use Livewire\Component;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Collection;
class ConversationMessages extends Component
{
	public $messages;

	public $conversationId;

	public function mount(Conversation $conversation, Collection $messages)
	{
		$this->conversationId=$conversation->id;
		$this->messages=$messages;
	}

	public function getListeners()
	{
		return [

			'message.created' => 'prependMessage',
			"echo-private:conversations.{$this->conversationId},Conversations\\MessageAdded" => 'prependMessageFromBroadcast',
		];
	}

	public function prependMessage($id)
	{
		$this->messages->prepend(Message::find($id));
	}

	public function prependMessageFromBroadcast($payload)
	{
		$this->prependMessage($payload['message']['id']);
	}

    public function render()
    {
        return view('livewire.conversations.conversation-messages');
    }
}
