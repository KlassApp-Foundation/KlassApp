{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.reception.layout')

@section('content')

    <div class="">
        <div>
            <h1 class="admin-h1 font-plex my-3">Dashboard</h1>
        </div>
        @include('partials.message')
        <!-- start -->
        <div class="flex flex-col lg:flex-row my-2">
            <div class="w-full lg:w-1/3 md:w-1/3 my-2">
                <div class="flex flex-wrap lg:flex-row">
                    <div class=" w-full lg:w-1/2 px-1 my-1">
                        <a href="#">
                            <div class="bg-white custom-shadow px-4 py-3 border">
                                <div class="w-20 h-20 rounded-full bg-light-green flex items-center justify-center mx-auto text-green-500">
                                    <svg class="w-10 h-10 fill-current" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><circle cx="139.691" cy="46.434" r="41.755"/></g></g><g><g><path d="M268.897,275.556c-0.329-0.904-33.225-90.905-47.714-133.063c-9.347-27.201-25.02-40.993-46.585-40.993 c-1.113,0-2.189,0-3.248,0c5.378,5.221,9.48,11.748,11.769,19.073c10.487,3.63,18.04,13.598,18.04,25.304v60.934 c14.372,40.119,29.668,81.966,29.894,82.581c3.821,10.45,15.388,15.825,25.84,12.004 C267.343,297.576,272.718,286.007,268.897,275.556z"/></g></g><g><g><path d="M104.793,101.5c-21.562,0-37.236,13.792-46.584,40.992c-14.488,42.156-47.384,132.16-47.715,133.064 c-3.821,10.45,1.553,22.02,12.003,25.84c10.442,3.82,22.018-1.547,25.84-12.004c0.225-0.617,15.521-42.463,29.893-82.581v-60.934 c0-11.705,7.554-21.673,18.04-25.304c2.289-7.325,6.392-13.852,11.77-19.073C106.983,101.5,105.906,101.5,104.793,101.5z"/></g></g><g><g><path d="M219.631,328.345c-8.461-43.676-14.352-74.023-18.47-95.246v4.717c0,14.764-12.011,26.774-26.774,26.774h-69.38 c-14.764,0-26.774-12.011-26.774-26.774v-4.725c-4.119,21.229-10.011,51.58-18.47,95.255c-1.342,6.93,3.999,13.333,10.988,13.333 c4.339,0,9.546,0,15.374,0v146.144c0,13.353,10.824,24.177,24.177,24.177c13.353,0,24.177-10.824,24.177-24.177V341.678 c3.475,0,6.963,0,10.438,0v146.144c0,13.353,10.824,24.177,24.177,24.177s24.177-10.824,24.177-24.177V341.678 c5.829,0,11.035,0,15.373,0C215.667,341.678,220.971,335.267,219.631,328.345z"/></g></g><g><g><path d="M174.385,134.117h-4.22c0-5.457-1.449-10.579-3.973-15.014c-5.244-9.22-15.153-15.456-26.497-15.456 s-21.253,6.236-26.498,15.456c-2.523,4.435-3.973,9.557-3.973,15.014h-4.22c-6.495,0-11.76,5.265-11.76,11.76v91.939 c0,6.495,5.265,11.76,11.76,11.76h69.38c6.495,0,11.76-5.265,11.76-11.76v-91.938C186.145,139.382,180.88,134.117,174.385,134.117 z M136.09,119.103h7.21c6.786,1.631,11.851,7.735,11.851,15.014h-30.912C124.239,126.837,129.304,120.733,136.09,119.103z M156.044,220.882h-32.697c-5.527,0-10.009-4.481-10.009-10.009c0-5.528,4.482-10.009,10.009-10.009h32.697 c5.527,0,10.009,4.481,10.009,10.009C166.053,216.401,161.571,220.882,156.044,220.882z"/></g></g><g><g><circle cx="399.284" cy="42.139" r="42.139"/></g></g><g><g><path d="M502.735,145.591c-0.132-26.4-22.055-47.877-48.868-47.877h-26.473c7.129,5.621,12.549,13.316,15.318,22.178 c0.094,0.033,0.185,0.074,0.279,0.108c11.252,3.827,19.502,14.76,19.078,27.325v147.747c0,11.23,9.104,20.333,20.333,20.333 s20.333-9.104,20.333-20.333V145.693C502.735,145.659,502.735,145.625,502.735,145.591z"/></g></g><g><g><path d="M433.979,263.909h-69.381v-0.001c-7.505,0-14.403-3.108-19.268-8.1c0,0.081,0,5.039,0,231.79 c0,13.476,10.816,24.4,24.292,24.4s24.4-10.924,24.4-24.4V292.571h10.534V487.6c0,13.476,10.924,24.4,24.4,24.4 s24.292-10.924,24.292-24.4c0-221.379,0-231.79,0-231.79C448.38,260.802,441.484,263.909,433.979,263.909z"/></g></g><g><g><path d="M344.709,97.714c-26.814,0-48.736,21.477-48.868,47.877c0,0.034,0,0.068,0,0.102v149.379 c0,11.229,9.104,20.333,20.333,20.333c11.229,0,20.333-9.104,20.333-20.333V149.73c-1.679-13.61,7.057-25.966,19.376-29.899 c2.776-8.836,8.186-16.509,15.298-22.118H344.709z"/></g></g><g><g><path d="M433.978,133.435h-4.22c0-5.457-1.449-10.579-3.972-15.014c-5.245-9.22-15.153-15.456-26.498-15.456 c-11.345-0.001-21.253,6.236-26.498,15.456c-2.523,4.435-3.973,9.557-3.973,15.014h-4.22c-6.495,0-11.76,5.265-11.76,11.76v91.939 c0,6.495,5.265,11.76,11.76,11.76h69.38c6.495,0,11.76-5.265,11.76-11.76v-91.939C445.738,138.7,440.473,133.435,433.978,133.435z M395.684,118.421h7.208c6.787,1.63,11.852,7.734,11.852,15.014h-30.912C383.832,126.155,388.896,120.051,395.684,118.421z M415.636,220.2h-32.698c-5.528,0-10.009-4.481-10.009-10.009c0-5.528,4.481-10.009,10.009-10.009h32.698 c5.528,0,10.009,4.481,10.009,10.009S421.165,220.2,415.636,220.2z"/></g></g></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800">{{ $dashboard['studentCount'] }}</p>
                                    <p class="text-base  item-title">Students</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="w-full lg:w-1/2 px-1 my-1">
                        <a href="#">
                            <div class="bg-white custom-shadow px-3 py-3 border ">
                                <div class="w-20 h-20 rounded-full bg-light-blue flex items-center justify-center mx-auto text-blue-500">
                                    <svg class="w-10 h-10 fill-current" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><path d="m88 360h32v16h-32z"/><path d="m82.207 459.624a18.922 18.922 0 0 0 18.868 20.376c.159 0 .312-.02.47-.024a8.169 8.169 0 0 1 .9-.045 18.936 18.936 0 0 0 17.555-18.856v-69.075h-32.592z"/><path d="m288 448v-107.293l-16.977 31.124a8 8 0 0 1 -14.046 0l-45.726-83.831h-26.771a40.489 40.489 0 0 0 -28.693 12.087 38.344 38.344 0 0 0 -8.419 12.913l-11.368 28.307v97.193l37.456-69.641a70.053 70.053 0 0 1 10.4-19.443c.034-.057.063-.119.1-.175l.142-.249.018.01a8.225 8.225 0 0 1 .827-1 70.515 70.515 0 0 1 9.939-10.144l10.244 12.292a54.544 54.544 0 0 0 -6.976 76.733 8 8 0 0 1 1.85 5.117v48h128v-17.441l-33.569-6.714a8 8 0 0 1 -6.431-7.845z"/><path d="m184.152 349.023c.162-.21.321-.422.485-.631a8 8 0 0 0 -.509.618z"/><path d="m299.428 286.341a19.216 19.216 0 0 1 -11.428-17.541v-24.483a80.039 80.039 0 0 1 -48 0v24.483a19.216 19.216 0 0 1 -11.428 17.541l9.628 17.659h51.6z"/><path d="m156.466 279.558a56.358 56.358 0 0 1 28.014-7.558h36.32a3.243 3.243 0 0 0 3.2-3.2v-31.563a80.026 80.026 0 0 1 -40-69.237v-40a8 8 0 0 1 3.562-6.656 100.785 100.785 0 0 0 34.182-38.721l3.1-6.2a8 8 0 0 1 12.277-2.568l4.543 3.787a191.273 191.273 0 0 0 95.466 42.439 8 8 0 0 1 6.87 7.919v40a80.026 80.026 0 0 1 -40 69.237v31.563a3.243 3.243 0 0 0 3.2 3.2h36.24a55.951 55.951 0 0 1 28.1 7.607l.909-8.244a122.558 122.558 0 0 0 .752-12.8 114.657 114.657 0 0 0 -9.357-45.258 131.612 131.612 0 0 1 -10.392-58.193l1.359-27.662c.077-1.618.15-3.157.15-4.567a91.024 91.024 0 0 0 -90.961-90.883 90.941 90.941 0 0 0 -90.81 95.443l1.36 27.684c.086 2.206.17 4.4.17 6.633a129.949 129.949 0 0 1 -10.569 51.556 114.7 114.7 0 0 0 -8.6 58.021z"/><path d="m328 168v-33.18a207.224 207.224 0 0 1 -93.376-42.273 116.869 116.869 0 0 1 -34.624 39.637v35.816a64 64 0 0 0 128 0zm-100-8a12 12 0 1 1 12-12 12.013 12.013 0 0 1 -12 12zm36 40a24.027 24.027 0 0 1 -24-24h16a8 8 0 0 0 16 0h16a24.027 24.027 0 0 1 -24 24zm36-40a12 12 0 1 1 12-12 12.013 12.013 0 0 1 -12 12z"/><path d="m281.069 320h-34.138l17.069 31.293z"/><path d="m100.762 275.056a7.56 7.56 0 0 0 -4.929 10.717l7.323 14.648a8 8 0 0 1 -7.156 11.579h-28.669a56.284 56.284 0 0 1 -25.043-5.912l-7.973-3.988a1.526 1.526 0 0 0 -1.556.07 1.534 1.534 0 0 0 -.759 1.363v22.578a7.958 7.958 0 0 0 4.422 7.156l13.021 6.51a40.2 40.2 0 0 0 17.888 4.223h52.669v-38.459l-10.383-25.958a7.589 7.589 0 0 0 -8.855-4.527z"/><path d="m304 441.441 64 12.8v-95.682l-64-12.8z"/><path d="m426.082 468.542-16.445 3.289-.055.011h-.013c-.059.011-.118.015-.177.026-.149.026-.3.051-.449.068a16.39 16.39 0 0 0 14.097 8.064 14.308 14.308 0 0 0 2.528-.2 16.678 16.678 0 0 0 8.57-4.245 15.791 15.791 0 0 1 -8.056-7.015z"/><path d="m416 454.241 8-1.6v-21.7a18.357 18.357 0 0 1 15.387-18.164l27.172-4.528a18.361 18.361 0 0 1 13.441 2.989v-65.479l-64 12.8z"/><path d="m416.989 331.946a20.263 20.263 0 0 0 -15.365 12.054h5.584l35.165-7.033 37.627-7.526v-8z"/><path d="m471.146 424.574a2.4 2.4 0 0 0 -1.546-.574 2.621 2.621 0 0 0 -.413.034l-27.173 4.529a2.408 2.408 0 0 0 -2.014 2.382v29.735l32-5.334h.006l-.006-28.929a2.387 2.387 0 0 0 -.854-1.843z"/><path d="m372.263 300.377-.148-.152a39.635 39.635 0 0 0 -28.675-12.225h-26.691l-9.674 17.735 62.567 10.428a36.387 36.387 0 0 1 14.357 5.7l-3.437-8.677a37.073 37.073 0 0 0 -8.299-12.809z"/><path d="m367.012 331.946-63.012-10.503v8l36.4 7.28 36.4 7.279h5.584a20.263 20.263 0 0 0 -15.372-12.056z"/><path d="m384 360h16v96h-16z"/></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800">{{ $dashboard['teacherCount'] }}</p>
                                    <p class="text-base  item-title">Teachers</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!--upcoming events-->
            <div class="w-full lg:w-1/3 md:w-1/3 px-1 my-3">
                <div class="bg-white custom-shadow px-3 py-2 border">
                    <div>
                        <h1 class="text-gray-800 font-semibold text-lg border-b mx-2 py-1 pb-3">Upcoming Events</h1>
                    </div>
                    <div class="notice-box">
                        @if(count($dashboard['events']) > 0)
                            @foreach($dashboard['events'] as $events)
                                <div class="notice-box-list py-3 mx-3 border-b">
                                    <div class="bg-teal-500 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>{{ $events->title }}</p>
                                    </div>
                                    <div class="bg-purple-500 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>{{ date('d M Y H:i',strtotime($events->start_date)) }}</p>
                                    </div>
                                    <div class="bg-orange-500 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>
                                            @if($events->select_type == 'class')
                                                {{ $events->standardLink->StandardSection }}
                                            @else
                                                {{ ucwords($events->select_type) }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="my-1">
                                        <p class="text-sm text-gray-900 font-semibold">{{ $events->description }}</p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="notice-box-list py-3 mx-3 border-b">
                                <p class="text-sm text-gray-900 font-semibold" style="text-align: center;">No records Found</p>
                            </div>
                        @endif
                    </div>   
                </div>
            </div>
            <!--upcoming events-->

            <div class="w-full lg:w-1/3 md:w-1/3 px-1 my-3">
                <div class="bg-white custom-shadow px-3 py-2 border">
                    <div>
                        <h1 class="text-gray-800 font-semibold text-lg border-b mx-2 py-1 pb-3">Notice Board</h1>
                    </div>
                    <div class="notice-box">
                        @if(count($dashboard['noticeboard']) > 0)
                            @foreach($dashboard['noticeboard'] as $noticeboard)
                                <div class="notice-box-list py-3 mx-3 border-b">
                                    <div class="bg-teal-500 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>{{ $noticeboard->title }}</p>
                                    </div>
                                    <div class="bg-purple-500 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>{{ date('d M Y',strtotime($noticeboard->publish_date)) }}</p>
                                    </div>
                                    <div class="bg-orange-500 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>
                                            @if($noticeboard->type == 'class')
                                                {{ $noticeboard->standardLink->StandardSection }}
                                            @else
                                                {{ ucwords($noticeboard->type) }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="my-1">
                                        <p class="text-sm text-gray-900 font-semibold">{!! $noticeboard->description !!}</p>
                                    </div>
                                    <div class="text-sm my-1">
                                        <p class="text-gray-500">
                                            <span class="text-gray-500">{{ $noticeboard->created_at->diffForHumans() }}</span>
                                        </p>
                                        @if($noticeboard->attachment_file != '')
                                            <div class="flex justify-end">
                                                <a href="{{ $noticeboard->AttachmentPath }}" target="_blank">
                                                    <svg class="w-5 h-5 fill-current mr-1 text-gray-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><path d="M472,313v139c0,11.028-8.972,20-20,20H60c-11.028,0-20-8.972-20-20V313H0v139c0,33.084,26.916,60,60,60h392 c33.084,0,60-26.916,60-60V313H472z"/></g></g><g><g><polygon points="352,235.716 276,311.716 276,0 236,0 236,311.716 160,235.716 131.716,264 256,388.284 380.284,264"/></g></g></svg>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="notice-box-list py-3 mx-3 border-b">
                                <p class="text-sm text-gray-900 font-semibold" style="text-align: center;">No Notice Found</p>
                            </div>
                        @endif
                    </div>   
                </div>
            </div>
        </div>
        <!-- end -->

        <!-- start -->
        <div class="flex flex-wrap">
            <!--Task Module-->
            <div class="w-full lg:w-1/3 md:w-1/2">
                <dashboard-task url="{{ url('/') }}" mode="receptionist"></dashboard-task>
            </div>
        </div>
        <!--end-->
    </div>
@endsection