{{-- SPDX-License-Identifier: MIT --}}
<form x-data="conversationReplyState()" action="#" wire:submit.prevent="reply">
	<div class="form-group mb-0">
		<textarea rows="3" class="form-control" wire:model="body" x-on:keydown.enter="submit" placeholder="Type Your Reply"></textarea>	  
	</div>

	<button type="submit" x-ref="submit" class="sr-only">Send</button>
</form>

<script>
		function conversationReplyState()
		{
			return {
				submit()
				{
					this.$refs.submit.click()
				}
			}
		}
</script>
