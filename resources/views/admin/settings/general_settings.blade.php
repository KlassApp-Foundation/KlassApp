{{-- SPDX-License-Identifier: MIT --}}
<form method="POST" action="" enctype="multipart/form-data" class="w-full">
	  @csrf
	<div class="tw-form-group">
	<label class="tw-form-label">Site Title</label>
		<input type="text" name="sitetitle" value="{{ old('sitetitle', \Config::get('settings.sitetitle')) }}" class="tw-form-control w-full lg:w-128">
		<span class="text-danger">{{$errors->first('sitetitle')}}</span>
	</div>
	<div class="tw-form-group">
	<label class="tw-form-label">School Name</label>
		<input type="text" name="school_name" value="{{ old('school_name', optional(Auth::user()->school)->name) }}" class="tw-form-control w-full lg:w-128">
		<span class="text-danger">{{$errors->first('school_name')}}</span>
		<p class="text-sm text-gray-500 mt-1">This is your school's display name (nav, reports, invoices). Platform branding is managed separately by KlassApp.</p>
	</div>
	<div class="tw-form-group">
		<label class="tw-form-label">Site Logo</label>
		<input type="file" name="sitelogo" class="p-2 border border-dashed lg:w-128">
		<img src={{asset(\Config::get('settings.sitelogo'))}} class="lg:w-64 h-auto my-2">
		<span class="text-danger">{{$errors->first('sitelogo')}}</span>
	</div>
	<div class="tw-form-group">
		<label class="tw-form-label">Site Favicon</label>

		<input type="file" name="favicon" class="p-2 border border-dashed lg:w-128">
		<img src={{asset(\Config::get('settings.favicon'))}} class="lg:w-64 h-auto my-2">

	</div>
	<input type="submit" value="Submit" name="submit" class="btn btn-submit">
</form>
