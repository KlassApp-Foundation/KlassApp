{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('title')
GegoK12 - school-attendance-management |  Online School Management 
@endsection

@section('content')
<!-- start -->
<div class="bg-red-600 py-8 lg:py-16 md:py-8">
	<div class="container mx-auto">
		<div class="text-center tracking-wider px-1">
		<h1 class="text-white font-plex text-2xl lg:text-4xl">School Attendance Management</h1>
		</div>
	</div>
</div>
<!-- end -->
<!-- <div class="bg-gray-400">
	<div class="container mx-auto py-16">
		<h1 class="text-4xl font-bold">School Attendance Management</h1>
	</div>
</div> -->
<div class="container mx-auto py-8 px-3">
<div class="flex flex-col lg:flex-row justify-between lg:items-center">
<div class="w-full lg:w-2/3">
<p class="leading-loose text-justify mb-8 text-gray-700">The process of tracking attendance at schools has always been a difficult task.  Teachers recognize that even when the students are constantly present, the effective discharge of their professional capabilities faces many problems. Tracking a school attendance is no simple task. Attendance data are often misleading and these have safety issues for parents and children. For example, if a student is missing or absent from school, parents and the school authorities can follow up with each other to know why the child is missing in the school and if he or she needs any assistant from supposed danger. In this method, the challenging part is consistency. Tracking attendance is mostly inconsistent when it comes between classrooms, and schools.</p>
<p class="leading-loose text-justify mb-8 text-gray-700">Teachers who are employed within the same school track attendance differently even with a proper attendance tracking system. Many teachers account attendance at the beginning of the class which makes tardiness as absence. Some teachers are not compliant with tracking the attendance specially when it comes with no motivation to do so. In some cases when teachers or other staffs are meeting the absent student during the class will fail to report the presence of the student in class. In order to tackle such inconsistencies, schools could establish a software for monitoring teacher’s attendance tracking routine. Establish a proper school attendance tracking tool or device for effectively manage the attendance.</p>
</div>
<div class="w-full lg:w-1/4">
	<img src="{{url('images/student-attendance.png')}}" class="mx-auto">
</div>
</div>
<p class="leading-loose text-justify mb-8 text-gray-700">Gego K12 is a school management software that comes with a foolproof tool in tracking school attendance. This tool collects daily class attendance from each class and updates it to the parents via notification through the app. Thus, if a student is playing hooky or is missing from class, parents can immediately reach out to the concerned authorities.</p>

<div class="flex flex-wrap">
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
		<svg class="h-8 fill-current" id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m471.811 57.007h-51.854v-18.89c0-8.284-6.716-15-15-15s-15 6.716-15 15v18.89h-267.915v-18.89c0-8.284-6.716-15-15-15s-15 6.716-15 15v18.89h-51.853c-22.16 0-40.189 18.029-40.189 40.189v351.497c0 22.16 18.029 40.189 40.189 40.189h431.621c22.16 0 40.189-18.029 40.189-40.189v-351.497c.001-22.16-18.028-40.189-40.188-40.189zm10.189 391.686c0 5.523-4.666 10.189-10.189 10.189h-431.622c-5.523 0-10.189-4.666-10.189-10.189v-351.497c0-5.523 4.666-10.189 10.189-10.189h51.853v18.35c0 8.284 6.716 15 15 15s15-6.716 15-15v-18.35h267.915v18.35c0 8.284 6.716 15 15 15s15-6.716 15-15v-18.35h51.854c5.523 0 10.189 4.666 10.189 10.189z"/><path d="m425.644 170.836c-5.857-5.857-15.355-5.857-21.213 0l-14.612 14.612-14.612-14.612c-5.857-5.857-15.355-5.857-21.213 0s-5.858 15.355 0 21.213l14.612 14.612-14.612 14.612c-5.858 5.857-5.858 15.355 0 21.213 2.929 2.929 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394l14.612-14.612 14.612 14.612c2.929 2.929 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394c5.858-5.857 5.858-15.355 0-21.213l-14.612-14.612 14.612-14.612c5.86-5.858 5.86-15.356.002-21.213z"/><path d="m291.825 170.836c-5.856-5.858-15.354-5.858-21.213 0l-14.612 14.612-14.613-14.613c-5.857-5.857-15.355-5.857-21.213 0s-5.858 15.355 0 21.213l14.613 14.612-14.613 14.612c-5.858 5.857-5.858 15.355 0 21.213 2.929 2.93 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394l14.614-14.611 14.613 14.613c2.929 2.929 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394c5.858-5.857 5.858-15.355 0-21.213l-14.613-14.612 14.613-14.612c5.859-5.859 5.859-15.357 0-21.214z"/><path d="m158.006 170.836c-5.857-5.857-15.355-5.857-21.213 0l-14.612 14.612-14.612-14.612c-5.857-5.857-15.355-5.857-21.213 0s-5.858 15.355 0 21.213l14.612 14.612-14.612 14.612c-5.858 5.857-5.858 15.355 0 21.213 2.929 2.929 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394l14.612-14.612 14.612 14.612c2.929 2.929 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394c5.858-5.857 5.858-15.355 0-21.213l-14.612-14.612 14.612-14.612c5.86-5.858 5.86-15.356.002-21.213z"/><path d="m425.644 320.943c-5.857-5.857-15.355-5.857-21.213 0l-14.612 14.613-14.612-14.613c-5.856-5.858-15.354-5.858-21.213 0-5.858 5.857-5.858 15.355 0 21.213l14.613 14.613-14.613 14.613c-5.858 5.857-5.858 15.355 0 21.213 2.929 2.929 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394l14.612-14.613 14.612 14.613c2.929 2.93 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394c5.858-5.857 5.858-15.355 0-21.213l-14.613-14.613 14.613-14.613c5.86-5.857 5.86-15.355.002-21.213z"/><path d="m291.825 320.943c-5.857-5.857-15.355-5.857-21.213 0l-14.612 14.613-14.613-14.613c-5.857-5.857-15.355-5.857-21.213 0s-5.858 15.355 0 21.213l14.613 14.613-14.613 14.613c-5.858 5.857-5.858 15.355 0 21.213 2.929 2.929 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394l14.614-14.613 14.613 14.613c2.929 2.929 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394c5.858-5.857 5.858-15.355 0-21.213l-14.613-14.613 14.613-14.613c5.859-5.857 5.859-15.355 0-21.213z"/><path d="m158.006 320.943c-5.857-5.857-15.355-5.857-21.213 0l-14.612 14.613-14.612-14.613c-5.856-5.858-15.354-5.858-21.213 0-5.858 5.857-5.858 15.355 0 21.213l14.613 14.613-14.613 14.613c-5.858 5.857-5.858 15.355 0 21.213 2.929 2.929 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394l14.612-14.613 14.612 14.613c2.929 2.93 6.768 4.394 10.606 4.394s7.678-1.465 10.606-4.394c5.858-5.857 5.858-15.355 0-21.213l-14.613-14.613 14.613-14.613c5.86-5.857 5.86-15.355.002-21.213z"/></g></svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Leave Master</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">The leave master module is for denoting the attendance of the school staff and teachers. It denotes the name of the staff on leave, the type of leave, and the number of days on leave. The admin has the right to approve or disapprove of any staff and teacher’s leave.</p>
</div>
</div>
</div>
<!-- **** -->
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
		<svg class="w-8 fill-current" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 15.692 15.692" style="enable-background:new 0 0 15.692 15.692;" xml:space="preserve">
<g>
	<g>
		<path style="" d="M2.996,5.11c0.037,0.223,0.123,0.364,0.208,0.453C3.406,6.909,4.531,8.158,5.56,8.158
			c1.199,0,2.291-1.352,2.501-2.592c0.087-0.088,0.174-0.23,0.212-0.456c0.068-0.252,0.156-0.69,0.002-0.896
			C8.267,4.204,8.258,4.193,8.25,4.185c0.145-0.529,0.328-1.623-0.327-2.368C7.865,1.743,7.497,1.304,6.712,1.072L6.337,0.943
			C5.719,0.752,5.331,0.709,5.314,0.707c-0.028-0.002-0.057,0-0.084,0.007C5.209,0.72,5.135,0.74,5.078,0.732
			c-0.148-0.021-0.37,0.055-0.409,0.07c-0.051,0.021-1.248,0.5-1.611,1.615c-0.034,0.09-0.179,0.564,0.014,1.726
			c-0.029,0.02-0.055,0.044-0.077,0.073C2.839,4.42,2.927,4.858,2.996,5.11z"/>
		<path style="" d="M7.784,13.594c-0.221-0.124-0.461-0.243-0.717-0.356c-0.124-0.055-0.25-0.107-0.375-0.156
			c-0.098-0.037-0.214-0.085-0.295-0.106l-1.186-0.32L7.43,8.138l0.951,0.6C8.582,8.864,8.73,8.971,8.892,9.09l0.034,0.025
			C9.087,9.234,9.245,9.356,9.4,9.482c0.337,0.272,0.635,0.538,0.912,0.813c0.021,0.021,0.041,0.04,0.062,0.061
			c0.093-0.103,0.184-0.195,0.275-0.294c-0.116-0.345-0.257-0.664-0.429-0.92c0,0-0.244-0.333-0.823-0.555
			c0,0-0.049-0.015-0.124-0.04C8.758,8.306,8.269,8.151,8.269,8.151C8.164,8.113,8.072,8.076,7.989,8.04
			c-0.35-0.173-0.641-0.368-0.701-0.552c0,0,0.202,1.955-1.507,2.001L5.543,9.478C3.994,9.34,3.891,7.484,3.891,7.484
			c-0.162,0.509-2.11,1.101-2.11,1.101C1.202,8.807,0.957,9.141,0.957,9.141C0.101,10.411,0,13.237,0,13.237
			c0.011,0.646,0.29,0.713,0.29,0.713c1.969,0.879,5.058,1.034,5.058,1.034c0.167,0.004,0.322-0.005,0.477-0.014l0.004,0.016
			c0,0,1.508-0.077,3.089-0.423L8.725,14.31C8.568,14.103,8.217,13.836,7.784,13.594z"/>
		<path style="" d="M7.222,7.571c0.021-0.027,0.044-0.054,0.066-0.084C7.283,7.469,7.282,7.46,7.282,7.46
			C7.263,7.499,7.241,7.532,7.222,7.571z"/>
		<path style="" d="M3.9,7.481L3.895,7.46L3.891,7.482C3.892,7.478,3.896,7.474,3.897,7.47
			C3.898,7.471,3.899,7.475,3.9,7.481z"/>
		<path style="" d="M13.882,8.388c-0.561,0.396-1.084,0.844-1.582,1.315c-0.499,0.474-0.972,0.973-1.427,1.488
			c-0.169,0.192-0.333,0.386-0.496,0.581c-0.002-0.003-0.004-0.006-0.005-0.009c-0.24-0.32-0.5-0.605-0.77-0.872
			c-0.27-0.266-0.55-0.512-0.838-0.746c-0.145-0.116-0.291-0.23-0.44-0.342C8.169,9.691,8.033,9.59,7.843,9.47l-1.182,2.405
			c0.108,0.029,0.265,0.09,0.398,0.142c0.141,0.054,0.279,0.112,0.417,0.173c0.276,0.122,0.545,0.255,0.802,0.398
			c0.508,0.284,0.981,0.63,1.251,0.983l0.909,1.192l0.523-1.134c0.263-0.568,0.578-1.162,0.901-1.728
			c0.326-0.57,0.674-1.129,1.051-1.668s0.781-1.06,1.233-1.54c0.452-0.477,0.951-0.921,1.546-1.236
			C15.046,7.649,14.442,7.996,13.882,8.388z"/>
	</g>
</g></svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Attendance tracker</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">The attendance tracker keeps in track each student’s attendance and sends notification to the parents every day regarding the student’s attendance and absence. This gives both parents and the school an up-hand over the student’s prompt and punctual presence in school.</p>
</div>
</div>
</div>
<!-- **** -->
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
	<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" class="h-8 fill-current"><path d="M 26.6875 12.6602 C 26.9687 12.6602 27.1094 12.4961 27.1797 12.2383 C 27.9062 8.3242 27.8594 8.2305 31.9375 7.4570 C 32.2187 7.4102 32.3828 7.2461 32.3828 6.9648 C 32.3828 6.6836 32.2187 6.5195 31.9375 6.4726 C 27.8828 5.6524 28.0000 5.5586 27.1797 1.6914 C 27.1094 1.4336 26.9687 1.2695 26.6875 1.2695 C 26.4062 1.2695 26.2656 1.4336 26.1953 1.6914 C 25.3750 5.5586 25.5156 5.6524 21.4375 6.4726 C 21.1797 6.5195 20.9922 6.6836 20.9922 6.9648 C 20.9922 7.2461 21.1797 7.4102 21.4375 7.4570 C 25.5156 8.2774 25.4687 8.3242 26.1953 12.2383 C 26.2656 12.4961 26.4062 12.6602 26.6875 12.6602 Z M 15.3438 28.7852 C 15.7891 28.7852 16.0938 28.5039 16.1406 28.0821 C 16.9844 21.8242 17.1953 21.8242 23.6641 20.5821 C 24.0860 20.5117 24.3906 20.2305 24.3906 19.7852 C 24.3906 19.3633 24.0860 19.0586 23.6641 18.9883 C 17.1953 18.0977 16.9609 17.8867 16.1406 11.5117 C 16.0938 11.0899 15.7891 10.7852 15.3438 10.7852 C 14.9219 10.7852 14.6172 11.0899 14.5703 11.5352 C 13.7969 17.8164 13.4687 17.7930 7.0469 18.9883 C 6.6250 19.0821 6.3203 19.3633 6.3203 19.7852 C 6.3203 20.2539 6.6250 20.5117 7.1406 20.5821 C 13.5156 21.6133 13.7969 21.7774 14.5703 28.0352 C 14.6172 28.5039 14.9219 28.7852 15.3438 28.7852 Z M 31.2344 54.7305 C 31.8438 54.7305 32.2891 54.2852 32.4062 53.6524 C 34.0703 40.8086 35.8750 38.8633 48.5781 37.4570 C 49.2344 37.3867 49.6797 36.8945 49.6797 36.2852 C 49.6797 35.6758 49.2344 35.2070 48.5781 35.1133 C 35.8750 33.7070 34.0703 31.7617 32.4062 18.9180 C 32.2891 18.2852 31.8438 17.8633 31.2344 17.8633 C 30.6250 17.8633 30.1797 18.2852 30.0860 18.9180 C 28.4219 31.7617 26.5938 33.7070 13.9140 35.1133 C 13.2344 35.2070 12.7891 35.6758 12.7891 36.2852 C 12.7891 36.8945 13.2344 37.3867 13.9140 37.4570 C 26.5703 39.1211 28.3281 40.8321 30.0860 53.6524 C 30.1797 54.2852 30.6250 54.7305 31.2344 54.7305 Z"></path></svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Kudos</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">When a student has hundred percent attendance in a year, his/her name and class will be displayed on the app for all to view. This is an attempt to inspire students to maintain a hundred percent attendance record.</p>
</div>
</div>
</div>
<!-- **** -->
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
		<?xml version="1.0"?>
<svg class="h-8 fill-current" xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 512 512" width="512" height="512"><path d="M476,43.051H442.585V30.634a6,6,0,0,0-6-6H255.453a6,6,0,0,0-6,6V43.051H206.038a26.027,26.027,0,0,0-26,26v43.232A88.807,88.807,0,0,0,37.083,182.717V209.61a21.454,21.454,0,0,0-13.778,15.269A26.863,26.863,0,0,0,39.75,256.331v5.738a87.274,87.274,0,0,0,45.194,76.4v26.359l-47.333,20.2c-.111.048-.22.1-.327.152a49.92,49.92,0,0,0-27.263,40.268c-.014.165-.021.332-.021.5v55.421a6,6,0,0,0,6,6H242.769a6,6,0,0,0,6-6V426.878H476a26.028,26.028,0,0,0,26-26V69.049A26.028,26.028,0,0,0,476,43.051ZM261.453,36.634H430.585V59.019H261.453ZM49.083,182.717a76.856,76.856,0,1,1,153.712,0v27.316c-14.868,1.607-60.255,4.032-96.7-20.134,25.042-3.338,55.568-11.026,78.208-28.389a6,6,0,1,0-7.3-9.521c-41.187,31.589-114.766,28.257-115.5,28.218a6.008,6.008,0,0,0-6.3,5.627l-1.8,29.633-3.915-2.01a.762.762,0,0,1-.4-.519Zm2.667,79.352V251.788a6,6,0,0,0-4.965-5.911A14.848,14.848,0,0,1,34.96,227.735a9.348,9.348,0,0,1,5.344-6.49,12.668,12.668,0,0,0,3.7,2.888l9.8,5.031a7.676,7.676,0,0,0,11.16-6.217c0-.038.006-.075.008-.113l1.857-30.485c4.9.046,12.478-.034,21.69-.676,49.005,41.688,118.357,30.048,121.323,29.527a5.979,5.979,0,0,0,1.776-.629,9.506,9.506,0,0,1,7.343,7.164,14.844,14.844,0,0,1-11.824,18.142,6,6,0,0,0-4.965,5.911v10.281a75.209,75.209,0,0,1-150.417,0Zm128.291,69.14v36.269l-6.216-2.652V335.584Q177.032,333.532,180.041,331.209ZM161.825,342v21.542a85.4,85.4,0,0,1-64.881,0V343.954A87.19,87.19,0,0,0,161.825,342Zm74.944,133.371H22V426.2A37.86,37.86,0,0,1,42.5,395.99l49.964-21.324a97.348,97.348,0,0,0,73.851,0l49.965,21.325A37.856,37.856,0,0,1,236.769,426.2ZM490,400.88a14.013,14.013,0,0,1-14,14H246.652a49.945,49.945,0,0,0-25.167-29.7c-.107-.055-.216-.1-.327-.152L192.041,372.6V320.052a86.854,86.854,0,0,0,22.126-57.983v-5.738a26.861,26.861,0,0,0,16.445-31.45,21.5,21.5,0,0,0-15.817-15.9V182.717A88.506,88.506,0,0,0,192.041,123.4V69.049a14.013,14.013,0,0,1,14-14h43.415v9.968a6,6,0,0,0,6,6H436.585a6,6,0,0,0,6-6V55.051H476a14.013,14.013,0,0,1,14,14Z"/><path d="M446.584,221.686H402.718a6,6,0,0,0,0,12h43.866a6,6,0,0,0,0-12Z"/><path d="M362.157,233.686h14.715a6,6,0,0,0,0-12H362.157a6,6,0,0,0,0,12Z"/><path d="M446.584,286.582H431.689a6,6,0,1,0,0,12h14.895a6,6,0,0,0,0-12Z"/><path d="M410.937,298.582a6,6,0,0,0,0-12H331.478a6,6,0,1,0,0,12Z"/><path d="M310.437,286.582h-5.361a6,6,0,0,0,0,12h5.361a6,6,0,0,0,0-12Z"/><path d="M245.212,286.582a6,6,0,0,0,0,12H279.23a6,6,0,0,0,0-12Z"/><path d="M446.584,346.563H412.249a6,6,0,0,0,0,12h34.335a6,6,0,0,0,0-12Z"/><path d="M390.714,346.563H386.4a6,6,0,0,0,0,12h4.311a6,6,0,1,0,0-12Z"/><path d="M369.514,346.563H336.9a6,6,0,0,0,0,12h32.615a6,6,0,0,0,0-12Z"/><path d="M310.437,346.563H245.212a6,6,0,0,0,0,12h65.225a6,6,0,0,0,0-12Z"/><path d="M446.584,170.6h-3.872a6,6,0,0,0,0,12h3.872a6,6,0,0,0,0-12Z"/><path d="M374.339,182.6h42.528a6,6,0,0,0,0-12H374.339a6,6,0,0,0,0,12Z"/><path d="M446.584,120.6H403.717a6,6,0,0,0,0,12h42.867a6,6,0,0,0,0-12Z"/><path d="M374.339,132.6h3.533a6,6,0,0,0,0-12h-3.533a6,6,0,0,0,0,12Z"/><path d="M287.875,237.92a66,66,0,1,0-66-66A66.075,66.075,0,0,0,287.875,237.92Zm0-120a54,54,0,1,1-54,54A54.062,54.062,0,0,1,287.875,117.92Z"/><path d="M270.268,198.079a6,6,0,0,0,8.485,0l43.832-43.832a6,6,0,0,0-8.485-8.485l-39.589,39.589L261.649,172.49a6,6,0,1,0-8.485,8.484Z"/></svg>

	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Attendance</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">Through the attendance module, students will be able to apply for leave. They can apply for either the morning session or the afternoon session or the entire day by choosing the options in the module. This will display the total students in the class and the number of students who are absent.</p>
</div>
</div>
</div>
<!-- **** -->
</div>          

</div>
@endsection 