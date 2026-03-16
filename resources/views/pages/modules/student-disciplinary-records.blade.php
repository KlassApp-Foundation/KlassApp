{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('title')
GegoK12 - student-disciplinary-records | Online School Management 
@endsection

@section('content')
<div class="bg-red-600">
<div class="container mx-auto py-8 px-1">
	<h1 class="lg:text-4xl text-2xl font-bold text-center text-white">Student disciplinary Record Management </h1>
</div>
</div>
<div class="container mx-auto">
<div class=" leading-loose py-5 px-3">
	<p class="text-base text-gray-700 my-3">
	You can record the incident and actions taken towards the Disciplinary of a particular student. Also you can fill out the Student Name, Teacher Name, Incident Date, Incident Detail and Actions taken.
	</p>
	<p class="text-base text-gray-700 my-3">
	It’s hard to teach effectually if the students are uncontrollable. So maintaining discipline is one of the most important responsibilities regarding a school.If you wish to run a disciplined school full of productive and polite students, then our Student Discipline record Management module is a powerful solution that enables tutors to document, track, and report disciplinary incidents in a secure, intuitive, and cost-effective manner. 
	</p>
	<p class="text-base text-gray-700 my-3">
	Without any punishment or special treats, the staff members of the school can experience a dramatic improvement in discipline throughout the school. It offers the school with a single interface for reporting disciplinary issues, maintaining related records, tracking the disciplinary actions and generates actionable reports to compare and analyze the discipline variance of multiple classes. In short, it is ideal for schools belonging to all grade levels and sizes.
	</p>
     <div class="w-full lg:w-10/12 mx-auto bg-gray-200 px-2 py-2 my-5">
     	<img src="{{url('images/disciplinary.png')}}">
     </div>
	<h2 class="text-xl lg:text-2xl md:text-2xl text-gray-800 my-3 italic">Key Features at a Glance</h2>
	<ul class="list-disc text-gray-700 px-4 lg:px-10 md:px-10">
		<li>Perfect discipline management software for any school</li>
		<li>Instant access of any student's disciplinary history</li>
		<li>File multiple discipline occurrences, students involved, follow-up actions, teacher comments and incident locations</li>
		<li>Track problematic students and, produce a complete report.</li>
		<li>Print discipline histories of each student</li>
		<li>Generates individualized messages to report student discipline incidents to parents.</li>
		<li>Analytical and customized report on issues and disciplinary action taken</li>
		<li>Facilitate staff, parents and students to access discipline data confidentially and securely online </li>
		<li>Know which discipline issues are affecting your staff</li>
	</ul>
	<p class="text-base text-gray-700 my-3">
	Document disciplinary incidents easily to the degree of detail you want to record.
	</p>
	<h2 class="text-xl lg:text-2xl md:text-2xl text-gray-800 my-3 italic">Record information such as:</h2>
	<ul class="list-disc text-gray-700 px-4 lg:px-10 md:px-10">
		<li>Students and staff involved</li>
		<li>Location and time</li>
		<li>Teacher remarks</li>
		<li>Follow-up actions</li>
		<li>Notifications to parents</li>
		<li>Actions taken</li>
	</ul>
	
</div>
</div>
</div>
@endsection 