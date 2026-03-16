{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="">
        @include('partials.message')
        <div class="py-2 mt-2 border-b">
            <h1 class="admin-h1 mb-3 flex items-center">
                <a  href="{{ url('/admin/staffs') }}" class="rounded-full bg-gray-100 p-2" title="Back">
                    <svg class="w-3 h-3 fill-current text-gray-700" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 492 492" style="enable-background:new 0 0 492 492;" xml:space="preserve" width="512px" height="512px"><g><g><g><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124 c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844 L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412 c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008 c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788 C492,219.198,479.172,207.418,464.344,207.418z" data-original="#000000" class="active-path" fill=""/></g></g></g> </svg>
                </a>
                <span class="mx-3">Non-Teaching Staff Profile</span>
            </h1>
        </div>
        <div class="flex flex-col lg:flex-row md:flex-col">
            <div class="w-full xl:w-1/5 lg:w-1/4 md:w-full py-3">
                <!-- start -->
                <div class="bg-white  rounded leading-relaxed">
                    <div class="relative">
                        <div class="p-4">
                            <img src="{{ $user->userprofile->AvatarPath }}" class="w-32 h-32 lg:w-full lg:max-h-48 lg:h-48 object-cover mx-auto">
                        </div>
                        <div class=" mx-auto p-2 ">
                            <ul class="flex justify-center">
                                <li class="mx-2">
                                    <a href="{{url('/admin/staff/edit/'.$user->name)}}" title="Edit Member" class="text-white text-xs flex items-center blue-bg rounded p-1" id="edit">
                                        <svg class="w-3 h-3 fill-current text-white" id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m128.285 260.925h319.073v75h-319.073z" transform="matrix(.707 -.707 .707 .707 -126.717 290.929)"/><path d="m29.021 422.521-29.021 89.479 89.481-29.02z"/><path d="m54.039 186.679h319.073v75h-319.073z" transform="matrix(.707 -.707 .707 .707 -95.964 216.682)"/><path d="m371.541 5.46h90v180h-90z" transform="matrix(.707 -.707 .707 .707 54.502 322.498)"/><path d="m57.148 335.796-17.737 54.689 82.106 82.105 54.689-17.737z"/></g></svg>
                                        <span class="mx-1">Edit</span>
                                    </a>
                                </li>

                                <li class="mx-2">
                                    <form action="{{ url('/admin/staff/delete', ['name'=>$user->name]) }}" method="POST" class="text-white text-xs flex items-center bg-red-600 rounded p-1" id="delete">
                                        @csrf
                                        @method('delete')
                                   
                                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve" class="w-3 h-3 fill-current text-white"><g><g><g><polygon points="353.574,176.526 313.496,175.056 304.807,412.34 344.885,413.804"></polygon><rect x="235.948" y="175.791" width="40.104" height="237.285"></rect><polygon points="207.186,412.334 198.497,175.049 158.419,176.52 167.109,413.804"></polygon> <path d="M17.379,76.867v40.104h41.789L92.32,493.706C93.229,504.059,101.899,512,112.292,512h286.74 c10.394,0,19.07-7.947,19.972-18.301l33.153-376.728h42.464V76.867H17.379z M380.665,471.896H130.654L99.426,116.971h312.474 L380.665,471.896z"></path></g></g></g> <g><g><path d="M321.504,0H190.496c-18.428,0-33.42,14.992-33.42,33.42v63.499h40.104V40.104h117.64v56.815h40.104V33.42 C354.924,14.992,339.932,0,321.504,0z"></path></g></g></svg>
                                        <button type="submit" class="mx-1">Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="my-2 px-2">
                        <p class="capitalize text-gray-600 text-sm">basic information :</p>
                        <ul class="list-reset text-xs leading-relaxed my-2">
                            <li class="flex py-1">
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 fill-current text-gray-800" id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m144 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m144 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m144 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m272 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m272 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m272 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m400 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m400 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m400 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m467 65h-36v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-130v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-130v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-36c-24.813 0-45 20.187-45 45v332c0 24.813 20.187 45 45 45h422c24.813 0 45-20.187 45-45 0-9.682 0-323.575 0-332 0-24.813-20.187-45-45-45zm-437 45c0-8.271 6.729-15 15-15h36v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h130v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h130v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h36c8.271 0 15 6.729 15 15v59h-452zm437 347h-422c-8.271 0-15-6.729-15-15v-243h452v243c0 8.271-6.729 15-15 15z"/></g></svg>
                                    <span class="mx-2 text-gray-700 font-medium">Date Of Birth  :</span>
                                </div>
                                <div class="">
                                    <p>{{ date('d-m-Y',strtotime(optional($user->userprofile)->date_of_birth)) }}</p>
                                </div>
                            </li>
                            <li class="flex py-1">
                                <div class="flex items-center">
                                    <!-- <svg class="w-3 h-3 fill-current text-gray-800" id="Layer_1" enable-background="new 0 0 511.805 511.805" height="512" viewBox="0 0 511.805 511.805" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m218.3 133.026c-7.04-9.11-13.171-16.409-18.224-21.694-7.182-7.512-13.125-12.168-20.55-12.168s-13.368 4.656-20.55 12.167c-5.053 5.285-11.185 12.584-18.224 21.695-14.86 19.23-31.528 43.594-46.934 68.601-24.292 39.432-65.043 112.577-65.043 159.428 0 83.124 67.626 150.75 150.75 150.75s150.75-67.626 150.75-150.75c0-46.85-40.751-119.996-65.043-159.428-15.405-25.007-32.073-49.37-46.932-68.601z"/><path d="m447.426 57.784c-8.311-13.491-17.317-26.653-25.359-37.061-12.422-16.075-18.291-20.723-26.165-20.723s-13.743 4.648-26.164 20.723c-8.042 10.408-17.048 23.569-25.359 37.061-16.242 26.365-35.603 63.103-35.603 88.395 0 48.042 39.085 87.126 87.126 87.126 48.042 0 87.127-39.085 87.127-87.126 0-25.293-19.361-62.03-35.603-88.395z"/></g></svg> -->
                                    <svg id="Layer_1" enable-background="new 0 0 511.805 511.805" height="512" viewBox="0 0 511.805 511.805" width="512" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current text-gray-800"><g><path d="m218.3 133.026c-7.04-9.11-13.171-16.409-18.224-21.694-7.182-7.512-13.125-12.168-20.55-12.168s-13.368 4.656-20.55 12.167c-5.053 5.285-11.185 12.584-18.224 21.695-14.86 19.23-31.528 43.594-46.934 68.601-24.292 39.432-65.043 112.577-65.043 159.428 0 83.124 67.626 150.75 150.75 150.75s150.75-67.626 150.75-150.75c0-46.85-40.751-119.996-65.043-159.428-15.405-25.007-32.073-49.37-46.932-68.601zm-38.774 348.779c-66.582 0-120.75-54.168-120.75-120.75 0-27.47 20.462-77.186 56.14-136.397 26.795-44.469 52.544-78.315 64.61-91.438 12.065 13.122 37.815 46.968 64.61 91.438 35.678 59.212 56.14 108.927 56.14 136.397 0 66.582-54.169 120.75-120.75 120.75z"></path><path d="m447.426 57.784c-8.311-13.491-17.317-26.653-25.359-37.061-12.422-16.075-18.291-20.723-26.165-20.723s-13.743 4.648-26.164 20.723c-8.042 10.408-17.048 23.569-25.359 37.061-16.242 26.365-35.603 63.103-35.603 88.395 0 48.042 39.085 87.126 87.126 87.126 48.042 0 87.127-39.085 87.127-87.126 0-25.293-19.361-62.03-35.603-88.395zm-51.524 145.521c-31.5 0-57.126-25.627-57.126-57.126 0-12.89 10.432-38.055 27.906-67.319 11.112-18.61 22.066-33.947 29.221-42.897 7.155 8.951 18.109 24.288 29.221 42.897 17.474 29.264 27.906 54.429 27.906 67.319-.001 31.499-25.628 57.126-57.128 57.126z"></path></g></svg>
                                    <span class="mx-2 text-gray-700 font-medium">Blood Group :</span>
                                </div>
                                <div class="">
                                    <p class="capitalize">{{ strtoupper($user->userprofile->blood_group) }}</p>
                                </div>
                            </li>
                            <li class="flex py-1">
                                <div class="flex items-center">
                                   <!-- <svg class="w-3 h-3 fill-current text-gray-800" xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 512 512" height="512px" viewBox="0 0 512 512" width="512px"><g><path d="m181 356.391v34.609h-60v60h60v61h60v-61h60v-60h-60v-34.609c60.07-13.678 105-67.18 105-131.391 0-26.389-7.87-50.817-20.958-71.614l50.958-50.959v17.573h60v-120h-120v60h17.573l-50.958 50.958c-20.798-13.088-45.226-20.958-71.615-20.958-74.559 0-135 60.441-135 135 0 64.211 44.93 117.713 105 131.391zm30-206.391c41.353 0 75 33.647 75 75s-33.647 75-75 75-75-33.647-75-75 33.647-75 75-75z" data-original="#000000" class="active-path" fill=""/></g> </svg> -->
                                   <svg class="w-3 h-3 fill-current text-gray-800" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 480 480" style="enable-background:new 0 0 480 480;" xml:space="preserve">
<g>
    <g>
        <path d="M455.944,0h-56v16h36.688l-48.144,48.144c-52.795-46.786-133.521-41.915-180.307,10.879
            c-14.589,16.462-24.679,36.414-29.293,57.921c-67.942-19.285-138.654,20.159-157.939,88.101s20.159,138.654,88.101,157.939
            c8.775,2.491,17.791,4.036,26.895,4.608V424h-40v16h40v40h16v-40h40v-16h-40v-40.408c57.099-3.622,104.877-44.661,117.072-100.56
            c67.84,19.42,138.577-19.833,157.997-87.672c12.021-41.992,1.757-87.215-27.213-119.904l48.144-48.144V64h16V8
            C463.944,3.582,460.362,0,455.944,0z M143.944,368c-61.856,0.044-112.036-50.064-112.08-111.92S81.929,144.044,143.785,144
            c11.09-0.008,22.119,1.631,32.727,4.864c-0.32,3.68-0.568,7.376-0.568,11.136c0.058,51.285,30.677,97.6,77.84,117.744
            C243.348,330.155,197.384,367.924,143.944,368z M255.696,260.92c-38.901-18.569-63.692-57.815-63.752-100.92
            c0-1.664,0.176-3.28,0.248-4.92c38.901,18.569,63.692,57.815,63.752,100.92C255.944,257.664,255.768,259.28,255.696,260.92z
             M413.842,181.744c-10.383,52.466-56.414,90.27-109.898,90.256c-11.037-0.001-22.012-1.64-32.568-4.864
            c0.32-3.68,0.568-7.376,0.568-11.136c-0.058-51.285-30.677-97.6-77.84-117.744c12.009-60.679,70.934-100.134,131.613-88.125
            C386.396,62.14,425.851,121.065,413.842,181.744z"></path>
    </g>
</g></svg>
                                    <span class="mx-2 text-gray-700 font-medium">Gender :</span>
                                </div>
                                <div class="">
                                    <p class="capitalize">{{ optional($user->userprofile)->gender }}</p>
                                </div>
                            </li>
                            <li class="flex py-1">
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 fill-current text-gray-800" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><path d="M411.631,190.282h-111.72c-9.899,0-17.923,8.023-17.923,17.923s8.023,17.923,17.923,17.923h111.72 c9.905,0,17.923-8.023,17.923-17.923S421.531,190.282,411.631,190.282z"/></g></g><g><g><path d="M411.631,285.872h-111.72c-9.899,0-17.923,8.023-17.923,17.923s8.023,17.923,17.923,17.923h111.72 c9.905,0,17.923-8.024,17.923-17.923S421.531,285.872,411.631,285.872z"/></g></g><g><g><path d="M494.077,55.263H17.923C8.023,55.263,0,63.286,0,73.185v365.629c0,9.899,8.023,17.923,17.923,17.923h476.154 c9.899,0,17.923-8.023,17.923-17.923V73.185C512,63.286,503.977,55.263,494.077,55.263z M476.154,420.891H35.846V91.109h440.308 V420.891z"/></g></g><g><g><path d="M155.93,143.683c-28.659,0-51.977,23.318-51.977,51.977s23.318,51.977,51.977,51.977 c28.659,0,51.977-23.318,51.977-51.977S184.589,143.683,155.93,143.683z M155.93,211.79c-8.896,0-16.131-7.235-16.131-16.131 c0-8.896,7.235-16.131,16.131-16.131c8.896,0,16.131,7.235,16.131,16.131C172.061,204.555,164.826,211.79,155.93,211.79z"/></g></g><g><g><path d="M155.93,248.837c-46.779,0-84.835,38.056-84.835,84.83v16.728c0,9.899,8.023,17.923,17.923,17.923h133.825 c9.899,0,17.923-8.018,17.923-17.917v-16.734C240.766,286.893,202.709,248.837,155.93,248.837z M106.952,332.471 c0.633-26.46,22.362-47.795,48.978-47.795c26.61,0,48.338,21.328,48.972,47.795H106.952z"/></g></g></svg>
                                    <span class="mx-2 text-gray-700 font-medium whitespace-no-wrap">Aadhar Number :</span>
                                </div>
                                <div class="">
                                    <p class="capitalize">
                                        @if(optional($user->userprofile)->aadhar_number != '')
                                            {{ optional($user->userprofile)->aadhar_number }}
                                        @else
                                            --
                                        @endif
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="my-4 px-2">
                        <p class="capitalize text-gray-600 text-sm">contact information :</p>
                        <ul class="list-reset text-xs leading-loose my-2">
                            <li class="flex items-center py-1">
                                <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 368.16 368.16" xml:space="preserve" class="w-4 h-4 fill-current text-gray-800"><g><g><g><path d="M184.08,0c-74.992,0-136,61.008-136,136c0,24.688,11.072,51.24,11.536,52.36c3.576,8.488,10.632,21.672,15.72,29.4
        l93.248,141.288c3.816,5.792,9.464,9.112,15.496,9.112s11.68-3.32,15.496-9.104l93.256-141.296
        c5.096-7.728,12.144-20.912,15.72-29.4c0.464-1.112,11.528-27.664,11.528-52.36C320.08,61.008,259.072,0,184.08,0z
         M293.8,182.152c-3.192,7.608-9.76,19.872-14.328,26.8l-93.256,141.296c-1.84,2.792-2.424,2.792-4.264,0L88.696,208.952
        c-4.568-6.928-11.136-19.2-14.328-26.808C74.232,181.816,64.08,157.376,64.08,136c0-66.168,53.832-120,120-120
        c66.168,0,120,53.832,120,120C304.08,157.408,293.904,181.912,293.8,182.152z"></path> <path d="M184.08,64.008c-39.704,0-72,32.304-72,72c0,39.696,32.296,72,72,72c39.704,0,72-32.304,72-72
        C256.08,96.312,223.784,64.008,184.08,64.008z M184.08,192.008c-30.872,0-56-25.12-56-56s25.128-56,56-56s56,25.12,56,56
        S214.952,192.008,184.08,192.008z"></path></g></g></g></svg>
                                <div class="w-full mx-2">
                                    <p class="text-gray-700 leading-normal">
                                        @if($user->userprofile->address==null)
                                            --
                                        @else
                                            {{ optional($user->userprofile)->address }}
                                        @endif
                                    </p>
                                </div>
                            </li>
                            <li class="flex py-1 items-center">
                                <svg id="Layer_1" enable-background="new 0 0 512.021 512.021" height="512" viewBox="0 0 512.021 512.021" width="512" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current text-gray-800"><g><path d="m367.988 512.021c-16.528 0-32.916-2.922-48.941-8.744-70.598-25.646-136.128-67.416-189.508-120.795s-95.15-118.91-120.795-189.508c-8.241-22.688-10.673-46.108-7.226-69.612 3.229-22.016 11.757-43.389 24.663-61.809 12.963-18.501 30.245-33.889 49.977-44.5 21.042-11.315 44.009-17.053 68.265-17.053 7.544 0 14.064 5.271 15.645 12.647l25.114 117.199c1.137 5.307-.494 10.829-4.331 14.667l-42.913 42.912c40.482 80.486 106.17 146.174 186.656 186.656l42.912-42.913c3.837-3.837 9.36-5.466 14.667-4.331l117.199 25.114c7.377 1.581 12.647 8.101 12.647 15.645 0 24.256-5.738 47.224-17.054 68.266-10.611 19.732-25.999 37.014-44.5 49.977-18.419 12.906-39.792 21.434-61.809 24.663-6.899 1.013-13.797 1.518-20.668 1.519zm-236.349-479.321c-31.995 3.532-60.393 20.302-79.251 47.217-21.206 30.265-26.151 67.49-13.567 102.132 49.304 135.726 155.425 241.847 291.151 291.151 34.641 12.584 71.867 7.64 102.132-13.567 26.915-18.858 43.685-47.256 47.217-79.251l-95.341-20.43-44.816 44.816c-4.769 4.769-12.015 6.036-18.117 3.168-95.19-44.72-172.242-121.772-216.962-216.962-2.867-6.103-1.601-13.349 3.168-18.117l44.816-44.816z"></path><path d="m496.02 272c-8.836 0-16-7.164-16-16 0-123.514-100.486-224-224-224-8.836 0-16-7.164-16-16s7.164-16 16-16c68.381 0 132.668 26.628 181.02 74.98s74.98 112.639 74.98 181.02c0 8.836-7.163 16-16 16z"></path><path d="m432.02 272c-8.836 0-16-7.164-16-16 0-88.224-71.776-160-160-160-8.836 0-16-7.164-16-16s7.164-16 16-16c105.869 0 192 86.131 192 192 0 8.836-7.163 16-16 16z"></path><path d="m368.02 272c-8.836 0-16-7.164-16-16 0-52.935-43.065-96-96-96-8.836 0-16-7.164-16-16s7.164-16 16-16c70.58 0 128 57.42 128 128 0 8.836-7.163 16-16 16z"></path></g></svg>
                                <div class="w-full mx-2">
                                    <a href="#" class="blue-text">
                                        @if($user->mobile_no != null)
                                            {{ $user->mobile_no }}
                                        @else
                                            --
                                        @endif
                                    </a>
                                </div>
                            </li>
                            <li class="flex py-1 items-center">
                                <svg class="w-4 h-4 fill-current text-gray-800" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
    <g>
        <path d="M469.333,64H42.667C19.135,64,0,83.135,0,106.667v298.667C0,428.865,19.135,448,42.667,448h426.667
            C492.865,448,512,428.865,512,405.333V106.667C512,83.135,492.865,64,469.333,64z M42.667,85.333h426.667
            c1.572,0,2.957,0.573,4.432,0.897c-36.939,33.807-159.423,145.859-202.286,184.478c-3.354,3.021-8.76,6.625-15.479,6.625
            s-12.125-3.604-15.49-6.635C197.652,232.085,75.161,120.027,38.228,86.232C39.706,85.908,41.094,85.333,42.667,85.333z
             M21.333,405.333V106.667c0-2.09,0.63-3.986,1.194-5.896c28.272,25.876,113.736,104.06,169.152,154.453
            C136.443,302.671,50.957,383.719,22.46,410.893C21.957,409.079,21.333,407.305,21.333,405.333z M469.333,426.667H42.667
            c-1.704,0-3.219-0.594-4.81-0.974c29.447-28.072,115.477-109.586,169.742-156.009c7.074,6.417,13.536,12.268,18.63,16.858
            c8.792,7.938,19.083,12.125,29.771,12.125s20.979-4.188,29.76-12.115c5.096-4.592,11.563-10.448,18.641-16.868
            c54.268,46.418,140.286,127.926,169.742,156.009C472.552,426.073,471.039,426.667,469.333,426.667z M490.667,405.333
            c0,1.971-0.624,3.746-1.126,5.56c-28.508-27.188-113.984-108.227-169.219-155.668c55.418-50.393,140.869-128.57,169.151-154.456
            c0.564,1.91,1.194,3.807,1.194,5.897V405.333z"></path>
    </g>
</g> </svg>
                                <div class="w-full mx-2">
                                    @if($user->email == '')
                                        --
                                    @else
                                        <a href="#" class="blue-text">{{ $user->email }}</a>
                                    @endif
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- end -->
            </div>
            <div class="w-full xl:w-4/5 lg:3/4 md:w-full lg:mx-8 md:mx-0">
                <div class="flex items-center lg:justify-between flex-row lg:flex-row relative">

                    <h3 class="font-semibold text-3xl text-gray-700">{{ ucwords($user->FullName) }}</h3>
                    <div onclick="showsidebar('teacher-profile-menu')" class="bg-white rounded-full w-6 h-6 ml-auto flex items-center justify-center cursor-pointer">
                     <svg id="Capa_1" enable-background="new 0 0 515.555 515.555" height="512" viewBox="0 0 515.555 515.555" width="512" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current text-gray-600"><path d="m303.347 18.875c25.167 25.167 25.167 65.971 0 91.138s-65.971 25.167-91.138 0-25.167-65.971 0-91.138c25.166-25.167 65.97-25.167 91.138 0"></path><path d="m303.347 212.209c25.167 25.167 25.167 65.971 0 91.138s-65.971 25.167-91.138 0-25.167-65.971 0-91.138c25.166-25.167 65.97-25.167 91.138 0"></path><path d="m303.347 405.541c25.167 25.167 25.167 65.971 0 91.138s-65.971 25.167-91.138 0-25.167-65.971 0-91.138c25.166-25.167 65.97-25.167 91.138 0"></path></svg>
                     </div>
                     <div id="teacher-profile-menu" class="hidden absolute top-0 right-0 bg-white shadow mt-10 rounded">
                    <div class="flex flex-col text-xs w-40 my-1">
                    
                        @if(optional($user->userprofile)->status == "inactive")
                            <a href="#" rel="{{ url('/admin/user/updateStatus/'.$user->name) }}" class="capitalize  text-teal-500  px-4 py-1 font-medium activate my-1 lg:my-0 md:my-0 mr-2" value="active" id="status">Activate</a>
                        @else

                            <a href="#" rel="{{ url('/admin/user/updateStatus/'.$user->name) }}" class="capitalize text-red-500  px-4 py-1  font-medium activate my-1 lg:my-0 md:my-0 mr-2" value="inactive" id="status">Deactivate</a>

                        @endif

                          @if(optional($user)->status != "exit")
                            <a href="#" rel="{{ url('/admin/user/updateStatus/'.$user->name) }} " class="capitalize text-teal-500 rounded px-4 py-1 font-medium activate my-1 lg:my-0 md:my-0 mr-2 " value="exit" id="status">Exit</a>
                        @endif
                    
                        
                    </div>
                    </div>
                </div>
                <div class="leading-relaxed">
                    <p class="text-lg text-gray-700 font-semibold">ID: {{ $user->id }}</p>
                    <change-credential url="{{url('/')}}" name="{{$user->name}}"  ></change-credential>
                </div>
                <div class="bg-white shadow my-5">
                    <profile-tab-teacher url="{{url('/')}}"  entity_id="{{ $user->id }}" school_id="{{ $user->school_id }}" name="{{$user->name}}" mode="staff"></profile-tab-teacher>
                   
                    <portal-target name="teacherprofile"></portal-target>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')

<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $('.activate').on('click', function(){
            var link = $(this).attr('rel');
            var status = $(this).attr('value');
            //alert(status);
            swal({
                icon: "info",
                text: "Do you want to change the status ?",
                buttons: {
                    cancel: true,
                    confirm: true,
                },
                allowOutsideClick: false,
            }).then((willChange) => {
                if (willChange) 
                {
                    $.ajax({
                        url: link,
                        data: { status: status },
                        type: "POST",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success:function(data)
                        {
                            //alert(ans);
                            swal({
                                icon: "success",
                                text: "Teacher Status Updated Successfully",
                            }).then(function(){
                                window.location.reload();
                            });
                        }
                    })
                } 
                else 
                {
                    swal("Cancelled");
                } 
            });
        });
    });


    $(document).ready(function(){
        $('.reset').on('click', function(){
            var link = $(this).attr('rel');
            //alert(link);
            swal({
                icon: "info",
                text: "Do you want to reset password for this teacher ?",
                buttons: {
                    cancel: true,
                    confirm: true,
                },
                allowOutsideClick: false,
            }).then((willChange) => {
                if (willChange) 
                {
                    $.ajax({
                        url: link,
                        type: "GET",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success:function(data)
                        {
                            //alert(ans);
                            swal({
                                icon: "success",
                                text: "Check your email to reset the password",
                                showConfirmButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                            }).then(function(){
                                window.location.reload();
                            });
                        }
                    })
                }
                else 
                {
                    swal("Cancelled");
                } 
            });
        });
    });

    $(document).ready(function(){
        $('.verify').on('click', function(){
            var link = $(this).attr('rel');
            //alert(link);
            swal({
                icon: "info",
                text: "Do you want to verify email for this teacher ?",
                buttons: {
                    cancel: true,
                    confirm: true,
                },
                allowOutsideClick: false,
            }).then((willChange) => {
                if (willChange) 
                {
                    $.ajax({
                        url: link,
                        type: "GET",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success:function(data)
                        {
                            //alert(ans);
                            swal({
                                icon: "success",
                                text: "Verification code sent Successfully",
                                showConfirmButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                            }).then(function(){
                                window.location.reload();
                            });
                        }
                    })
                }
                else 
                {
                    swal("Cancelled");
                } 
            });
        });
    });

    $(document).ready(function(){
        $('.exit-member').on('click', function(){
            var link = $(this).attr('rel');
            var name = {!! json_encode($user->name) !!};
            //alert(link);
            swal({
                icon: "info",
                text: "Do you want to exit this member ?",
                buttons: {
                    cancel: true,
                    confirm: true,
                },
                allowOutsideClick: false,
            }).then((willChange) => {
                if (willChange) 
                {
                    $.ajax({
                        url: link,
                        type: "GET",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success:function(data)
                        {
                            //alert(name);
                            window.location.href="/admin/teacher/exit/"+name;
                        }
                    })
                }
                else 
                {
                    swal("Cancelled");
                } 
            });
        });
    });
</script>

@endpush