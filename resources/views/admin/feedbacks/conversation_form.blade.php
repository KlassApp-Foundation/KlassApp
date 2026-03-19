{{-- SPDX-License-Identifier: MIT --}}
<div class="w-1/2">
	<form method="post" action="{{ url('admin/feedback/edit/'.$feedback->id)}}" class="form-horizontal" id="contact">
	{{ csrf_field() }} 
	    <div class="form-group{{ $errors->has('message') ? ' has-error' : '' }}">
	        <textarea rows="3" class="tw-form-control w-full"  placeholder="Enter your message here" name="message" required="required">{{ old('message') }}</textarea>
	        <small class="text-red-500 text-xs font-semibold">{{ $errors->first('message') }}</small>
	    </div>
	    <div class="form-group flex my-3">
	        <input value="Submit" class="btn btn-primary blue-bg text-white rounded px-3 py-1 text-sm font-medium" type="submit" onclick="this.disabled=true;this.form.submit();"> 
	        <div class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 ml-3 text-sm font-medium"><a href="" class='btn btn-default'>Reset</a></div>
	    </div>
	</form>
</div>