<?php

namespace App\Livewire\Conversations;

use Livewire\Component;
use App\Models\Conversation;
use App\Events\Conversations\MessageAdded;
class ConversationReply extends Component
{
	public $conversation;

	public $body = '';

	public function mount(Conversation $conversation)
	{
		$this->conversation = $conversation;
	}

	public function reply()
	{
		$this->validate([

			'body' => 'required'
			]);

		$message = $this->conversation->messages()->create([
			'user_id' => auth()->id(),
			'body' => $this->body

			]);

		$this->conversation->update([
			'last_message_at' => now()
			]);

		foreach($this->conversation->others as $user)
		{
			$user->conversations()->updateExistingPivot($this->conversation, [


				'read_at' => null


				]);
		}

		broadcast(new MessageAdded($message))->toOthers();

		$this->emit('message.created', $message->id);

		$this->body='';
	}
    public function render()
    {
        return view('livewire.conversations.conversation-reply');
    }
}
