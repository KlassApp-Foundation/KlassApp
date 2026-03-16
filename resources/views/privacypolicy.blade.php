{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('content')
<!-- start -->
<div class="bg-red-600 py-16">
	<div class="container mx-auto h-full">
		<div class="text-center flex flex-col justify-center items-center py-5 leading-loose tracking-wider h-full">
		<h1 class="text-white font-plex text-4xl">{!! __('policy.privacy_policy') !!}</h1>
		<h2 class="text-base font-exo text-white">{!! __('policy.text_1') !!}</h2>
		</div>
		
	</div>
</div>
<!-- end -->
<!-- start -->
<div class="bg-white py-5 px-3 lg:px-0 md:px-0">
	<div class="container mx-auto">
		<div class="w-full lg:w-2/3 lg:mx-auto leading-loose py-2">
			<h2 class="text-2xl text-gray-800 py-3 italic">{!! __('policy.text_2') !!}</h2>
			<p class="text-base text-gray-700 my-2">{!! __('policy.text_3') !!}</p>
			<p class="text-base text-gray-700 my-2">{!! __('policy.text_4') !!}</p>
			<p class="text-base text-gray-700 my-2">{!! __('policy.text_5') !!}</p>
			<h2 class="text-2xl text-gray-800 py-3">{!! __('policy.text_6') !!}</h2>
			<p class="text-base text-gray-700 my-2">
					{!! __('policy.text_7') !!}
			</p>
			<p class="text-base text-gray-700 my-2">
				{!! __('policy.text_8') !!}
			</p>
			<p class="text-base text-gray-700 my-2">
				{!! __('policy.text_9') !!}
			</p>
		<h2 class="text-2xl text-gray-800 py-3">{!! __('policy.text_10') !!}</h2>
		<p class="text-base text-gray-700 my-2">
			{!! __('policy.text_11') !!}
		</p>
		<p class="text-base text-gray-700 my-2">
			{!! __('policy.text_12') !!}
		</p>
		</div>
	</div>
</div>
<!-- end -->
@endsection 

