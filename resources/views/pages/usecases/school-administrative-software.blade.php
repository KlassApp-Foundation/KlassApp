{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('title')
GegoK12 - School Administrative Software |  Online School Management 
@endsection

@section('content')
<!-- start -->
<div class="bg-red-600 py-8 lg:py-16 md:py-8">
	<div class="container mx-auto">
		<div class="text-center tracking-wider px-1">
		<h1 class="text-white font-plex text-2xl lg:text-4xl">School Administrative Software</h1>
		</div>
	</div>
</div>
<!-- end -->
<!-- <div class="bg-gray-400">
	<div class="container mx-auto py-12">
		<h1 class="text-4xl font-bold">School Administrative Software</h1>
	</div>
</div> -->
<div class="container mx-auto py-8 px-3">

<p class="leading-loose text-justify mb-8 text-gray-700">A school administration faces many issues in handling the large amount of data that gather on their desk every day. Among these, one of the biggest issues they face is the huge amount of paperwork that piles up daily. For the modern school administrator, paperwork is the biggest area of frustration. Most people tend to postpone these works to the next day and when the next day brings more paperwork to the table, they are at a loss of how to handle these. These types of works can be automated with the help of an automation tool. As the school administrator, he/she will have a huge responsibility towards the school, staff, students and parents. In such cases, the administrator can use a single school administration tool to automate the processes.</p>
<div class="w-full lg:w-10/12 px-2 py-2 bg-gray-200 mx-auto my-8">
	<img src="{{url('images/administration.png')}}">
</div>
<p class="leading-loose text-justify mb-8 text-gray-700">A school administrator software is a tool that has a specific set of computerized instructions that are designed to automate and digitally monitor the day to day activities of any educational institution. Many school administrators face problems in managing paperwork, the big data that piles up every day, keeping track of attendance, fees, transport, admission, et cetera.</p>

<div class="flex flex-wrap">
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
		<svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg" class="h-8 fill-current"><path d="m88 360h32v16h-32z"></path><path d="m82.207 459.624a18.922 18.922 0 0 0 18.868 20.376c.159 0 .312-.02.47-.024a8.169 8.169 0 0 1 .9-.045 18.936 18.936 0 0 0 17.555-18.856v-69.075h-32.592z"></path><path d="m288 448v-107.293l-16.977 31.124a8 8 0 0 1 -14.046 0l-45.726-83.831h-26.771a40.489 40.489 0 0 0 -28.693 12.087 38.344 38.344 0 0 0 -8.419 12.913l-11.368 28.307v97.193l37.456-69.641a70.053 70.053 0 0 1 10.4-19.443c.034-.057.063-.119.1-.175l.142-.249.018.01a8.225 8.225 0 0 1 .827-1 70.515 70.515 0 0 1 9.939-10.144l10.244 12.292a54.544 54.544 0 0 0 -6.976 76.733 8 8 0 0 1 1.85 5.117v48h128v-17.441l-33.569-6.714a8 8 0 0 1 -6.431-7.845z"></path><path d="m184.152 349.023c.162-.21.321-.422.485-.631a8 8 0 0 0 -.509.618z"></path><path d="m299.428 286.341a19.216 19.216 0 0 1 -11.428-17.541v-24.483a80.039 80.039 0 0 1 -48 0v24.483a19.216 19.216 0 0 1 -11.428 17.541l9.628 17.659h51.6z"></path><path d="m156.466 279.558a56.358 56.358 0 0 1 28.014-7.558h36.32a3.243 3.243 0 0 0 3.2-3.2v-31.563a80.026 80.026 0 0 1 -40-69.237v-40a8 8 0 0 1 3.562-6.656 100.785 100.785 0 0 0 34.182-38.721l3.1-6.2a8 8 0 0 1 12.277-2.568l4.543 3.787a191.273 191.273 0 0 0 95.466 42.439 8 8 0 0 1 6.87 7.919v40a80.026 80.026 0 0 1 -40 69.237v31.563a3.243 3.243 0 0 0 3.2 3.2h36.24a55.951 55.951 0 0 1 28.1 7.607l.909-8.244a122.558 122.558 0 0 0 .752-12.8 114.657 114.657 0 0 0 -9.357-45.258 131.612 131.612 0 0 1 -10.392-58.193l1.359-27.662c.077-1.618.15-3.157.15-4.567a91.024 91.024 0 0 0 -90.961-90.883 90.941 90.941 0 0 0 -90.81 95.443l1.36 27.684c.086 2.206.17 4.4.17 6.633a129.949 129.949 0 0 1 -10.569 51.556 114.7 114.7 0 0 0 -8.6 58.021z"></path><path d="m328 168v-33.18a207.224 207.224 0 0 1 -93.376-42.273 116.869 116.869 0 0 1 -34.624 39.637v35.816a64 64 0 0 0 128 0zm-100-8a12 12 0 1 1 12-12 12.013 12.013 0 0 1 -12 12zm36 40a24.027 24.027 0 0 1 -24-24h16a8 8 0 0 0 16 0h16a24.027 24.027 0 0 1 -24 24zm36-40a12 12 0 1 1 12-12 12.013 12.013 0 0 1 -12 12z"></path><path d="m281.069 320h-34.138l17.069 31.293z"></path><path d="m100.762 275.056a7.56 7.56 0 0 0 -4.929 10.717l7.323 14.648a8 8 0 0 1 -7.156 11.579h-28.669a56.284 56.284 0 0 1 -25.043-5.912l-7.973-3.988a1.526 1.526 0 0 0 -1.556.07 1.534 1.534 0 0 0 -.759 1.363v22.578a7.958 7.958 0 0 0 4.422 7.156l13.021 6.51a40.2 40.2 0 0 0 17.888 4.223h52.669v-38.459l-10.383-25.958a7.589 7.589 0 0 0 -8.855-4.527z"></path><path d="m304 441.441 64 12.8v-95.682l-64-12.8z"></path><path d="m426.082 468.542-16.445 3.289-.055.011h-.013c-.059.011-.118.015-.177.026-.149.026-.3.051-.449.068a16.39 16.39 0 0 0 14.097 8.064 14.308 14.308 0 0 0 2.528-.2 16.678 16.678 0 0 0 8.57-4.245 15.791 15.791 0 0 1 -8.056-7.015z"></path><path d="m416 454.241 8-1.6v-21.7a18.357 18.357 0 0 1 15.387-18.164l27.172-4.528a18.361 18.361 0 0 1 13.441 2.989v-65.479l-64 12.8z"></path><path d="m416.989 331.946a20.263 20.263 0 0 0 -15.365 12.054h5.584l35.165-7.033 37.627-7.526v-8z"></path><path d="m471.146 424.574a2.4 2.4 0 0 0 -1.546-.574 2.621 2.621 0 0 0 -.413.034l-27.173 4.529a2.408 2.408 0 0 0 -2.014 2.382v29.735l32-5.334h.006l-.006-28.929a2.387 2.387 0 0 0 -.854-1.843z"></path><path d="m372.263 300.377-.148-.152a39.635 39.635 0 0 0 -28.675-12.225h-26.691l-9.674 17.735 62.567 10.428a36.387 36.387 0 0 1 14.357 5.7l-3.437-8.677a37.073 37.073 0 0 0 -8.299-12.809z"></path><path d="m367.012 331.946-63.012-10.503v8l36.4 7.28 36.4 7.279h5.584a20.263 20.263 0 0 0 -15.372-12.056z"></path><path d="m384 360h16v96h-16z"></path></svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Staff management system</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">One of the most important functions of a school administration is to observe how the teachers and other staff are working. With the staff management module, the admin will be able to manage class scheduling, salary allotment, etc. By simply clicking on the profile of a staff, the admin will have the privilege of viewing the employee’s timetable, their designation, class and other professional details. The admin will also be able to manage the leave requests for the teachers through this module</p>
</div>
</div>
</div>
<!-- **** -->
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
		<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 480 480" xml:space="preserve" class="h-8 fill-current"><g><g><path d="M160,344h-16c-4.418,0-8,3.582-8,8s3.582,8,8,8h16c4.418,0,8-3.582,8-8S164.418,344,160,344z"></path></g></g><g><g><path d="M384,344H192c-4.418,0-8,3.582-8,8s3.582,8,8,8h192c4.418,0,8-3.582,8-8S388.418,344,384,344z"></path></g></g><g><g><path d="M160,296h-16c-4.418,0-8,3.582-8,8s3.582,8,8,8h16c4.418,0,8-3.582,8-8S164.418,296,160,296z"></path></g></g><g><g><path d="M384,296H192c-4.418,0-8,3.582-8,8s3.582,8,8,8h192c4.418,0,8-3.582,8-8S388.418,296,384,296z"></path></g></g><g><g><path d="M160,248h-16c-4.418,0-8,3.582-8,8s3.582,8,8,8h16c4.418,0,8-3.582,8-8S164.418,248,160,248z"></path></g></g><g><g><path d="M384,248H192c-4.418,0-8,3.582-8,8s3.582,8,8,8h192c4.418,0,8-3.582,8-8S388.418,248,384,248z"></path></g></g><g><g><path d="M160,200h-16c-4.418,0-8,3.582-8,8s3.582,8,8,8h16c4.418,0,8-3.582,8-8S164.418,200,160,200z"></path></g></g><g><g><path d="M384,200H192c-4.418,0-8,3.582-8,8s3.582,8,8,8h192c4.418,0,8-3.582,8-8S388.418,200,384,200z"></path></g></g><g><g><path d="M160,152h-16c-4.418,0-8,3.582-8,8s3.582,8,8,8h16c4.418,0,8-3.582,8-8S164.418,152,160,152z"></path></g></g><g><g><path d="M384,152H192c-4.418,0-8,3.582-8,8s3.582,8,8,8h192c4.418,0,8-3.582,8-8S388.418,152,384,152z"></path></g></g><g><g><path d="M439.896,119.496c-0.04-0.701-0.177-1.393-0.408-2.056c-0.088-0.256-0.152-0.504-0.264-0.752 c-0.389-0.87-0.931-1.664-1.6-2.344l-112-112c-0.68-0.669-1.474-1.211-2.344-1.6c-0.248-0.112-0.496-0.176-0.744-0.264 c-0.669-0.23-1.366-0.37-2.072-0.416C320.328,0.088,320.176,0,320,0H96c-4.418,0-8,3.582-8,8v24H48c-4.418,0-8,3.582-8,8v432 c0,4.418,3.582,8,8,8h336c4.418,0,8-3.582,8-8v-40h40c4.418,0,8-3.582,8-8V120C440,119.824,439.912,119.672,439.896,119.496z M328,27.312L412.688,112H328V27.312z M376,464H56V48h32v376c0,4.418,3.582,8,8,8h280V464z M424,416H104V16h208v104 c0,4.418,3.582,8,8,8h104V416z"></path></g></g><g><g><path d="M192,72h-48c-4.418,0-8,3.582-8,8v48c0,4.418,3.582,8,8,8h48c4.418,0,8-3.582,8-8V80C200,75.582,196.418,72,192,72z M184,120h-32V88h32V120z"></path></g></g></svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Documents module</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">Getting hold of a single document within the masses is truly like searching for a needle in a haystack. With the Documents Module, the admin will be able to easily save and retrieve scanned copies of the employee’s certificates, ID cards and other professional data. This module also saves all the documents for easy retrieval later on.</p>
</div>
</div>
</div>
<!-- **** -->
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
		<svg xmlns="http://www.w3.org/2000/svg" height="512px" viewBox="0 0 480 480" width="512px" class="h-8 fill-current"><g><path d="m399.832031 54.398438c-.0625-.277344-.230469-.496094-.3125-.757813-.136719-.449219-.316406-.882813-.535156-1.296875-.25-.476562-.550781-.921875-.894531-1.335938-.277344-.34375-.582032-.664062-.914063-.960937-.402343-.339844-.84375-.632813-1.3125-.878906-.394531-.238281-.8125-.441407-1.246093-.609375-.519532-.164063-1.058594-.273438-1.601563-.328125-.332031-.097657-.671875-.175781-1.015625-.230469h-40.335938c-2.0625-27.847656-14.984374-48-31.664062-48s-29.601562 20.152344-31.664062 48h-16.671876c-2.0625-27.847656-14.984374-48-31.664062-48s-29.601562 20.152344-31.664062 48h-16.671876c-2.0625-27.847656-14.984374-48-31.664062-48s-29.601562 20.152344-31.664062 48h-16.671876c-2.0625-27.847656-14.984374-48-31.664062-48s-29.601562 20.152344-31.664062 48h-40.335938c-4.417969 0-8 3.582031-8 8v416c0 4.417969 3.582031 8 8 8h384c4.417969 0 8-3.582031 8-8v-24h72c2.402344 0 4.675781-1.082031 6.191406-2.941406 1.519532-1.863282 2.121094-4.304688 1.640625-6.660156zm-79.832031-38.398438c5.777344 0 13.839844 12.257812 15.617188 32h-31.203126c1.746094-19.742188 9.808594-32 15.585938-32zm-80 0c5.777344 0 13.839844 12.257812 15.617188 32h-31.203126c1.746094-19.742188 9.808594-32 15.585938-32zm-80 0c5.777344 0 13.839844 12.257812 15.617188 32h-31.203126c1.746094-19.742188 9.808594-32 15.585938-32zm-80 0c5.777344 0 13.839844 12.257812 15.617188 32h-31.203126c1.746094-19.742188 9.808594-32 15.585938-32zm-64 48h32.335938c2.0625 27.847656 14.984374 48 31.664062 48 4.417969 0 8-3.582031 8-8s-3.582031-8-8-8c-5.777344 0-13.839844-12.257812-15.617188-32h63.953126c2.089843 27.847656 14.984374 48 31.664062 48 4.417969 0 8-3.582031 8-8s-3.582031-8-8-8c-5.777344 0-13.839844-12.257812-15.617188-32h63.953126c2.089843 27.847656 14.984374 48 31.664062 48 4.417969 0 8-3.582031 8-8s-3.582031-8-8-8c-5.777344 0-13.839844-12.257812-15.617188-32h63.953126c2.089843 27.847656 14.984374 48 31.664062 48 4.417969 0 8-3.582031 8-8s-3.582031-8-8-8c-5.777344 0-13.839844-12.257812-15.617188-32h79.617188v80h-368zm368 400h-368v-304h368zm16-32v-298.398438l62.167969 298.398438zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m176 288h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m256 288h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m96 288h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m176 368h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m256 368h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m96 368h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m176 208h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m256 208h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m336 288h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m336 368h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m336 208h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m96 208h-32c-4.417969 0-8 3.582031-8 8v32c0 4.417969 3.582031 8 8 8h32c4.417969 0 8-3.582031 8-8v-32c0-4.417969-3.582031-8-8-8zm-8 32h-16v-16h16zm0 0" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path></g></svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Calendar</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">A school calendar is vital for every academic year. With the school calendar, parents, teachers and students will be able to view the holidays, special school events and date of exams. The admin will also be able to schedule new events on the calendar and make changes to existing ones.</p>
</div>
</div>
</div>
<!-- **** -->
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
		<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" class="h-8 fill-current"><path d="M 15.3203 53.0078 L 40.7031 53.0078 C 45.3438 53.0078 47.7344 50.6172 47.7344 45.8828 L 47.7344 10.1406 C 47.7344 5.4297 45.3438 2.9922 40.7031 2.9922 L 15.3203 2.9922 C 10.6562 2.9922 8.2656 5.4297 8.2656 10.1406 L 8.2656 45.8828 C 8.2656 50.6172 10.6562 53.0078 15.3203 53.0078 Z M 15.4844 49.2578 C 13.2344 49.2578 12.0391 48.0156 12.0391 45.8359 L 12.0391 10.1875 C 12.0391 7.9843 13.2344 6.7656 15.5078 6.7656 L 40.4922 6.7656 C 42.7656 6.7656 43.9609 7.9843 43.9609 10.1875 L 43.9609 45.8359 C 43.9609 48.0156 42.7656 49.2578 40.5156 49.2578 Z M 33.8125 26.8047 C 36.6719 26.8047 38.6406 24.8594 38.6406 22.0234 C 38.6406 19.2812 36.9531 17.4765 34.4687 17.4765 C 33.2266 17.4765 32.1953 17.8750 31.4687 18.7656 L 31.0000 18.7656 C 31.1875 17.9453 31.7031 17.1250 32.4766 16.4687 C 33.2031 15.7890 34.1406 15.3672 35.1953 15.1328 C 36.0625 14.9453 36.3203 14.6641 36.3203 14.1484 C 36.3203 13.6797 35.9687 13.3047 35.3125 13.3047 C 33.7891 13.3047 31.8438 14.2656 30.5781 15.6250 C 29.2656 17.0547 28.6094 18.9063 28.6094 20.8750 C 28.6094 24.6250 30.9297 26.8047 33.8125 26.8047 Z M 21.9297 26.8281 C 24.7891 26.8281 26.7578 24.8828 26.7578 22.0468 C 26.7578 19.3047 25.0703 17.5 22.5860 17.5 C 21.3438 17.5 20.3125 17.8984 19.6094 18.7890 L 19.1172 18.7890 C 19.3047 17.9453 19.8438 17.1016 20.6640 16.4453 C 21.4141 15.8125 22.2813 15.3672 23.3125 15.1563 C 24.1797 14.9687 24.4609 14.6875 24.4609 14.1719 C 24.4609 13.7031 24.1094 13.3281 23.4297 13.3281 C 21.9062 13.3281 20.0078 14.2422 18.6953 15.6484 C 17.3828 17.0781 16.7266 18.9297 16.7266 20.8984 C 16.7266 24.6484 19.0469 26.8281 21.9297 26.8281 Z M 18.8125 34.7265 L 36.6016 34.7265 C 37.4219 34.7265 38.0547 34.0937 38.0547 33.2734 C 38.0547 32.4765 37.4219 31.8437 36.6016 31.8437 L 18.8125 31.8437 C 17.9687 31.8437 17.3594 32.4765 17.3594 33.2734 C 17.3594 34.0937 17.9687 34.7265 18.8125 34.7265 Z M 18.8125 42.9062 L 27.2500 42.9062 C 28.0703 42.9062 28.6797 42.2969 28.6797 41.4765 C 28.6797 40.6562 28.0703 40.0234 27.2500 40.0234 L 18.8125 40.0234 C 17.9687 40.0234 17.3594 40.6562 17.3594 41.4765 C 17.3594 42.2969 17.9687 42.9062 18.8125 42.9062 Z"></path></svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Gallery module and notice board module</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">The Gallery module has the complete set of pictures, videos and audio files that are comprised on special days. Students, parents and teachers can view them as their perusal. The Notice Board module gives out circulars, important dates and events, examination timetable, events and other information will be posted on the notice board module and also sent as a notification to parents.</p>
</div>
</div>
</div>
<!-- **** -->

</div>




<p class="leading-loose text-justify mb-8 text-gray-700">Gego K12 is a complete school administrative software which aims to support a school management in automating and handling the big data with simplicity. Gego K12 is a tool that liberates the school management, faculty, students and parents with the gap in communication.</p>
</div>
@endsection 