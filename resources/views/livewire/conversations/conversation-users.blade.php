{{-- SPDX-License-Identifier: MIT --}}
<div>
	<div class="d-flex justify-content-between">
 		<div class="font-weight-bold text-muted">
    		@foreach($users as $user)
    			{{ $user->present()->FullName }}
    			@if ($users->last() != $user) , @endif
    		@endforeach
    	</div>
    	<a href="#">Add Someone</a>
  	</div>
</div>