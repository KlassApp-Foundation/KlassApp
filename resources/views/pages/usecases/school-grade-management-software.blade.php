{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.main')

@section('title')
GegoK12 - school-grade-management-software
@endsection

@section('content')
<!-- start -->
<div class="bg-red-600 py-8 lg:py-16 md:py-8">
	<div class="container mx-auto">
		<div class="text-center tracking-wider px-1">
		<h1 class="text-white font-plex text-2xl lg:text-4xl">School Grade Management Software</h1>
		</div>
	</div>
</div>
<!-- end -->
<!-- <div class="bg-gray-400">
	<div class="container mx-auto py-12 ">
		<h1 class="text-4xl font-bold">School Grade Management Software </h1>
	</div>
</div> -->

<div class="container mx-auto py-8 px-3">
  <div class="flex flex-col lg:flex-row items-center justify-between">
  <div class="w-full lg:w-2/3">
	<p class="leading-loose text-justify mb-8 text-gray-700">Today’s world is very competitive and accuracy is the key to solving all problems. When grades are wrongly calculated, it is a very grievous mistake on the part of the school. Accurate calculations done manually produce rare success since the human brain is not designed to be perfect throughout the calculation and hence, using the right school grade management software is vital. This helps the students know where they rank and improve themselves. Having the perfect grade management system gives an overview of every student’s performance in school and is the perfect reflection of the work done in a school. It serves as a prototype for admissions for the upcoming year. For the parents the grades are the reference which helps them access their ward’s performance academically.</p>
<p class="leading-loose text-justify mb-8 text-gray-700">Hence the grade sheet should be seen as a tool for accurate communication and evaluation of the student’s academic behaviour and progress without any hassle for the school or the parents. Without a grade system, students will lack the necessary push to learn anything. Today the grades remain the primary focus of education on a student and school. The grade system identifies the weakness and strengths of the students. It gives a detailed view on where the child is lacking in concentration and needs to improve. Hence managing school grade system is an important factor in the development of education.</p>
<p class="leading-loose text-justify mb-8 text-gray-700">Gego K12 is a school management software that promotes a school grade management system that is designed and built specifically to provide automated and precise results for every internal and external examinations.</p>
</div>
<div class="w-full lg:w-1/4">
<img src="{{url('images/grade.png')}}" class="mx-auto">
</div>
</div>


<div class="flex flex-wrap">
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
		<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" class="h-8 fill-current"><path d="M 23.6641 52.3985 C 26.6407 55.375 29.3594 55.3516 32.3126 52.3985 L 35.9219 48.8125 C 36.2969 48.4610 36.6250 48.3203 37.1172 48.3203 L 42.1797 48.3203 C 46.3749 48.3203 48.3204 46.3985 48.3204 42.1797 L 48.3204 37.1172 C 48.3204 36.625 48.4610 36.2969 48.8124 35.9219 L 52.3749 32.3125 C 55.3749 29.3594 55.3514 26.6407 52.3749 23.6641 L 48.8124 20.0547 C 48.4610 19.7031 48.3204 19.3516 48.3204 18.8829 L 48.3204 13.7969 C 48.3204 9.625 46.3985 7.6563 42.1797 7.6563 L 37.1172 7.6563 C 36.6250 7.6563 36.2969 7.5391 35.9219 7.1875 L 32.3126 3.6016 C 29.3594 .6250 26.6407 .6485 23.6641 3.6016 L 20.0547 7.1875 C 19.7032 7.5391 19.3516 7.6563 18.8828 7.6563 L 13.7969 7.6563 C 9.6016 7.6563 7.6563 9.5782 7.6563 13.7969 L 7.6563 18.8829 C 7.6563 19.3516 7.5391 19.7031 7.1876 20.0547 L 3.6016 23.6641 C .6251 26.6407 .6485 29.3594 3.6016 32.3125 L 7.1876 35.9219 C 7.5391 36.2969 7.6563 36.625 7.6563 37.1172 L 7.6563 42.1797 C 7.6563 46.3750 9.6016 48.3203 13.7969 48.3203 L 18.8828 48.3203 C 19.3516 48.3203 19.7032 48.4610 20.0547 48.8125 Z M 26.2891 49.7734 L 21.8828 45.3438 C 21.3672 44.8047 20.8282 44.5938 20.1016 44.5938 L 13.7969 44.5938 C 11.7110 44.5938 11.3828 44.2656 11.3828 42.1797 L 11.3828 35.875 C 11.3828 35.1719 11.1719 34.6329 10.6563 34.1172 L 6.2266 29.7109 C 4.7501 28.2109 4.7501 27.7891 6.2266 26.2891 L 10.6563 21.8829 C 11.1719 21.3672 11.3828 20.8282 11.3828 20.1016 L 11.3828 13.7969 C 11.3828 11.6875 11.6876 11.3829 13.7969 11.3829 L 20.1016 11.3829 C 20.8282 11.3829 21.3672 11.1953 21.8828 10.6563 L 26.2891 6.2266 C 27.7891 4.7500 28.2110 4.7500 29.7110 6.2266 L 34.1172 10.6563 C 34.6328 11.1953 35.1719 11.3829 35.8750 11.3829 L 42.1797 11.3829 C 44.2657 11.3829 44.5938 11.7109 44.5938 13.7969 L 44.5938 20.1016 C 44.5938 20.8282 44.8282 21.3672 45.3439 21.8829 L 49.7733 26.2891 C 51.2498 27.7891 51.2498 28.2109 49.7733 29.7109 L 45.3439 34.1172 C 44.8282 34.6329 44.5938 35.1719 44.5938 35.875 L 44.5938 42.1797 C 44.5938 44.2656 44.2657 44.5938 42.1797 44.5938 L 35.8750 44.5938 C 35.1719 44.5938 34.6328 44.8047 34.1172 45.3438 L 29.7110 49.7734 C 28.2110 51.2500 27.7891 51.2500 26.2891 49.7734 Z M 24.3438 39.2266 C 25.0235 39.2266 25.5391 38.9453 25.8907 38.5234 L 38.8985 20.3360 C 39.1563 19.9609 39.2969 19.5391 39.2969 19.1407 C 39.2969 18.1094 38.5001 17.2891 37.4219 17.2891 C 36.6485 17.2891 36.2266 17.5469 35.7579 18.2266 L 24.2735 34.3985 L 18.3438 27.8594 C 17.9454 27.4141 17.5001 27.2266 16.9141 27.2266 C 15.7657 27.2266 14.9454 28.0000 14.9454 29.0782 C 14.9454 29.5469 15.1094 29.9922 15.4376 30.3203 L 22.8907 38.6172 C 23.2423 38.9922 23.6876 39.2266 24.3438 39.2266 Z"></path></svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Automated report cards</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">The school grade management tool has built in support which ensures the complete process of grade generation is automated within a few clicks of the mouse. This saves teachers and the administration a lot of time and the grades can be generated digitally online and shared with the parents.</p>
</div>
</div>
</div>
<!-- **** -->
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
	<svg class="h-8 fill-current" id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m337.206 0h-276.206v512h390v-398.245c-5.375-5.373-110.324-110.286-113.794-113.755zm62.571 104.984-53.777.012v-53.801c14.058 14.058 29.369 29.378 53.777 53.789zm-287.431 377.016 76.127-75.735 45.027 45.027 112.5-112.5v39.208h30v-90h-90v30h38.366l-90.866 90.866-44.973-44.973-97.527 97.026v-430.919h225v105.004l105-.024v347.02z"/><path d="m136 105h119.667v30h-119.667z"/><path d="m136 179.737h119.667v30h-119.667z"/></g></svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Error free report system</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">By using an automated grade management system, to create and manage student information, the school administration can vouch for a safe and error free grading system that is precise every time. Creating a report card manually will result in some kind of manual error. The automated grade system also converts the marks into grades and sends the report directly to the student and parent.</p>
</div>
</div>
</div>
<!-- **** -->
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
		<svg xmlns="http://www.w3.org/2000/svg" id="Capa_1" enable-background="new 0 0 512 512" height="512px" viewBox="0 0 512 512" width="512px" class="h-8 fill-current w-auto"><g><path d="m497 161h-60c-8.284 0-15 6.716-15 15s6.716 15 15 15h23.787l-73.093 73.093c-6.583-30.894-26.794-56.8-54.017-71.104 16.093-13.767 26.323-34.2 26.323-56.989 0-41.355-33.645-75-75-75s-75 33.645-75 75c0 22.789 10.23 43.222 26.323 56.988-25.113 13.196-44.261 36.262-52.247 64.012-1.878-2.151-3.844-4.24-5.91-6.252-7.355-7.167-15.59-13.13-24.454-17.789 16.073-13.767 26.288-34.187 26.288-56.959 0-41.355-33.645-75-75-75s-75 33.645-75 75c0 22.789 10.23 43.222 26.323 56.988-33.452 17.578-56.323 52.672-56.323 93.012v110c0 8.284 6.716 15 15 15h122c3.979 0 7.794-1.58 10.606-4.394l109.394-109.393 39.394 39.394c5.857 5.857 15.355 5.857 21.213 0l164.393-164.394v23.787c0 8.284 6.716 15 15 15s15-6.716 15-15v-60c0-8.284-6.716-15-15-15zm-257-25c0-24.813 20.187-45 45-45s45 20.187 45 45-20.187 45-45 45-45-20.187-45-45zm-180 40c0-24.813 20.187-45 45-45s45 20.187 45 45-20.187 45-45 45-45-20.187-45-45zm70.787 245h-100.787v-95c0-41.355 33.645-75 75-75 19.629 0 38.177 7.541 52.229 21.233 14.024 13.666 22.066 31.967 22.644 51.531.016.528.06 1.048.128 1.56v46.462zm176.213-76.213-39.394-39.394c-5.857-5.857-15.355-5.857-21.213 0l-36.393 36.394v-55.787c0-41.355 33.645-75 75-75s75 33.645 75 75v5.787z" data-original="#000000" data-old_color="#000000" class="active-path"></path></g></svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Promotion</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">Using this module the admin can promote or de-promote any student based on their grades. With Gego K12, it is easy to separate the students who are promoted to the next standard with the ones who are detained. This module also sends the message over to the parents regarding their ward’s promotion news.</p>
</div>
</div>
</div>
<!-- **** -->
<!-- **** -->
<div class="w-full lg:w-1/2 lg:px-5 py-2">
<div class="px-4 py-3 flex">
<div>
	<div class="bg-red-300 text-red-700 w-16 p-4 rounded-full flex items-center justify-center">
		<svg class="h-8 fill-current" xmlns="http://www.w3.org/2000/svg" height="937pt" version="1.1" viewBox="0 -31 937.5 937" width="937pt">
<g id="surface1">
<path d="M 62.5 750.25 L 93.75 750.25 L 93.75 719 L 62.5 719 C 45.238281 719 31.25 705.011719 31.25 687.75 L 31.25 62.75 C 31.25 45.488281 45.238281 31.5 62.5 31.5 L 750 31.5 C 767.261719 31.5 781.25 45.488281 781.25 62.75 L 781.25 94 L 812.5 94 L 812.5 62.75 C 812.5 28.234375 784.515625 0.25 750 0.25 L 62.5 0.25 C 27.984375 0.25 0 28.234375 0 62.75 L 0 687.75 C 0 722.265625 27.984375 750.25 62.5 750.25 Z M 62.5 750.25 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 62.5 62.75 L 93.75 62.75 L 93.75 94 L 62.5 94 Z M 62.5 62.75 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 125 62.75 L 156.25 62.75 L 156.25 94 L 125 94 Z M 125 62.75 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 187.5 62.75 L 218.75 62.75 L 218.75 94 L 187.5 94 Z M 187.5 62.75 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 125 812.75 C 125 847.265625 152.984375 875.25 187.5 875.25 L 875 875.25 C 909.515625 875.25 937.5 847.265625 937.5 812.75 L 937.5 187.75 C 937.5 153.234375 909.515625 125.25 875 125.25 L 187.5 125.25 C 152.984375 125.25 125 153.234375 125 187.75 Z M 156.25 187.75 C 156.25 170.488281 170.238281 156.5 187.5 156.5 L 875 156.5 C 892.261719 156.5 906.25 170.488281 906.25 187.75 L 906.25 812.75 C 906.25 830.011719 892.261719 844 875 844 L 187.5 844 C 170.238281 844 156.25 830.011719 156.25 812.75 Z M 156.25 187.75 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 187.5 187.75 L 218.75 187.75 L 218.75 219 L 187.5 219 Z M 187.5 187.75 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 250 187.75 L 281.25 187.75 L 281.25 219 L 250 219 Z M 250 187.75 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 312.5 187.75 L 343.75 187.75 L 343.75 219 L 312.5 219 Z M 312.5 187.75 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 187.5 250.25 L 875 250.25 L 875 281.5 L 187.5 281.5 Z M 187.5 250.25 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 187.5 328.375 L 312.5 328.375 L 312.5 359.625 L 187.5 359.625 Z M 187.5 328.375 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 187.5 390.875 L 281.25 390.875 L 281.25 422.125 L 187.5 422.125 Z M 187.5 390.875 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 531.25 453.375 C 470.839844 453.375 421.875 502.339844 421.875 562.75 C 421.875 623.160156 470.839844 672.125 531.25 672.125 C 591.660156 672.125 640.625 623.160156 640.625 562.75 C 640.554688 502.375 591.625 453.445312 531.25 453.375 Z M 531.25 640.875 C 488.105469 640.875 453.125 605.894531 453.125 562.75 C 453.125 519.605469 488.105469 484.625 531.25 484.625 C 574.394531 484.625 609.375 519.605469 609.375 562.75 C 609.320312 605.875 574.375 640.820312 531.25 640.875 Z M 531.25 640.875 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 781.25 515.875 C 781.25 507.242188 774.257812 500.25 765.625 500.25 L 724.328125 500.25 C 721.019531 489.984375 716.894531 479.996094 711.96875 470.40625 L 741.171875 441.222656 C 747.265625 435.117188 747.265625 425.230469 741.171875 419.125 L 674.875 352.828125 C 668.769531 346.734375 658.882812 346.734375 652.777344 352.828125 L 623.59375 382.015625 C 613.988281 377.109375 604.007812 372.984375 593.75 369.671875 L 593.75 328.375 C 593.75 319.742188 586.757812 312.75 578.125 312.75 L 484.375 312.75 C 475.742188 312.75 468.75 319.742188 468.75 328.375 L 468.75 369.671875 C 458.492188 372.984375 448.511719 377.109375 438.90625 382.015625 L 409.722656 352.828125 C 403.617188 346.734375 393.730469 346.734375 387.625 352.828125 L 321.328125 419.125 C 315.234375 425.230469 315.234375 435.117188 321.328125 441.222656 L 350.515625 470.40625 C 349.210938 472.964844 347.953125 475.53125 346.765625 478.125 C 343.46875 485.328125 340.601562 492.710938 338.171875 500.25 L 296.875 500.25 C 288.242188 500.25 281.25 507.242188 281.25 515.875 L 281.25 609.625 C 281.25 618.257812 288.242188 625.25 296.875 625.25 L 338.171875 625.25 C 341.480469 635.515625 345.605469 645.503906 350.53125 655.09375 L 321.328125 684.277344 C 315.234375 690.382812 315.234375 700.269531 321.328125 706.375 L 387.625 772.671875 C 393.730469 778.765625 403.617188 778.765625 409.722656 772.671875 L 438.90625 743.484375 C 448.511719 748.390625 458.492188 752.515625 468.75 755.828125 L 468.75 797.125 C 468.75 805.757812 475.742188 812.75 484.375 812.75 L 578.125 812.75 C 586.757812 812.75 593.75 805.757812 593.75 797.125 L 593.75 755.828125 C 604.007812 752.515625 613.988281 748.390625 623.59375 743.484375 L 652.777344 772.671875 C 658.882812 778.765625 668.769531 778.765625 674.875 772.671875 L 741.171875 706.375 C 747.265625 700.269531 747.265625 690.382812 741.171875 684.277344 L 711.96875 655.09375 C 716.894531 645.503906 721.019531 635.515625 724.328125 625.25 L 765.625 625.25 C 774.257812 625.25 781.25 618.257812 781.25 609.625 Z M 750 594 L 712.59375 594 C 705.46875 594 699.25 598.824219 697.46875 605.71875 C 693.476562 621.226562 687.324219 636.109375 679.1875 649.90625 C 675.566406 656.039062 676.5625 663.851562 681.59375 668.890625 L 708.011719 695.324219 L 663.824219 739.53125 L 637.390625 713.09375 C 632.347656 708.054688 624.535156 707.0625 618.398438 710.6875 C 604.601562 718.824219 589.726562 724.980469 574.21875 728.96875 C 567.324219 730.75 562.5 736.96875 562.5 744.09375 L 562.5 781.5 L 500 781.5 L 500 744.09375 C 500 736.96875 495.175781 730.75 488.28125 728.96875 C 472.773438 724.980469 457.898438 718.824219 444.101562 710.6875 C 437.964844 707.0625 430.152344 708.054688 425.109375 713.09375 L 398.675781 739.53125 L 354.488281 695.324219 L 380.90625 668.890625 C 385.9375 663.851562 386.933594 656.039062 383.3125 649.90625 C 375.175781 636.109375 369.023438 621.226562 365.03125 605.71875 C 363.25 598.824219 357.03125 594 349.90625 594 L 312.5 594 L 312.5 531.5 L 349.90625 531.5 C 357.03125 531.5 363.25 526.675781 365.03125 519.78125 C 367.554688 509.945312 370.957031 500.367188 375.191406 491.144531 C 377.632812 485.796875 380.34375 480.582031 383.3125 475.519531 C 386.921875 469.382812 385.933594 461.582031 380.90625 456.546875 L 354.488281 430.113281 L 398.675781 385.90625 L 425.109375 412.351562 C 430.152344 417.390625 437.964844 418.375 444.101562 414.746094 C 457.898438 406.617188 472.773438 400.453125 488.28125 396.464844 C 495.175781 394.6875 500 388.460938 500 381.347656 L 500 344 L 562.5 344 L 562.5 381.40625 C 562.5 388.53125 567.324219 394.75 574.21875 396.53125 C 589.726562 400.519531 604.601562 406.675781 618.398438 414.8125 C 624.535156 418.4375 632.347656 417.445312 637.390625 412.40625 L 663.824219 385.96875 L 708.011719 430.175781 L 681.59375 456.609375 C 676.5625 461.648438 675.566406 469.460938 679.1875 475.59375 C 687.324219 489.390625 693.476562 504.273438 697.46875 519.78125 C 699.25 526.675781 705.46875 531.5 712.59375 531.5 L 750 531.5 Z M 750 594 " style=" stroke:none;fill-rule:nonzero;" />
<path d="M 781.25 781.5 L 875 781.5 L 875 812.75 L 781.25 812.75 Z M 781.25 781.5 " style=" stroke:none;fill-rule:nonzero;" />
</g>
</svg>
	</div>
</div>
<div class="w-full px-3">
<h3 class="text-base text-gray-700 font-semibold ">Customizable</h3>
<p class="text-sm text-gray-700 my-1 text-justify leading-loose">With Gego K12, it is easy to customize the grade system based on the syllabus with which the school is affiliated. For example, the tool is perfect for CBSE, State board, or even ICSE</p>
</div>
</div>
</div>
<!-- **** -->
</div>

</div>
@endsection 