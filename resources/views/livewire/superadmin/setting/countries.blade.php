{{-- SPDX-License-Identifier: MIT --}}
<div>
	@if (session()->has('message'))
	    <div class="alert alert-success">
	        {{ session('message') }}
	    </div>
    @endif
    
    <div class="" style="display:flex;justify-content: space-between;padding:10px;align-items:center;">
		<div class="">
		    <p style="font-size:1.25rem; font-weight:700;">Countries</p>
		</div>
	</div>

    {{ $this->table }}
</div>