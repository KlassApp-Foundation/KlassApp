{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('title')
GegoK12 - Contact Form 
@endsection

@section('content')
<div class="bg-red-600">
	<div class="container mx-auto py-16">
		<h1 class="text-3xl text-white text-center font-plex">Contact</h1>
	</div>
</div>
<div class="container mx-auto">
	<div class="flex flex-col lg:flex-row">
		<div class="w-full lg:w-2/3 px-8 lg:px-24 lg:my-16 md:my-16 my-5">
		<h2 class="text-2xl font-plex mb-8">Contact Form</h2>
		<p class="text-sm leading-relaxed lg:mr-24 mr-0">Enter your query or feedback in the form given below. Our support team will get back to you. You can also directly mail us at support@gegosoft.com</p>
		<div class="lg:mr-24 mr-0">
			<!-- <form action="{{url('/contact')}}" method="post" enctype="multipart/form-data">
				{{csrf_field()}} -->
				<contact url="{{url('/')}}"></contact>
			<!-- </form> -->
		</div>
		</div>
		<div class="w-full lg:w-1/3 p-4 lg:pr-32 lg:mt-32 md:mt-32 mt-6">
			<h3 class="font-bold text-xl mb-4">Head Office</h3>
			<h4 class="font-exo">Gegosoft</h4>
			<p class="font-exo">8-6/8, Vaigai Nathi Street</p>
			<p class="font-exo">Mahathma Gandhi Nagar</p>
			<p class="font-exo">Madurai - 625014</p>
			<p class="font-exo">Tamilnadu. India</p>
			<div class="flex my-2 items-center">
				<div class="text-red-600">
					<svg class="w-8 h-8 fill-current" xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56"><path d="M 27.9999 51.9063 C 41.0546 51.9063 51.9063 41.0781 51.9063 28 C 51.9063 14.9453 41.0312 4.0937 27.9765 4.0937 C 14.8983 4.0937 4.0937 14.9453 4.0937 28 C 4.0937 41.0781 14.9218 51.9063 27.9999 51.9063 Z M 27.9999 47.9219 C 16.9374 47.9219 8.1014 39.0625 8.1014 28 C 8.1014 16.9609 16.9140 8.0781 27.9765 8.0781 C 39.0155 8.0781 47.8983 16.9609 47.9219 28 C 47.9454 39.0625 39.0390 47.9219 27.9999 47.9219 Z M 22.3749 33.5312 C 27.8358 39.0156 34.7968 42.4844 38.9218 38.3828 C 39.0858 38.2188 39.2030 38.0781 39.3202 37.9141 C 40.3983 36.7188 40.7030 35.1250 39.3202 34.0937 C 38.2421 33.3203 37.0936 32.5234 35.1483 31.1641 C 33.7890 30.2031 32.8983 30.4375 31.8436 31.4922 L 30.9062 32.4063 C 30.6249 32.6641 30.1796 32.6406 29.8749 32.4531 C 29.0546 31.9609 27.6718 30.8594 26.3593 29.5469 C 25.0702 28.2578 23.8983 26.8047 23.4530 26.0312 C 23.2890 25.7734 23.1718 25.3516 23.4999 25.0469 L 24.4140 24.0625 C 25.4687 22.9609 25.7030 22.1172 24.7187 20.7344 L 21.8358 16.6563 C 20.8514 15.2734 19.4218 15.5312 17.8514 16.7031 C 17.7343 16.7968 17.6405 16.8906 17.5468 16.9844 C 13.4218 21.1094 16.9140 28.0703 22.3749 33.5312 Z"/></svg>
				</div>
				<div>+ 91.452.4220409</div>
			</div>
			<div >
				<div class="text-red-600">
					<svg class="w-8 h-8 fill-current" xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56">
					  <path fill-rule="evenodd" d="M15.724,40.6279381 C34.592,40.6279381 44.912,24.9959381 44.912,11.4399381 C44.912,10.9959381 44.912,10.5539381 44.882,10.1139381 C46.8896627,8.66175977 48.6227109,6.86369685 50,4.80393806 C48.1277581,5.63354161 46.1416586,6.17759536 44.108,6.41793806 C46.249488,5.13590218 47.852247,3.11948203 48.618,0.743938063 C46.604315,1.93884869 44.4012922,2.7809621 42.104,3.23393806 C38.9241655,-0.14727338 33.8714387,-0.974836769 29.7790985,1.21529792 C25.6867584,3.40543261 23.57255,8.06858952 24.622,12.5899381 C16.3737915,12.1764371 8.68895156,8.28058461 3.48,1.87193806 C0.757245513,6.55922752 2.14797577,12.555657 6.656,15.5659381 C5.0234824,15.5175532 3.42655874,15.0771644 2,14.2819381 L2,14.4119381 C2.00133627,19.295124 5.44350298,23.5010084 10.23,24.4679381 C8.71973919,24.8798182 7.13516117,24.9400267 5.598,24.6439381 C6.94189586,28.8227837 10.7931491,31.6855058 15.182,31.7679381 C11.5494701,34.6228019 7.06211902,36.1725935 2.442,36.1679381 C1.62580714,36.1663712 0.810408131,36.116953 0,36.0199381 C4.6912719,39.0304912 10.1498268,40.6273719 15.724,40.6199381" transform="translate(3 7)"/>
					</svg>
				</div>
			</div>
			<hr class="h-1 bg-gray-300">

		</div>
	</div>
</div>
@endsection