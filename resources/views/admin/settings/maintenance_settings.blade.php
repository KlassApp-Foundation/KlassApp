{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')
@section('content')
<div class="container mx-auto">
<div class="w-full my-3 lg:mx-8 md:mx-8 px-3 lg:px-0 md:px-0">
	<h1 class="my-3">Maintenance Settings</h1>
	<form method="POST" action="" enctype="multipart/form-data">
		  @csrf
		<div class="flex items-center my-5">
			<div class="w-1/2 lg:w-2/5 md:w-2/5 flex items-center">
				<p class="text-sm lg:text-base md:text-base">Maintenance Mode</p>
				</div>
				<div class="w-1/2 lg:w-3/5 md:w-3/5 flex justify-end lg:justify-start md:justify-start">
					<label class='toggle-label'>
 						<input type='checkbox' name="maintenance" value="1"  @if(Config::get('settings.maintenance')==1) checked @endif / >
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
				<p class="text-sm lg:text-base md:text-base">User Registeration Mode</p>
				</div>
				<div class="w-1/2 lg:w-3/5 md:w-3/5 flex justify-end lg:justify-start md:justify-start">
					<label class='toggle-label'>
 						<input type='checkbox' name="register" value="1"  @if(Config::get('settings.register')==1) checked @endif / >
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
 						<input type='checkbox' name="login_status" value="1" @if(Config::get('settings.login_status')==1) checked @endif  / >
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