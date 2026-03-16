{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('title')
	GegoK12 - Frequently Asked Questions
@endsection

@section('content')
<!-- start -->
<div class="bg-red-600">
	<div class="container mx-auto h-full">
		<div class="text-center flex flex-col justify-center items-center py-5 leading-loose tracking-wider h-full">
		<div class="flex flex-col justify-center items-center py-3 text-white">
					<svg class="fill-current w-24 h-24 "xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512"  xml:space="preserve" width="512px" height="512px"><g><g>
			<g>
				<g>
				<circle cx="256" cy="378.5" r="25" data-original="#000000" class="active-path" data-old_color="#000000" />
				<path d="M256,0C114.516,0,0,114.497,0,256c0,141.484,114.497,256,256,256c141.484,0,256-114.497,256-256     C512,114.516,397.503,0,256,0z M256,472c-119.377,0-216-96.607-216-216c0-119.377,96.607-216,216-216     c119.377,0,216,96.607,216,216C472,375.377,375.393,472,256,472z" data-original="#000000" class="active-path" data-old_color="#000000" />
				<path d="M256,128.5c-44.112,0-80,35.888-80,80c0,11.046,8.954,20,20,20s20-8.954,20-20c0-22.056,17.944-40,40-40     c22.056,0,40,17.944,40,40c0,22.056-17.944,40-40,40c-11.046,0-20,8.954-20,20v50c0,11.046,8.954,20,20,20     c11.046,0,20-8.954,20-20v-32.531c34.466-8.903,60-40.26,60-77.469C336,164.388,300.112,128.5,256,128.5z" data-original="#000000" class="active-path" data-old_color="#000000" />
			</g>
		</g>
	</g></g> </svg>
			<h1 class="text-white text-2xl py-1">{!! __('faq.faq_heading') !!}</h1>
		</div>
		</div>
		
	</div>
</div>
<!-- end -->
<!-- start -->
<div class="bg-gray-100 py-5">
	<div class="container mx-auto">

		<div>
			<!-- <h2 class="text-2xl text-gray-800 py-3 italic text-center">What we Believe</h2> -->
			<div class="my-3 w-full lg:w-2/3 mx-auto px-3 lg:px-0 md:px-0">
				<ul class="list-reset leading-loose">
				<li class="border-b border-gray-300 py-3">
					<h2 class="text-black text-base lg:text-xl md:text-lg font-bold mb-2">{!! __('faq.question_1') !!}</h2>
					<p class="text-base text-black text-justify">
					{!! __('faq.answer_1') !!}
					</p>
				</li>
				<li class="border-b border-gray-300 py-3">
					<h2 class="text-black text-base lg:text-xl md:text-lg font-bold mb-2">{!! __('faq.question_2') !!}</h2>
					<p class="text-base text-black text-justify">
					{!! __('faq.answer_2') !!}
					</p>
				</li>
				<li class="border-b border-gray-300 py-3">
					<h2 class="text-black text-base lg:text-xl md:text-lg font-bold mb-2">{!! __('faq.question_3') !!}</h2>
					<p class="text-base text-black text-justify">
					{!! __('faq.answer_3') !!}
					</p>
				</li>
				<li class="border-b border-gray-300 py-3">
					<h2 class="text-black text-base lg:text-xl md:text-lg font-bold mb-2">{!! __('faq.question_4') !!}</h2>
					<p class="text-base text-black text-justify">
					{!! __('faq.answer_4') !!}
					</p>
				</li>
				<li class="border-b border-gray-300 py-3">
					<h2 class="text-black text-base lg:text-xl md:text-lg font-bold mb-2">{!! __('faq.question_5') !!}</h2>
					<p class="text-base text-black text-justify">
					{!! __('faq.answer_5') !!}
					</p>
				</li>
				</ul>
			</div>
		</div>
	</div>
</div>
<!-- end -->
@endsection 

