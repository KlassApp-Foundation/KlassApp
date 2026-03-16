{{-- SPDX-License-Identifier: MIT --}}
<div class="w-full my-3 lg:mx-8 md:mx-8 px-3 lg:px-0 md:px-0">
	<h1 class="my-3">SeoDetail Settings</h1>
	<form method="POST" action="" enctype="multipart/form-data">
		  @csrf
		<div class="tw-form-group">
		<label class="tw-form-label">Site Title</label>
			<input type="text" name="sitetitle" value="{!!(\config::get('settings.sitetitle'))!!}" class="tw-form-control w-full lg:w-128">
			<span class="text-red">{{$errors->first('sitetitle')}}</span>
		</div>

		<div class="tw-form-group">
		<label class="tw-form-label">Site Description</label>
			<input type="text" name="site_description" value="{!!(\config::get('settings.site_description'))!!}" class="tw-form-control w-full lg:w-128">
			<span class="text-red">{{$errors->first('site_description')}}</span>
		</div>

		<div class="tw-form-group">
		<label class="tw-form-label">Site Keyword</label>
			<input type="text" name="site_keyword" value="{!!(\config::get('settings.site_keyword'))!!}"class="tw-form-control w-full lg:w-128">
			<span class="text-red">{{$errors->first('site_keyword')}}</span>
		</div>

		<div class="tw-form-group">
		<label class="tw-form-label">Twitter Handle</label>
			<input type="text" name="twitter_handle" value="{!!(\config::get('settings.twitter_handle'))!!}"class="tw-form-control w-full lg:w-128">
			<span class="text-red">{{$errors->first('twitter_handle')}}</span>
		</div>

		<div class="tw-form-group">
		<label class="tw-form-label">Twitter Description</label>
			<input type="text" name="twitter_description" value="{!!(\config::get('settings.twitter_description'))!!}"class="tw-form-control w-full lg:w-128">
			<span class="text-red">{{$errors->first('twitter_description')}}</span>
		</div>



		<div class="tw-form-group">
		<label class="tw-form-label">Facebook Site URL</label>
			<input type="url" name="facebook_site_url" value="{!!(\config::get('settings.facebook_site_url'))!!}"class="tw-form-control w-full lg:w-128">
			<span class="text-red">{{$errors->first('facebook_site_url')}}</span>
		</div>

		

		<div class="tw-form-row mt-4 mb-16">
            <input type="submit" value="Submit" name="submit" class="btn btn-submit">
        </div>	

	</form>
</div>


