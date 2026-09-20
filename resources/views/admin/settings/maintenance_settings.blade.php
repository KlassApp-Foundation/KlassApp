{{-- SPDX-License-Identifier: MIT --}}
{{-- NOTE: unlike general_settings/seodetail_settings (pure fragments), this view
     extends the layout directly, so it overrides the maintenancesettings wrapper. --}}
@extends('layouts.app')
@section('content')
<div class="container mx-auto">
@include('layouts.partials.settings-nav')
<div class="w-full my-3 px-3 md:px-8">
	<h1 class="my-3">Maintenance Settings</h1>
	<form method="POST" action="" enctype="multipart/form-data">
		  @csrf
		<div class="flex items-center my-5">
			<div class="w-1/2 lg:w-2/5 md:w-2/5 flex items-center">
				<p class="text-sm lg:text-base md:text-base">Maintenance Mode</p>
				</div>
				<div class="w-1/2 lg:w-3/5 md:w-3/5 flex justify-end lg:justify-start md:justify-start">
					<label class='toggle-label'>
 						<input type='checkbox' name="maintenance" value="1"  @if(optional(\Auth::user()->school)->detailValue('maintenance') == 1) checked @endif / >
	 					<span class='back'>
						<span class='toggle'></span>
						
 						<span class='label on'>ON</span>
 						
						<span class='label off'>OFF</span>  
						
						</span>
				</label>
			   </div>
			</div>
		<div class="flex items-center my-5">
			<div class="w-1/2 lg:w-2/5 md:w-2/5 flex items-center">
				<p class="text-sm lg:text-base md:text-base">User login Mode</p>
				</div>
				<div class="w-1/2 lg:w-3/5 md:w-3/5 flex justify-end lg:justify-start md:justify-start">
					<label class='toggle-label'>
 						<input type='checkbox' name="login_status" value="1" @if(optional(\Auth::user()->school)->detailValue('login_status') == 1) checked @endif  / >
	 					<span class='back'>
						<span class='toggle'></span>
						
 						<span class='label on'>ON</span>
                        
						<span class='label off'>OFF</span>
						 
						</span>
				</label>
			   </div>
			</div>
			
		<div class="tw-form-row mt-4 mb-16">
            <input type="submit" value="Submit" name="submit" class="btn btn-submit">
        </div>	
	</form>
</div>
</div>
@endsection