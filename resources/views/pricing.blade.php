{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('content')
<div class="bg-red-600">
	<div class="container mx-auto py-24">
		<div class="text-center leading-loose tracking-wider">
			<h1 class="text-white text-2xl lg:text-5xl uppercase font-bold tracking-widest">try free for 180 days</h1>
			<h3 class="text-white text-lg lg:text-2xl font-medium ">Flexible pricing plans to best fit your school.</h3>
			<div class="pt-12 lg:pb-10">
				<a href="{{ url('/register') }}" class="uppercase text-white border-2 font-semibold px-4 lg:px-8 py-2 lg:py-4 hover:bg-white hover:text-black">start a free trail</a>
			</div>
		</div>
	</div>
</div>

<div class="container-wrapper">
	<div class="container mx-auto py-8">
		<div class="flex flex-col">
			<h1 class="font-plex text-3xl font-bold my-8 text-center">Simplified Pricing</h1>
			<p class="w-full lg:w-2/3 mx-auto text-center font-exo">GegoK12 is completly cloud based solution and you pay to use all modules based on your student strength. No additional cost or setup cost.</p>
			<div class="w-full lg:w-1/2 mx-auto flex flex-col lg:flex-row md:flex-row mt-8 items-center px-3 lg:p-0 md:px-0">
				<div class="w-full lg:w-1/2 md:w-1/2 bg-red-500 p-4 rounded-lg">
					<ul class="list-outside list-disc px-8">
						<li class="my-4 text-white font-medium">Web app for School Admin, Teachers, Staffs, Librarian & Students.</li>
						<li class="my-4 text-white font-medium">Mobile Apps for Parents</li>
						<li class="my-4 text-white font-medium">Unlimited Bandwidth</li>
						<li class="my-4 text-white font-medium">Unlimited Messaging</li>
						<li class="my-4 text-white font-medium">Unlimited Storage</li>
					</ul>
				</div>
				<div class="w-full lg:w-1/2 md:w-1/2 my-3 lg:my-0 md:my-0 bg-red-600 p-8 lg:rounded-r-lg md:rounded-r-lg">
					<div class="text-4xl text-white font-exo font-extrabold">INR 500</div>
					<div class="text-xl text-white font-exo">Per Student / Per Year</div>
					<p class="mt-2 text-sm text-white font-exo">* 50% Off for Non-profit schools.</p>
					<p class="mt-2 text-xs text-white font-exo">** Local taxes (VAT, GST, etc.) will be charged in addition to the prices mentioned.</p>
				</div>
			</div>
			<div class="w-full lg:w-2/3 mx-auto flex  justify-center my-6 lg:my-24 md:my-24">
				<a href="{{ url('/register') }}" class="mx-8 bg-blue-600 text-white px-3 py-2 rounded tracking-wider">TRY NOW</a>
				<a href="{{ url('/subscription') }}" class="mx-8 bg-blue-600 text-white px-3 py-2 rounded tracking-wider">BUY NOW</a>
			</div>
		</div>
	</div>
</div>

@endsection 