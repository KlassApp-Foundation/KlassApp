{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('title')
GegoK12 - event-management | Online School Management 
@endsection

@section('content')
<div class="bg-red-600">
<div class="container mx-auto py-8 px-1">
	<h1 class="lg:text-4xl text-2xl font-bold text-center text-white">School Events & Event Gallery System </h1>
</div>
</div>
<div class="container mx-auto">
<div class="leading-loose py-5 px-3">
	<p class="text-base text-gray-700 my-3">
	This feature regularly updates School events like Sports Day, Teacher’s Day, and Annual Day, Cultural Meet Day, all kinds of Festival Day, Republic & Independence Day celebrations and other important events. 
	</p>
	<div class="px-2 py-2 mx-auto" style="width: fit-content;">
     <img src="{{url('images/events-gallery.png')}}" class="mx-auto border-8 border-gray-200">
    </div>
	<h2 class="text-2xl text-gray-800 my-3 italic">Why there is a need?</h2>
	<p class="text-base text-gray-700 my-3">
	It actually helps to easily manage events or student activities for school/institution/colleges. With the introduction of event gallery management feature, it becomes easier to schedule, view and program of school events or co-curricular activities. There might be many lists of activities which includequizzes, science, fairs, music, drama and sports.
	</p>
	<p class="text-base text-gray-700 my-3">
	Event gallery system manages for all the upcoming and past events. With this feature, the school can send a message or email to parents informing them about the events and that results Event gallery management works in a systematic way. The entire process becomes hassle-free, which further helps the management team.
	</p>
	<p class="text-base text-gray-700 my-3">
	By introducing the Event gallery management it will ensure that entire school will be aware of day to day activities & events. It will also improve coordination among different departments. This is the best feature that provides end-to-end event details like on which particular date which activity is schedule.  The students can easily access the gallery on their mobile device and download.
	</p>
	<div class="flex flex-col lg:flex-row md:flex-row">
	<div class="w-full lg:w-1/2 md:w-1/2">
	<h2 class="text-2xl text-gray-800 my-3 italic">Features at a glance</h2>
     <ul class="list-disc text-gray-700 px-4 lg:px-10 md:px-10">
     	<li>Academic Calendar</li>
     	<li>Displays School Holidays</li>
     	<li>Monthly / Weekly Activities  </li>
     	<li>Updates about Sports Day, Teacher’s Day, and Annual Day</li>
     	<li>Updates about Cultural Meet Day</li>
     	<li>Updates about all festivals related functions</li>
     </ul>
     </div>
     <div class="w-full lg:w-1/2 md:w-1/2">
     	<img src="{{url('images/img3.png')}}" class="" style="height: 400px;">
     </div>
     </div>
</div>
</div>
@endsection 