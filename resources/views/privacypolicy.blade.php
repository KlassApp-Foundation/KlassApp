{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('content')
{{-- Draft banner --}}
@if(config('app.legal_draft_banner'))
<div data-testid="legal-draft-banner" class="bg-yellow-50 border-b border-yellow-300 py-2 px-4 text-center text-sm text-yellow-800">
    {{ config('app.legal_draft_banner') }}
</div>
@endif

<!-- start -->
<div class="bg-red-600 py-16">
	<div class="container mx-auto h-full">
		<div class="text-center flex flex-col justify-center items-center py-5 leading-loose tracking-wider h-full">
		<h1 class="text-white font-plex text-4xl">{!! __('policy.title') !!}</h1>
		</div>
	</div>
</div>
<!-- end -->
<!-- start -->
<div class="bg-white py-5 px-3 lg:px-0 md:px-0">
	<div class="container mx-auto">
		<div class="w-full lg:w-2/3 lg:mx-auto leading-loose py-2">
			@foreach(range(1, 16) as $n)
			<h2 class="text-2xl text-gray-800 py-3">{!! __("policy.s{$n}_heading") !!}</h2>
			<div class="text-base text-gray-700 my-2">{!! __("policy.s{$n}_body") !!}</div>
			@endforeach
		</div>
	</div>
</div>
<!-- end -->
@endsection
