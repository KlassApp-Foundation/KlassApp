{{-- SPDX-License-Identifier: MIT --}}
<form action="" class="bg-white" wire:submit.prevent="create">
	<div class="p-4 border-bottom">
		<div class="mb-2 text-muted">
			Send to 
			@foreach($users as $index => $user)
    			<a href="#" class="font-weight-bold">{{ $user['FullName'] }}</a>
    				@if ($index + 1 !== count($user)) , @endif
    		@endforeach
		</div>

		<div x-data="conversationCreateState()">
			<div class="form-group">
				<label for="user" class="sr-only">User</label>
				<input type="text" id="user" class="form-control" autocomplete="off" placeholder="Search users" x-on:input.debounce="search" x-ref="search">
			</div>


			<div v-for="user in suggestions" :key="user.id">
				<a href="#" v-on:click="addUser(user)" class="d-block" v-text="user.name"></a>
			</div>
		</div>
	</div>
	<div class="p-4 border-top">
		<div class="form-group">
			<label for="body" class="sr-only">Message</label>
			<textarea rows="3" id="body" class="form-control" wire:model="body"></textarea>			
		</div>

		<button type="submit" class="btn btn-secondary btn-block">
			Start Conversation
		</button>
	</div>
</form>

@push('scripts')
<script type="text/javascript">
	
	function conversationCreateState(){
		return{
			suggestions: [],
		
			search (e){
				
				var key=e.target.value;
				fetch('/api/search/users?q='+key)
				.then(response => response.json())
				.then((suggestions) => {
					this.suggestions = suggestions
					//console.log(this.suggestions);
				})				
			},

			addUser(user)
			{
				@this.call('addUser',user)
				this.$refs.search.value = ''
				this.suggestions = []
			}
		}
	}
</script>
@endpush