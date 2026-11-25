{{-- SPDX-License-Identifier: MIT --}}
{{-- KlassApp Terms of Service (/terms). Text comes from lang/en/terms.php. --}}
{{-- Remove the draft banner by clearing config('app.legal_draft_banner'). --}}
@extends('layouts.main')

@section('title', 'Terms of Service — KlassApp')

@section('content')
<div class="bg-red-600 py-10">
    <div class="container mx-auto text-center">
        <h1 class="text-white font-plex text-4xl">{!! __('terms.title') !!}</h1>
    </div>
</div>

<div class="bg-white py-6 px-3 lg:px-0">
    <div class="container mx-auto">
        <div class="w-full lg:w-2/3 lg:mx-auto leading-loose">

            @if(config('app.legal_draft_banner'))
            <p class="text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-300 rounded px-4 py-2 mb-6" data-testid="legal-draft-banner">{{ config('app.legal_draft_banner') }}</p>
            @endif

            @foreach(range(1, 14) as $n)
            @php $h = __('terms.s'.$n.'_heading'); $b = __('terms.s'.$n.'_body'); @endphp
            @if($h !== 'terms.s'.$n.'_heading')
            <h2 class="text-xl font-semibold text-gray-800 mt-6 mb-1">{{ $h }}</h2>
            <div class="text-base text-gray-700 my-2">{!! $b !!}</div>
            @endif
            @endforeach

        </div>
    </div>
</div>
@endsection
