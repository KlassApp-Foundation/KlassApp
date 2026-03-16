<?php

namespace App\Livewire\Conversations;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Events\Conversations\ConversationCreated;
use App\Models\Conversation;
class ConversationCreate extends Component
{

	public $users = [];

	public $body = '';

	public function addUser($user)
	{
		$this->users[]=$user;
	}

	public function create()
	{
		$this->validate([

			'body' => 'required',
			'users' => 'required',

			]);
		//dd($this->users);

		$conversation = Conversation::create([
			'uuid' => Str::uuid()
			]);

		$conversation->messages()->create([

			'user_id' =>auth()->id(),
			'body' => $this->body,

			]);

			$conversation->users()->sync($this->collectUserIds());

			broadcast(new ConversationCreated($conversation))->toOthers();

			return redirect()->route('admin.conversations.show',$conversation);

			


	}

	public  function collectUserIds()
	{
		return collect($this->users)->merge([auth()->user()])->pluck('id')->unique();
	}
    public function render()
    {
        return view('livewire.conversations.conversation-create');
    }
}
