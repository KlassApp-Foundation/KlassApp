{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--admin">
        <div class="dashboard-heading">
            <div>
                <div class="dashboard-live-badge">
                    <span class="dashboard-live-dot"></span>
                    The Admin Command Center
                </div>
                <p class="dashboard-subtitle font-semibold">Live school activity, enrollment pulse, and updates in one place.</p>
            </div>
        </div>
        @include('partials.message')

        <div class="flex flex-wrap my-2 dashboard-topfold">
            <div class="w-full xl:w-1/3 lg:w-2/3 my-2">
                <div class="flex flex-wrap lg:flex-row">
                    <div class="w-full lg:w-1/2 md:w-1/2 px-1 my-1">
                        <a href="{{ url('/admin/students') }}">
                            <div class="bg-white custom-shadow px-4 py-3 border dashboard-kpi-card">
                                <div class="w-20 h-20 rounded-full bg-light-green flex items-center justify-center mx-auto text-green-500 dashboard-kpi-icon">
                                    <svg id="Capa_1" enable-background="new 0 0 511.996 511.996" height="512" viewBox="0 0 511.996 511.996" width="512" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 fill-current"><g><path d="m40.353 511.996h428.195c13.015 0 23.604-10.659 23.604-23.761v-12.053c0-6.067-4.921-11.004-10.97-11.004h-9.36c1.882-3.799 2.945-8.029 2.945-12.427v-156.499c0-15.596-12.688-28.285-28.284-28.285h-97.077c-4.143 0-7.5 3.358-7.5 7.5s3.357 7.5 7.5 7.5h97.077c7.325 0 13.284 5.959 13.284 13.285v156.499c0 5.502-3.5 10.48-8.623 12.427h-229.337c-5.29-2.026-8.636-7.106-8.636-12.427 0-15.342 0-141.27 0-156.499 0-7.325 5.96-13.285 13.285-13.285h87.95c4.143 0 7.5-3.358 7.5-7.5s-3.357-7.5-7.5-7.5h-55.218l-.016-19.279c17.179-12.574 29.077-31.932 31.736-54.099 19.515-1.711 34.036-17.268 34.036-35.4 0-12.417-6.299-23.904-16.102-30.579 1.042-2.496 1.622-5.231 1.622-8.101v-14.699c0-4.142-3.357-7.5-7.5-7.5s-7.5 3.358-7.5 7.5v14.701c0 3.26-2.599 6.156-6.375 6.094-10.322-.151-18.194-8.706-18.194-18.447 0-4.063-2.754-7.591-6.697-8.58-4.021-1.005-8.07.913-9.937 4.4-7.466 13.965-21.925 22.641-37.734 22.641h-85.865c-7.192 0-13.668-5.844-13.668-13.668v-55.177c0-23.586 19.188-42.774 42.774-42.774h75.654c17.569 0 20.542 7.759 22.232 16.404 1.999 10.223 11.572 17.311 22.287 16.487 7.525-.578 15.521 5.941 15.521 15.042v7.877c0 4.142 3.357 7.5 7.5 7.5s7.5-3.358 7.5-7.5v-7.877c0-18.073-15.671-31.228-31.672-29.998-3.174.252-5.868-1.611-6.416-4.41-3.753-19.194-15.84-28.525-36.953-28.525h-75.654c-31.856 0-57.774 25.917-57.774 57.774v55.176c0 7.854 3.177 14.979 8.311 20.162-7.256 6.847-11.698 16.362-11.698 26.911 0 10.857 4.293 20.663 11.311 27.75v54.018c0 13.653-2.177 26.87-6.195 39.162h-13.116c-13.015 0-23.604 10.659-23.604 23.761v12.052c0 6.068 4.921 11.005 10.97 11.005h9.359c-1.934 3.888-3.003 8.227-3.003 12.734v156.499c0 15.596 12.688 28.285 28.284 28.285h97.078c4.143 0 7.5-3.358 7.5-7.5s-3.357-7.5-7.5-7.5h-97.078c-7.325 0-13.284-5.959-13.284-13.285v-156.499c0-5.502 3.501-10.48 8.623-12.427h229.338c5.29 2.026 8.636 7.106 8.636 12.427 0 15.342 0 141.27 0 156.499 0 7.325-5.96 13.285-13.285 13.285h-87.95c-4.143 0-7.5 3.358-7.5 7.5s3.357 7.5 7.5 7.5h55.218l.017 19.279c-17.18 12.574-29.078 31.932-31.736 54.099-19.515 1.711-34.036 17.268-34.036 35.4 0 12.417 6.299 23.904 16.102 30.579-1.042 2.496-1.622 5.231-1.622 8.101v14.699c0 4.142 3.357 7.5 7.5 7.5s7.5-3.358 7.5-7.5v-14.701c0-3.26 2.599-6.156 6.375-6.094 10.322.151 18.194 8.706 18.194 18.447 0 4.063 2.754 7.591 6.697 8.58 4.021.992 8.069-.91 9.937-4.4 7.466-13.965 21.925-22.641 37.734-22.641h85.865c7.192 0 13.668 5.844 13.668 13.668v55.176c0 23.586-19.188 42.774-42.774 42.774h-75.654c-17.569 0-20.542-7.759-22.232-16.404-2-10.223-11.572-17.311-22.287-16.487-7.524.578-15.521-5.941-15.521-15.042v-7.877c0-4.142-3.357-7.5-7.5-7.5s-7.5 3.358-7.5 7.5v7.877c0 18.073 15.671 31.228 31.672 29.998 3.174-.252 5.868 1.611 6.416 4.41 3.753 19.194 15.84 28.525 36.953 28.525h75.654c31.856 0 57.774-25.917 57.774-57.774v-55.176c0-7.854-3.177-14.979-8.311-20.162 7.256-6.847 11.698-16.362 11.698-26.911 0-10.857-4.293-20.663-11.311-27.75v-54.018c0-13.653 2.177-26.87 6.195-39.162h13.116c13.015 0 23.604-10.659 23.604-23.761v-12.053c0-6.068-4.921-11.005-10.97-11.005h-9.359c1.934-3.888 3.003-8.227 3.003-12.734v-156.499c0-15.596-12.688-28.285-28.284-28.285h-97.078c-4.143 0-7.5 3.358-7.5 7.5s3.357 7.5 7.5 7.5h97.078c7.325 0 13.284 5.959 13.284 13.285v156.499c0 5.502-3.501 10.48-8.623 12.427z"/></g></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800 dashboard-kpi-value">{{ $dashboard['studentCount'] }}</p>
                                    <p class="text-base item-title dashboard-kpi-label">Students</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="w-full lg:w-1/2 md:w-1/2 px-1 my-1">
                        <a href="{{ url('/admin/teachers') }}">
                            <div class="bg-white custom-shadow px-4 py-3 border dashboard-kpi-card">
                                <div class="w-20 h-20 rounded-full bg-light-blue flex items-center justify-center mx-auto text-blue-500 dashboard-kpi-icon">
                                    <svg class="w-10 h-10 fill-current" id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m497 121h-30v-30c0-8.284-6.716-15-15-15s-15 6.716-15 15v30h-30c-8.284 0-15 6.716-15 15s6.716 15 15 15h30v30c0 8.284 6.716 15 15 15s15-6.716 15-15v-30h30c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m271 166h106c8.284 0 15-6.716 15-15s-6.716-15-15-15h-106c-19.555 0-35.441 15.886-35.441 35.441v85.559h-85.559c-19.555 0-35.441 15.886-35.441 35.441v85.559h-85.559c-19.555 0-35.441 15.886-35.441 35.441v60.118c0 8.284 6.716 15 15 15h151c8.284 0 15-6.716 15-15v-60.118c0-19.555-15.886-35.441-35.441-35.441h-85.559v-85.559c0-3.001 2.44-5.441 5.441-5.441h85.559c19.555 0 35.441-15.886 35.441-35.441v-85.559c0-3.001 2.44-5.441 5.441-5.441z"/></g></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800 dashboard-kpi-value">{{ $dashboard['teacherCount'] }}</p>
                                    <p class="text-base item-title dashboard-kpi-label">Teachers</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="w-full lg:w-1/2 md:w-1/2 px-1 my-1">
                        <a href="{{ url('/admin/parents') }}">
                            <div class="bg-white custom-shadow px-4 py-3 border dashboard-kpi-card">
                                <div class="w-20 h-20 rounded-full bg-light-amber flex items-center justify-center mx-auto text-amber-500 dashboard-kpi-icon">
                                    <svg class="w-10 h-10 fill-current" id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m490.052 221.047-75.097-75.097c-7.506-7.506-19.662-7.506-27.168 0l-75.097 75.097c-7.506 7.506-7.506 19.662 0 27.168l75.097 75.097c7.506 7.506 19.662 7.506 27.168 0l75.097-75.097c7.506-7.506 7.506-19.662 0-27.168zm-88.681 13.584-61.513-61.513 61.513-61.513 61.513 61.513z"/><path d="m256 0c-47.061 0-85.333 38.272-85.333 85.333s38.272 85.333 85.333 85.333 85.333-38.272 85.333-85.333-38.272-85.333-85.333-85.333zm0 128c-23.531 0-42.667-19.136-42.667-42.667s19.136-42.667 42.667-42.667 42.667 19.136 42.667 42.667-19.136 42.667-42.667 42.667z"/><path d="m341.333 256h-170.666c-47.061 0-85.333 38.272-85.333 85.333v85.333c0 47.061 38.272 85.333 85.333 85.333h170.666c47.061 0 85.333-38.272 85.333-85.333v-85.333c0-47.061-38.272-85.333-85.333-85.333zm42.667 170.667c0 23.531-19.136 42.667-42.667 42.667h-170.666c-23.531 0-42.667-19.136-42.667-42.667v-85.333c0-23.531 19.136-42.667 42.667-42.667h170.666c23.531 0 42.667 19.136 42.667 42.667z"/></g></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800 dashboard-kpi-value">{{ $dashboard['parentCount'] }}</p>
                                    <p class="text-base item-title dashboard-kpi-label">Parents</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="w-full lg:w-1/2 md:w-1/2 px-1 my-1">
                        <a href="{{ url('/admin/staffs') }}">
                            <div class="bg-white custom-shadow px-3 py-3 border dashboard-kpi-card">
                                <div class="w-20 h-20 rounded-full bg-light-red flex items-center justify-center mx-auto text-red-500 dashboard-kpi-icon">
                                    <svg class="w-10 h-10 fill-current" id="Outline" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><path d="m456 48h-48v-8a24 24 0 0 0 -48 0v8h-80v-8a24 24 0 0 0 -48 0v8h-80v-8a24 24 0 0 0 -48 0v8h-48a40.045 40.045 0 0 0 -40 40v368a40.045 40.045 0 0 0 40 40h400a40.045 40.045 0 0 0 40-40v-368a40.045 40.045 0 0 0 -40-40zm-80-8a8 8 0 0 1 16 0v48a8 8 0 0 1 -16 0zm-128 0a8 8 0 0 1 16 0v48a8 8 0 0 1 -16 0zm-128 0a8 8 0 0 1 16 0v48a8 8 0 0 1 -16 0zm-64 24h48v24a24 24 0 0 0 48 0v-24h80v24a24 24 0 0 0 48 0v-24h80v24a24 24 0 0 0 48 0v-24h48a24.028 24.028 0 0 1 24 24v56h-448v-56a24.028 24.028 0 0 1 24-24zm400 416h-400a24.028 24.028 0 0 1 -24-24v-296h448v296a24.028 24.028 0 0 1 -24 24z"/><path d="m440 216h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m280 376h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m200 216h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m120 296h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m200 296h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m120 376h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m200 376h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m369.208 248.983-40-6.112-17.966-38.271a8 8 0 0 0 -14.484 0l-17.958 38.271-40 6.112a8 8 0 0 0 -4.518 13.5l29.181 29.907-6.9 42.326a8 8 0 0 0 11.753 8.284l35.684-19.724 35.684 19.724a8 8 0 0 0 11.766-8.289l-6.9-42.326 29.181-29.907a8 8 0 0 0 -4.518-13.5zm-38.934 35.117a8 8 0 0 0 -2.17 6.875l4.9 30.051-25.136-13.9a8 8 0 0 0 -7.74 0l-25.136 13.9 4.9-30.051a8 8 0 0 0 -2.17-6.875l-21.122-21.652 28.833-4.4a8 8 0 0 0 6.033-4.509l12.534-26.711 12.535 26.706a8 8 0 0 0 6.033 4.504l28.834 4.4z"/></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800 dashboard-kpi-value">{{ $dashboard['nonteachingCount'] }}</p>
                                    <p class="text-base  item-title dashboard-kpi-label">Non Teaching Staff</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/3 px-1 my-3">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-chart-card">
                    <div>
                        <h1 class="text-gray-800 font-semibold text-xl dashboard-panel-title">Students</h1>
                    </div>
                    <canvas id="graph" class="dashboard-chart-canvas"></canvas>
                    <div class="flex items-center justify-between my-1">
                        <div class="border-r w-1/2 mt-4 bar-bg-blue relative student_count dashboard-gender-stat">
                            <a href="{{ url('/admin/students?gender=female') }}">
                                <p class="text-sm item-title font-semibold">Girls</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $dashboard['femaleCount'] }}</p>
                            </a>
                        </div>
                        <div class="w-1/2 text-right mt-4 bar-bg-orange relative student_count student_male_count dashboard-gender-stat">
                            <a href="{{ url('/admin/students?gender=male') }}" target="_blank">
                                <p class="text-sm item-title font-semibold ">Boys</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $dashboard['maleCount'] }}</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-full xl:w-1/3 lg:w-full md:w-1/3 px-1 my-3">
                <div class="bg-white custom-shadow px-3 py-2 border dashboard-notice-card">
                    <div>
                        <h1 class="text-gray-800 font-semibold text-lg border-b mx-2 py-1 pb-3 dashboard-panel-title">Notice Board</h1>
                    </div>
                    <div class="notice-box">
                        @if(count($dashboard['noticeboard']) > 0)
                            @foreach($dashboard['noticeboard'] as $noticeboard)
                                <div class="notice-box-list py-3 mx-3 border-b">
                                    <div class="bg-teal-500 text-x rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>{{ $noticeboard->title }}</p>
                                    </div>
                                    <div class="bg-purple-500 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>{{ date('d M Y',strtotime($noticeboard->publish_date)) }}</p>
                                    </div>
                                    <div class="bg-orange-500 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>{{ ucwords($noticeboard->type) }}</p>
                                    </div>
                                    <div class="my-1">
                                        <p class="text-sm text-gray-900 font-semibold">{!! $noticeboard->description !!}</p>
                                    </div>
                                    <div class="text-sm my-1">
                                        <p class="text-gray-500">
                                            <span class="text-gray-500">{{ $noticeboard->created_at->diffForHumans() }}</span>
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="my-4 text-center text-gray-400">No notices available</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row my-2">
            @if(config('gexam.enabled', false))
            <div class="w-full lg:w-2/3 px-1 my-2">
                <div class="bg-white custom-shadow py-1 border dashboard-panel-card">
                    <div>
                        <h1 class="text-gray-800 px-3 font-semibold text-xl py-2 pb-3">Upcoming Exams</h1>
                    </div>
                    <div class="exam-box mt-2 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Exam Name</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base w-40">Subject</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Class</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Time</th>
                                </tr>
                            </thead>
                            @if(count($dashboard['upcomingExam']) > 0)
                                <tbody>
                                    @foreach($dashboard['upcomingExam'] as $key => $upcomingExams)
                                        <tr>
                                            <td colspan="4">
                                                <p class="bg-gray-100 px-4 py-2 border-t border-b text-base font-semibold text-gray-700">{{ date('d-m-Y H:i:s',strtotime($key)) }}</p>
                                            </td>
                                        </tr>
                                        @foreach($upcomingExams as $upcomingExam)
                                            <tr>
                                                <td class="text-left px-3 pt-2 pb-3 text-base">{{ $upcomingExam->exam->name }}</td>
                                                <td class="text-left px-3 pt-2 pb-3 text-base">{{ $upcomingExam->subject->name }}</td>
                                                <td class="text-left px-3 pt-2 pb-3 text-base">{{ $upcomingExam->standardLink->StandardSection }}</td>
                                                <td class="text-left px-3 pt-2 pb-3 text-base">{{ date('H:i:s',strtotime($key)) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center py-6 text-gray-400">No upcoming exams</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="w-full lg:w-1/3 px-1 my-2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border dashboard-panel-card">
                    <div>
                        <h1 class="text-gray-800 px-3 font-semibold text-xl py-2 pb-3">Feedbacks</h1>
                    </div>
                    <div class="mt-2">
                        @if(count($dashboard['feedbacks']) != 0)
                            @foreach($dashboard['feedbacks'] as $feedback)
                                <div class="border-b pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                                    <div class="text-sm">
                                        <span class="font-semibold text-gray-800">{{ $feedback->parent->name ?? 'Parent' }}</span>
                                        <span class="text-gray-500"> - {{ $feedback->feedbackMessage->message ?? '' }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">{{ $feedback->created_at->diffForHumans() }}</p>
                                </div>
                            @endforeach
                        @else
                            <div class="my-4 text-center text-gray-400">No feedbacks available</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row my-2">
            <div class="w-full lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border dashboard-panel-card">
                    <div>
                        <h1 class="text-gray-800 px-3 font-semibold text-xl py-2 pb-3">Events</h1>
                    </div>
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Event Name</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Date</th>
                                </tr>
                            </thead>
                            @if(count($dashboard['events']) != 0)
                                <tbody>
                                    @foreach($dashboard['events'] as $event)
                                        <tr>
                                            <td class="text-left px-3 pt-2 pb-3 text-base">{{ $event->name }}</td>
                                            <td class="text-left px-3 pt-2 pb-3 text-base">{{ date('d M Y',strtotime($event->event_date)) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="text-center py-6 text-gray-400">No events available</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border dashboard-panel-card">
                    <div>
                        <h1 class="text-gray-800 px-3 font-semibold text-xl py-2 pb-3">Products</h1>
                    </div>
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Product</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Price</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Stock</th>
                                </tr>
                            </thead>
                            @if(count($dashboard['products']) > 0)
                                <tbody>
                                    @foreach($dashboard['products'] as $product)
                                        <tr>
                                            <td class="text-left px-3 pt-2 pb-3 text-base">{{ $product->name }}</td>
                                            <td class="text-left px-3 pt-2 pb-3 text-base">{{ $product->price }}</td>
                                            <td class="text-left px-3 pt-2 pb-3 text-base">{{ $product->stock }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="text-center py-6 text-gray-400">No products available</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row my-2">
            <div class="w-full xl:w-1/3 lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border h-full dashboard-panel-card">
                    <absentees-student url="{{ url('/') }}"></absentees-student>
                    <absentees-staff url="{{ url('/') }}"></absentees-staff>
                </div>
            </div>
            @if(count($dashboard['products']) > 0)
            <div class="w-full xl:w-2/3 lg:w-1/2 px-1 my-2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border h-full dashboard-panel-card">
                    <div>
                        <h1 class="text-gray-800 px-3 font-semibold text-xl py-2 pb-3">Latest Products</h1>
                    </div>
                    <div class="mt-2">
                        <table class="w-full text-sm">
                            @foreach($dashboard['products'] as $product)
                                <tr class="border-b">
                                    <td class="py-2 px-3 text-base font-semibold text-gray-800">{{ $product->name }}</td>
                                    <td class="py-2 px-3 text-base text-gray-600">{{ $product->price }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>
    <script>
        var ctx = document.getElementById('graph').getContext('2d');
        var femaleCount = {!! trans($dashboard['femaleCount']) !!};
        var maleCount = {!! trans($dashboard['maleCount']) !!};
        var chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ["Male Students", "Female Students"],
                datasets: [{
                    label: " Students",
                    backgroundColor: [
                        "#ffa601", "#304ffe"
                    ],
                    data: [maleCount,femaleCount],
                }]
            },
            options: {
                legend: {
                    display: false,
                },
                tooltips: {
                    enabled: true,
                    mode: 'index',
                    callbacks: {
                        label: function (tooltipItems, data) {
                            var i, label = [], l = data.datasets.length;
                            for (i = 0; i < l; i += 1) {
                                label[i] = data.datasets[i].label + ': ' + data.datasets[i].data[tooltipItems.index] + '%';
                            }
                            return label;
                        }
                    }
                }
            }
        });

        var ctx = document.getElementById("barChart").getContext('2d');
        var barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ["January", "February", "March", "April", "May", "June", "July"],
                datasets: [{
                    label: 'Term-1',
                    data: [12, 19, 3, 17, 28, 24, 7],
                    backgroundColor: "#dd5e89"
                }, {
                    label: 'Term-2',
                    data: [30, 29, 5, 5, 20, 3, 10],
                    backgroundColor: "#f7bb97"
                }]
            }
        });

        $(document).ready(function(){
        });
    </script>
@endpush
