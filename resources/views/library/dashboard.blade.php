{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.library.layout')

@section('content')
    <div class="">
        <div>
            <h1 class="admin-h1 font-plex my-3">Dashboard</h1>
        </div>
        @include('partials.message')
        <!-- start -->
        <div class="flex flex-wrap my-2">
            <div class="w-full xl:w-1/3 lg:w-1/2 my-2">
                <div class="flex flex-wrap lg:flex-row">
                    <div class="w-full lg:w-1/2 md:w-1/2 px-1 my-1">
                        <a href="{{ url('/library/books/index') }}">
                            <div class="bg-white custom-shadow px-4 py-3 border">
                                <div class="w-20 h-20 rounded-full bg-light-green flex items-center justify-center mx-auto text-green-500">
                                    <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 465 465" xml:space="preserve" class="w-10 h-10 fill-current"><g><path d="M240,356.071V132.12c0-4.143-3.357-7.5-7.5-7.5s-7.5,3.357-7.5,7.5v223.951c0,4.143,3.357,7.5,7.5,7.5 S240,360.214,240,356.071z"></path> <path d="M457.5,75.782c-15.856,0-35.614-6.842-56.533-14.085c-26.492-9.174-56.521-19.571-87.663-19.571 c-36.035,0-58.019,15.791-70.115,29.038c-4.524,4.956-8.03,9.922-10.688,14.327c-2.658-4.405-6.164-9.371-10.688-14.327 c-12.097-13.247-34.08-29.038-70.115-29.038c-31.143,0-61.171,10.397-87.663,19.571C43.114,68.94,23.356,75.782,7.5,75.782 c-4.143,0-7.5,3.357-7.5,7.5v302.092c0,4.143,3.357,7.5,7.5,7.5c18.38,0,39.297-7.243,61.441-14.911 c25.375-8.786,54.136-18.745,82.755-18.745c24.54,0,44.403,8.126,59.038,24.152c2.792,3.058,7.537,3.273,10.596,0.48 s3.273-7.537,0.48-10.596c-12.097-13.246-34.08-29.037-70.114-29.037c-31.143,0-61.171,10.397-87.663,19.571 C46.298,369.931,29.396,375.782,15,377.422V90.41c16.491-1.571,34.755-7.896,53.941-14.539 c25.375-8.786,54.136-18.745,82.755-18.745c57.881,0,73.025,45.962,73.634,47.894c0.968,3.148,3.876,5.298,7.17,5.298 s6.202-2.149,7.17-5.298c0.146-0.479,15.383-47.894,73.634-47.894c28.619,0,57.38,9.959,82.755,18.745 c19.187,6.644,37.45,12.968,53.941,14.539v287.012c-14.396-1.64-31.298-7.491-49.033-13.633 c-26.492-9.174-56.521-19.571-87.663-19.571c-36.036,0-58.02,15.791-70.115,29.038c-2.793,3.06-2.578,7.803,0.48,10.596 c3.06,2.793,7.804,2.578,10.596-0.48c14.635-16.027,34.498-24.153,59.039-24.153c28.619,0,57.38,9.959,82.755,18.745 c22.145,7.668,43.062,14.911,61.441,14.911c4.143,0,7.5-3.357,7.5-7.5V83.282C465,79.14,461.643,75.782,457.5,75.782z"></path><path d="M457.5,407.874c-15.856,0-35.614-6.842-56.533-14.085c-26.492-9.174-56.521-19.571-87.663-19.571 c-33.843,0-55.291,13.928-67.796,26.596l-26.017-0.001c-12.505-12.668-33.954-26.595-67.795-26.595 c-31.143,0-61.171,10.397-87.663,19.571c-20.919,7.243-40.677,14.085-56.533,14.085c-4.143,0-7.5,3.357-7.5,7.5s3.357,7.5,7.5,7.5 c18.38,0,39.297-7.243,61.441-14.911c25.375-8.786,54.136-18.745,82.755-18.745c24.54,0,44.403,8.126,59.038,24.152 c1.421,1.556,3.431,2.442,5.538,2.442l32.454,0.001c2.107,0,4.117-0.887,5.538-2.442c14.635-16.027,34.498-24.153,59.039-24.153 c28.619,0,57.38,9.959,82.755,18.745c22.145,7.668,43.062,14.911,61.441,14.911c4.143,0,7.5-3.357,7.5-7.5 S461.643,407.874,457.5,407.874z"></path></g></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800">{{ $dashboard['bookCount'] }}</p>
                                    <p class="text-base item-title">Total Books </p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="w-full lg:w-1/2 md:w-1/2 px-1 my-1">
                        <a href="{{ url('/library/booklending/index') }}" target="_blank">
                            <div class="bg-white custom-shadow px-3 py-3 border ">
                                <div class="w-20 h-20 rounded-full bg-light-blue flex items-center justify-center mx-auto text-blue-500">
                                    <svg class="w-10 h-10 fill-current" height="482pt" viewBox="0 0 482.86474 482" width="482pt" xmlns="http://www.w3.org/2000/svg"><path d="m473.441406 248.320312c-5.984375-6.039062-14.128906-9.4375-22.628906-9.4375s-16.648438 3.398438-22.628906 9.4375l-93.839844 93.710938-.183594.175781c4.726563-9.914062 4.042969-21.558593-1.820312-30.847656-5.859375-9.292969-16.074219-14.925781-27.058594-14.925781h-168c-2.125 0-4.15625.84375-5.65625 2.34375l-27.769531 27.765625-19.648438-34.03125c-2.207031-3.824219-7.101562-5.136719-10.925781-2.925781l-69.28125 40c-3.828125 2.207031-5.136719 7.101562-2.929688 10.925781l80 138.5625c2.210938 3.824219 7.101563 5.136719 10.929688 2.925781l69.28125-40c3.824219-2.207031 5.136719-7.101562 2.925781-10.925781l-13.070312-22.640625h190.863281c10.605469-.027344 20.773438-4.230469 28.296875-11.707032l103.109375-103.199218c6.050781-5.964844 9.457031-14.109375 9.457031-22.605469 0-8.5-3.40625-16.644531-9.457031-22.609375zm-382.511718 215.824219-72-124.703125 55.421874-32 72 124.703125zm371.148437-181.929687-103.117187 103.203125c-4.511719 4.476562-10.605469 6.996093-16.960938 7.015625h-200.097656l-3.292969-5.707032-26.457031-45.855468 28.4375-28.4375h164.691406c8.835938 0 16 7.164062 16 16 0 8.835937-7.164062 16-16 16h-88v16h111.359375c6.386719.015625 12.515625-2.527344 17.015625-7.066406l93.863281-93.734376c6.292969-6.109374 16.300781-6.109374 22.59375 0 3.046875 2.96875 4.769531 7.042969 4.769531 11.300782 0 4.253906-1.722656 8.332031-4.769531 11.296875zm0 0"/><path d="m161.28125 272.433594h176c4.417969 0 8-3.582032 8-8v-256c0-4.417969-3.582031-8-8-8h-176c-22.082031.023437-39.976562 17.917968-40 40v192c-.011719 10.609375 4.199219 20.792968 11.703125 28.292968 7.503906 7.503907 17.683594 11.714844 28.296875 11.707032zm0-16c-13.257812 0-24-10.746094-24-24 0-13.257813 10.742188-24 24-24h168v16h-168v16h168v16zm168-64h-160v-176h160zm-192-152c.039062-10.136719 6.445312-19.152344 16-22.527344v175.324219c-.570312.121093-1.089844.363281-1.648438.507812-1.628906.394531-3.234374.890625-4.800781 1.484375-.800781.3125-1.550781.648438-2.320312 1.007813-1.457031.683593-2.875 1.453125-4.238281 2.304687-.671876.417969-1.355469.800782-1.992188 1.273438-.328125.222656-.691406.390625-1 .632812zm0 0"/><path d="m201.28125 112.433594h96c4.417969 0 8-3.582032 8-8v-48c0-4.417969-3.582031-8-8-8h-96c-4.417969 0-8 3.582031-8 8v48c0 4.417968 3.582031 8 8 8zm8-48h80v32h-80zm0 0"/></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800">{{ $dashboard['booklendingCount'] }}</p>
                                    <p class="text-base  item-title">Books Lent </p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="w-full lg:w-1/2 md:w-1/2 px-1 my-1">
                        <a href="#">
                            <div class="bg-white custom-shadow px-3 py-3 border">
                                <div class="w-20 h-20 rounded-full bg-light-orange flex items-center justify-center mx-auto text-orange-500">
                                    <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 490.667 490.667" xml:space="preserve" class="w-10 h-10 fill-current"><g><g><path d="M245.333,85.333c-41.173,0-74.667,33.493-74.667,74.667s33.493,74.667,74.667,74.667S320,201.173,320,160 C320,118.827,286.507,85.333,245.333,85.333z M245.333,213.333C215.936,213.333,192,189.397,192,160 c0-29.397,23.936-53.333,53.333-53.333s53.333,23.936,53.333,53.333S274.731,213.333,245.333,213.333z"></path></g></g> <g><g><path d="M394.667,170.667c-29.397,0-53.333,23.936-53.333,53.333s23.936,53.333,53.333,53.333S448,253.397,448,224 S424.064,170.667,394.667,170.667z M394.667,256c-17.643,0-32-14.357-32-32c0-17.643,14.357-32,32-32s32,14.357,32,32 C426.667,241.643,412.309,256,394.667,256z"></path></g></g> <g><g><path d="M97.515,170.667c-29.419,0-53.333,23.936-53.333,53.333s23.936,53.333,53.333,53.333s53.333-23.936,53.333-53.333 S126.933,170.667,97.515,170.667z M97.515,256c-17.643,0-32-14.357-32-32c0-17.643,14.357-32,32-32c17.643,0,32,14.357,32,32 C129.515,241.643,115.157,256,97.515,256z"></path></g></g> <g><g><path d="M245.333,256c-76.459,0-138.667,62.208-138.667,138.667c0,5.888,4.779,10.667,10.667,10.667S128,400.555,128,394.667 c0-64.704,52.629-117.333,117.333-117.333s117.333,52.629,117.333,117.333c0,5.888,4.779,10.667,10.667,10.667 c5.888,0,10.667-4.779,10.667-10.667C384,318.208,321.792,256,245.333,256z"></path></g></g> <g><g><path d="M394.667,298.667c-17.557,0-34.752,4.8-49.728,13.867c-5.013,3.072-6.635,9.621-3.584,14.656 c3.093,5.035,9.621,6.635,14.656,3.584C367.637,323.712,380.992,320,394.667,320c41.173,0,74.667,33.493,74.667,74.667 c0,5.888,4.779,10.667,10.667,10.667c5.888,0,10.667-4.779,10.667-10.667C490.667,341.739,447.595,298.667,394.667,298.667z"></path></g></g> <g><g><path d="M145.707,312.512c-14.955-9.045-32.149-13.845-49.707-13.845c-52.928,0-96,43.072-96,96 c0,5.888,4.779,10.667,10.667,10.667s10.667-4.779,10.667-10.667C21.333,353.493,54.827,320,96,320 c13.675,0,27.029,3.712,38.635,10.752c5.013,3.051,11.584,1.451,14.656-3.584C152.363,322.133,150.741,315.584,145.707,312.512z"></path></g></g></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800">{{ $dashboard['cardHolderCount'] }}</p>
                                    <p class="text-base  item-title">Library Card Holder</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="w-full lg:w-1/2 md:w-1/2 px-1 my-1">
                        <a href="{{ url('/library/bookscategory/index') }}" target="_blank">
                            <div class="bg-white custom-shadow px-3 py-3 border ">
                                <div class="w-20 h-20 rounded-full bg-light-red flex items-center justify-center mx-auto text-red-500">
                                    <svg id="Layer_1" enable-background="new 0 0 512.5 512.5" height="512" viewBox="0 0 512.5 512.5" width="512" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 fill-current"><g><path d="m512.212 203.854c-.314-3.737-3.711-6.813-7.462-6.751h-96.17v-100.624h22.085c26.599 0 48.239-21.64 48.239-48.239s-21.64-48.24-48.239-48.24h-324.357c-9.697 0-9.697 15 0 15h17.987v66.479h-108.85c-4.142 0-7.5 3.358-7.5 7.5v26.953c0 9.697 15 9.697 15 0v-19.453h206.138v100.624h-206.138v-42.977c0-9.697-15-9.697-15 0v50.477c0 4.142 3.358 7.5 7.5 7.5h35.902v98.216h-6.924c-24.357 0-44.174 19.816-44.174 44.174s19.816 44.174 44.174 44.174h12.182c-41.111 36.161-21.441 105.003 32.595 112.683 4.544.635 9.487 1.15 14.05 1.15.054 0 .217-.002.163-.002-.163 0 252.562-.371 276.09-.498 11.508.238 11.409-15.091-.081-15h-281.172c-53.232 0-66.152-78.185-16.121-95.605 8.012-1.998 11.411-2.645 16.121-2.645 0 0 354.674.053 375.167.017v22.677h-301.41c-9.697 0-9.697 15 0 15h301.409v22.778h-219.389c-9.697 0-9.697 15 0 15h219.389v22.778h-52.154c-9.697 0-9.697 15 0 15h79.654c9.697 0 9.697-15 0-15h-12.5v-98.276c4.186-.017 8.373-.032 12.546-.058 9.696-.059 9.608-15.133-.092-15-6.28.039-76.593 0-76.593 0v-58.348h80.473c4.142 0 7.5-3.358 7.5-7.5v-113.215c0-.252-.013-.501-.038-.749zm-118.632-6.751h-24.997-2.5v-100.624h27.497zm-254.285-140.532h163.427c9.697 0 9.697-15 0-15h-163.427v-26.571h291.37c18.328 0 33.239 14.911 33.239 33.239s-14.911 33.239-33.239 33.239h-291.37zm104.788 39.908h107v100.624h-107zm-177.735 115.624h294.736v98.216h-294.736zm342.929 136.448h-97.815c-9.697 0-9.697 15 0 15h97.815v20.104c-17.414-.08-364.854.011-364.854.011-16.086 0-29.174-13.087-29.174-29.174s13.087-29.174 29.174-29.174h364.854zm87.973-38.232h-121.167v-98.216h121.167z"></path><path d="m212.25 238.711h-102.009c-12.406 0-22.5 10.093-22.5 22.5s10.093 22.5 22.5 22.5h102.009c12.407 0 22.5-10.093 22.5-22.5s-10.093-22.5-22.5-22.5zm0 30h-102.009c-4.135 0-7.5-3.364-7.5-7.5s3.364-7.5 7.5-7.5h102.009c4.136 0 7.5 3.364 7.5 7.5s-3.364 7.5-7.5 7.5z"></path><path d="m412.583 268.711h48.833c9.697 0 9.697-15 0-15h-48.833c-9.697 0-9.697 15 0 15z"></path><path d="m324.917 126.625c-4.142 0-7.5 3.358-7.5 7.5v26.163c0 4.142 3.358 7.5 7.5 7.5s7.5-3.358 7.5-7.5v-26.163c0-4.142-3.358-7.5-7.5-7.5z"></path><path d="m297.583 126.625c-4.142 0-7.5 3.358-7.5 7.5v26.163c0 4.142 3.358 7.5 7.5 7.5s7.5-3.358 7.5-7.5v-26.163c0-4.142-3.357-7.5-7.5-7.5z"></path><path d="m270.25 126.625c-4.142 0-7.5 3.358-7.5 7.5v26.163c0 4.142 3.358 7.5 7.5 7.5s7.5-3.358 7.5-7.5v-26.163c0-4.142-3.358-7.5-7.5-7.5z"></path></g></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800">{{ $dashboard['categoryCount'] }}</p>
                                    <p class="text-base item-title">Book Category</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!--upcoming events-->
            <div class="w-full xl:w-1/3 lg:w-1/2 md:w-1/2 px-1 my-3">
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
                                <p class="text-sm text-gray-900 font-semibold" style="text-align: center;">No Records Found</p>
                            </div>
                        @endif
                    </div>   
                </div>
            </div>
            <!--upcoming events-->

            <div class="w-full xl:w-1/3 lg:w-full md:w-1/2 px-1 my-3">
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
                                        @if($noticeboard->attachment_file > '')
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
        <div class="flex flex-col lg:flex-row my-2">
            <div class="w-full lg:w-2/3  px-1 my-2">
                <div class="bg-white custom-shadow  py-1 border h-full">
                    <div>
                        <h1 class="text-gray-800 px-3 font-semibold text-xl py-2 pb-3">Overdue Students List</h1>
                    </div>
                    <div class="exam-box mt-2 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-grey-light">
                                <tr>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Student Name</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base w-40">Book Code</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Library Card Number</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Return Date</th>
                                </tr>
                            </thead>
                            @if(count($dashboard['booklendings']) > 0)
                                <tbody>
                                    @foreach($dashboard['booklendings'] as $booklending)
                                        <tr class="border-t align-baseline">
                                            <td class="py-3 px-3 w-64">{{ $booklending->user[0]['FullName'] }}</td>
                                            <td class="py-3 px-3">{{ $booklending->book_code_no }}</td>
                                            <td class="py-3 px-3">{{ $booklending->library_card_no }}</td>
                                            <td class="py-3 px-3">{{ $booklending->return_date }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>  
                            @else
                                <tbody>
                                    <tr class="border-t py-2">
                                        <td colspan="4" class="text-center py-2">No Records found</td>
                                    </tr>
                                </tbody> 
                            @endif    
                        </table>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/3  px-1 my-2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border h-full">
                    <view-birthday-teacher url="{{ url('/') }}" mode="library"></view-birthday-teacher>
                    <view-work-anniversary url="{{ url('/') }}" mode="library"></view-work-anniversary>
                </div>
            </div>
        </div>
        <!-- end -->

        <!-- start -->
        <div class="flex flex-wrap">
            <!--Task Module-->
            <div class="w-full lg:w-1/3 md:w-full">
                <dashboard-task url="{{ url('/') }}" mode="library"></dashboard-task>
            </div>
        </div>
        <!--end-->
    </div>
@endsection