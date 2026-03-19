{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('title')
GegoK12 - student-journal | Online School Management 
@endsection

@section('content')
<div class="bg-red-600">
<div class="container mx-auto py-8 px-1">
	<h1 class="lg:text-4xl text-2xl  font-bold text-center text-white">School Magazine & Journal Publishing </h1>
</div>
</div>
<div class="container mx-auto">
<div class="leading-loose py-5 px-3">
   
	<p class="text-base text-gray-700 my-3">
	The students are encouraged to represent School Magazine and Journal publishing. One can publishshort stories, articles, plays, poems and activities that have literary and academic merit. </p>
	<p class="text-base text-gray-700 my-3">
	As a student, there’s no better way to share your voice, ideas and creativity with your community than through the pages of your own publication. Having the freedom to publish content you’ve created and share it with your peers guarantees an unmatched feeling of accomplishment and pride.</p>
	<p class="text-base text-gray-700 my-3">
	The school children could respond well to texts that delight, intrigue, challenge and inspire them—texts written especially for them. Remember that our students live in a multicultural, diverse society and that School Magazine reflects this. </p>
	<p class="text-base text-gray-700 my-3">
	The students thus can explore their identities and appreciate insights into the world around them. School magazines provide an outlet for students to create and express in ways that they might not otherwise get to so school magazine gives students a platform.</p>

	<div class="px-2 py-2 my-8">
		 <img src="{{url('images/school-magazine.png')}}" class="mx-auto border-8 border-gray-200">
	</div>
	<h2 class="text-2xl text-gray-800 my-3 italic">What works best? </h2>
	<ul class="list-disc text-gray-700 px-4 lg:px-10 md:px-10">
		<li>Creative, Energetic and accessible writing </li>
		<li>Fast-paced action, twists, credible characters</li>
		<li>Good concepts and positive messages</li>
		<li>Journal publishing with focused intent</li>
	</ul>

	<p class="text-base text-gray-700 my-3">
	Finally by using this software one can easily able to upload any kind of Magazine and Journal with details incurring like Magazine name, Select Year, Upload Cover page, Upload Magazine File and Upload category.</p>
</div>

</div>
@endsection 