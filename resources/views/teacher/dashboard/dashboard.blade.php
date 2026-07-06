{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--teacher">
        <div class="ds-page-head">
            <h1 class="ds-page-head-title">Teacher</h1>
            <p class="ds-page-head-sub">Stay on top of notice updates, subjects, exams, and day-to-day class rhythm.</p>
        </div>

        <!-- Teaching KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white custom-shadow px-4 py-3 border dashboard-kpi-card text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto text-green-500" style="background: rgba(34,197,94,0.10);">
                    <i class="fa-solid fa-user-graduate text-2xl"></i>
                </div>
                <p class="text-xl font-semibold text-gray-800 dashboard-kpi-value mt-2">{{ $dashboard['myStudents'] }}</p>
                <p class="text-xs item-title dashboard-kpi-label">My Students</p>
            </div>
            <div class="bg-white custom-shadow px-4 py-3 border dashboard-kpi-card text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto text-blue-500" style="background: rgba(30,111,217,0.10);">
                    <i class="fa-solid fa-door-open text-2xl"></i>
                </div>
                <p class="text-xl font-semibold text-gray-800 dashboard-kpi-value mt-2">{{ $dashboard['myClasses'] }}</p>
                <p class="text-xs item-title dashboard-kpi-label">My Classes</p>
            </div>
            <div class="bg-white custom-shadow px-4 py-3 border dashboard-kpi-card text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto text-amber-500" style="background: rgba(217,119,6,0.10);">
                    <i class="fa-solid fa-calendar-check text-2xl"></i>
                </div>
                <p class="text-xl font-semibold text-gray-800 dashboard-kpi-value mt-2">{{ count($dashboard['upcomingExam']) }}</p>
                <p class="text-xs item-title dashboard-kpi-label">Upcoming Exams</p>
            </div>
            <div class="bg-white custom-shadow px-4 py-3 border dashboard-kpi-card text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto text-green-600" style="background: rgba(34,197,94,0.10);">
                    <i class="fa-brands fa-whatsapp text-2xl"></i>
                </div>
                <p class="text-xl font-semibold text-gray-800 dashboard-kpi-value mt-2">{{ $dashboard['whatsapp']['totalLinked'] }}</p>
                <p class="text-xs item-title dashboard-kpi-label">WhatsApp Linked</p>
            </div>
        </div>

        @if(($dashboard['myStudents'] ?? 0) === 0)
        <div style="background: #FFF7ED; border: 1px solid #FED7AA; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; font-size: 14px; color: #9A3412;">
            You haven't been assigned any classes yet. Contact your school admin to set up your class assignments before students and schedules appear here.
        </div>
        @endif

        <!-- start -->
        <div class="flex flex-col lg:flex-row my-2 dashboard-topfold dashboard-topfold--teacher">
            <div class="w-full my-2">
                <div class="flex flex-col lg:flex-row my-2">
                    <div class="w-full lg:w-1/2 my-3 lg:my-0 md:my-0 px-1">
                        <div class="bg-white custom-shadow px-3 py-2 border dashboard-panel-card">
                            <div>
                                <h1 class="dashboard-panel-title border-b mx-2 py-1 pb-3">Notice Board</h1>
                            </div>
                            <div class="notice-box">
                                @if(count($dashboard['noticeboard']) > 0)
                                    @foreach($dashboard['noticeboard'] as $noticeboard)
                                        <div class="notice-box-list py-3 mx-3 border-b">
                                            <div class="bg-green-600 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                                <p>{{ $noticeboard->title }}</p>
                                            </div>
                                            <div class="text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2" style="background:#1E6FD9;">
                                                <p>{{ date('d M Y',strtotime($noticeboard->publish_date)) }}</p>
                                            </div>
                                            <div class="text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2" style="background:#D97706;">
                                                <p>{{ ucwords($noticeboard->type) }}</p>
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
                                                            <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve" class="w-5 h-5 fill-current mr-1 text-gray-600"><g><g><path d="M472,313v139c0,11.028-8.972,20-20,20H60c-11.028,0-20-8.972-20-20V313H0v139c0,33.084,26.916,60,60,60h392 c33.084,0,60-26.916,60-60V313H472z"></path></g></g> <g><g><polygon points="352,235.716 276,311.716 276,0 236,0 236,311.716 160,235.716 131.716,264 256,388.284 380.284,264"></polygon></g></g></svg>
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="notice-box-list py-3 mx-3 border-b">
                                        <p class="text-sm text-gray-400 text-center">No notices available</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- start -->
                    <div class="w-full lg:w-1/2 my-3 lg:my-0 md:my-2 px-1">
                        <div class="bg-white custom-shadow px-3 py-2 border dashboard-panel-card">
                            <div>
                                <h1 class="dashboard-panel-title border-b mx-2 py-1 pb-3">Subjects</h1>
                            </div>
                            <div class="notice-box flex flex-wrap lg:flex-row items-center">
                                @foreach($dashboard['subject'] as $subject)
                                    <div class="w-1/3 lg:w-1/4 px-2 my-5 h-56 subject-box">
                                        <div class="bg-purple-300 px-2 py-3 rounded h-full flex flex-col justify-between">
                                            <div class="w-16 h-16 lg:w-20 lg:h-20 md:w-20 md:h-20 rounded-full ml-auto bg-purple-400 flex items-center p-1 my-2 mx-1">
                                                <svg class="w-10 h-10 mx-auto" height="511pt" viewBox="-30 1 511 511.9995" width="511pt" xmlns="http://www.w3.org/2000/svg"><path d="m261.652344 423.269531-51.871094-95.089843v-91.179688h-150v91.179688l-51.871094 95.089843c-21.785156 39.953125 7.078125 88.730469 52.671875 88.730469h148.398438c45.515625 0 74.496093-48.703125 52.671875-88.730469zm0 0" fill="#dcf1ff"/><path d="m261.652344 423.269531c-31.222656-57.234375-23.464844-43.011719-51.871094-95.089843v-91.179688h-75.128906v275h74.328125c45.515625 0 74.496093-48.703125 52.671875-88.730469zm0 0" fill="#bfe4fe"/><path d="m214.78125 242h-160c-8.285156 0-15-6.714844-15-15s6.714844-15 15-15h160c8.285156 0 15 6.714844 15 15s-6.714844 15-15 15zm0 0" fill="#dcf1ff"/><path d="m229.78125 227c0 8.28125-6.71875 15-15 15h-80.128906v-30h80.128906c8.28125 0 15 6.71875 15 15zm0 0" fill="#bfe4fe"/><path d="m428.8125 162.789062-141.460938-141.460937c-.457031.566406-105.507812 105.585937-106.019531 106.101563l141.417969 141.417968c29.289062 29.3125 76.757812 29.324219 106.0625 0 29.304688-29.285156 29.320312-76.757812 0-106.058594zm0 0" fill="#dcf1ff"/><path d="m428.8125 162.789062-141.460938-141.460937c-.457031.570313-52.636718 52.707031-53.152343 53.222656l194.453125 194.449219c.046875-.039062.109375-.101562.160156-.152344 29.304688-29.285156 29.320312-76.757812 0-106.058594zm0 0" fill="#bfe4fe"/><path d="m163.648438 130.964844c-5.859376-5.855469-5.859376-15.355469 0-21.210938l105.359374-105.359375c5.855469-5.859375 15.355469-5.859375 21.210938 0 5.859375 5.855469 5.859375 15.355469 0 21.210938l-105.355469 105.359375c-5.859375 5.859375-15.359375 5.859375-21.214843 0zm0 0" fill="#dcf1ff"/><path d="m290.21875 25.609375-52.476562 52.480469-21.210938-21.210938 52.480469-52.488281c5.847656-5.851563 15.347656-5.851563 21.207031 0 5.863281 5.859375 5.855469 15.371094 0 21.21875zm0 0" fill="#bfe4fe"/><path d="m236.410156 377h-203.261718l-25.238282 46.269531c-21.796875 39.960938 7.089844 88.730469 52.671875 88.730469h148.394531c45.515626 0 74.5-48.710938 52.671876-88.730469zm0 0" fill="#e33754"/><path d="m208.980469 512h-74.328125v-135h101.757812l25.242188 46.269531c21.785156 39.960938-7.085938 88.730469-52.671875 88.730469zm0 0" fill="#d1002d"/><path d="m428.8125 162.785156-26.785156-26.785156h-212.132813l132.851563 132.851562c29.3125 29.308594 76.753906 29.3125 106.066406 0s29.316406-76.753906 0-106.066406zm0 0" fill="#f6d401"/><path d="m428.8125 268.847656c-.050781.050782-.113281.113282-.160156.152344l-133-133h106.378906l26.78125 26.789062c29.304688 29.289063 29.320312 76.757813 0 106.058594zm0 0" fill="#fd9d34"/><path d="m150.152344 167c0 8.285156-6.71875 15-15 15-8.285156 0-15-6.714844-15-15s6.714844-15 15-15c8.28125 0 15 6.714844 15 15zm0 0" fill="#f6993c"/><path d="m150.152344 167c0 8.902344-7.707032 15.476562-15.5 14.988281v-29.980469c7.761718-.484374 15.5 6.058594 15.5 14.992188zm0 0" fill="#ec5b20"/><path d="m120.152344 107c0 8.285156-6.71875 15-15 15-8.285156 0-15-6.714844-15-15s6.714844-15 15-15c8.28125 0 15 6.714844 15 15zm0 0" fill="#2aeed0"/><path d="m120.152344 107c0 8.902344-7.707032 15.476562-15.5 14.988281v-29.980469c7.761718-.484374 15.5 6.058594 15.5 14.992188zm0 0" fill="#1bdeab"/></svg>
                                            </div>
                                            <div class="pt-5 lg:px-2 md:px-2">
                                                <p class="text-white text-base lg:text-lg md:text-lg font-bold">{{ $subject['subject'] }}</p>
                                                <p class="text-white text-sm font-semibold">{{ $subject['class'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <!-- end -->
                </div>
            </div>
        </div>
        <!-- end -->

        <!-- start -->
        <div class="flex flex-wrap lg:flex-row md:flex-row">


            <!-- start -->
            <div class="w-full lg:w-1/4  md:w-1/2 my-3 lg:my-0 md:my-2 px-1">
                <div class="bg-white px-3 py-2 h-full dashboard-panel-card">
                    <div class="border-b mx-2 py-1 pb-3">
                        <h1 class="text-gray-800 font-semibold text-lg">Activity Log</h1>
                    </div>
                    <div class="event-box">
                        <div class="notice-box-list py-3  mx-2">
                            <!-- ****** -->
                            @foreach($dashboard['activitylog'] as $activitylog)
                                <div class="w-full border-l-2 border-purple-500 relative my-1">
                                    <div class="border py-2 px-2 activity-log ">
                                        <div class="">
                                            <div class="px-3">
                                                <p class="text-gray-800 text-sm font-semibold">{{ $activitylog->created_at->diffForHumans() }}</p>
                                                <p class="text-gray-500 text-xs">{{ $activitylog->description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <!-- ***** -->

                            <!-- ****** -->
                            <!-- ***** -->

                            <!-- ****** -->
                            <!-- ***** -->

                            <!-- ****** -->
                            <!-- ***** -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- end -->

            <!-- start -->
            @if(config('gexam.enabled', false))
            <div class="w-full lg:w-2/4 md:w-full px-1">
                <div class="bg-white custom-shadow py-1 border h-full dashboard-panel-card">
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
                                            <tr class="border-t font-semibold">
                                                <td class="px-3 py-2">{{ $upcomingExam->exam->name }}</td>
                                                <td class="px-3 py-2">{{ $upcomingExam->subject->name }}</td>
                                                <td class="px-3 py-2">{{ $upcomingExam->standardLink->StandardSection }}</td>
                                                <td class="px-3 py-2">{{ date('H:i A',strtotime($upcomingExam->start_time)) }} - {{ date('H:i A',strtotime($upcomingExam->duration)) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="4">
                                            <p class="bg-gray-100 px-4 py-2 border-t border-b text-base font-semibold text-gray-700" style="text-align: center;">No Records Found</p>
                                        </td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            @endif
            <!-- end -->
        </div>
        <!-- end -->

        <!-- start -->
        <div class="flex flex-wrap">
            <!--Task Module-->
            <div class="w-full lg:w-1/2 md:w-1/2 px-1">
                <div class="bg-white custom-shadow px-3 py-2 border dashboard-panel-card h-full">
                    <dashboard-task url="{{ url('/') }}" mode="teacher"></dashboard-task>
                </div>
            </div>

            @if(config('gtimetable.enabled', false))
             <div class="w-full lg:w-1/2 md:w-1/2 my-3 lg:my-0 md:my-2 px-1">
                <div class="bg-white custom-shadow px-3 py-2 border dashboard-panel-card">
                    <div class="mx-2 py-1">
                        <!-- <p class="text-sm text-gray-500">Monday, June</p>
                        <h1 class="text-gray-800 font-semibold text-3xl ">08</h1> -->
                        <h1 class="text-gray-800 font-semibold text-lg">TimeTable</h1>
                    </div>

                    <div class="px-2 pb-2">
                        @if(count($dashboard['timetable'] ?? []) > 0)
                            @foreach($dashboard['timetable'] as $standard => $entries)
                                @foreach($entries as $entry)
                                    <div class="flex items-center gap-3 px-3 py-2 my-1 rounded bg-purple-500 text-white">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold">{{ $entry['subject'] ?? '—' }}</p>
                                            <p class="text-xs opacity-80">{{ $standard }}</p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-xs font-medium">{{ $entry['start_time'] ?? '—' }} - {{ $entry['end_time'] ?? '—' }}</p>
                                            <p class="text-xs opacity-70">{{ $entry['period'] ?? '' }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        @else
                            <p class="text-sm text-gray-400 text-center py-6">No timetable entries for today.</p>
                        @endif
                    </div>
                                <div class="my-1">
                                    <svg class="w-4 h-4 fill-current text-white" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="124.813px" height="124.813px" viewBox="0 0 124.813 124.813" style="enable-background:new 0 0 124.813 124.813;" xml:space="preserve"><g><g><path d="M48.083,80.355l-1.915,11.374c-0.261,1.555,0.377,3.122,1.65,4.05c1.275,0.926,2.968,1.05,4.361,0.32l10.226-5.338 L72.631,96.1c0.605,0.314,1.268,0.472,1.924,0.472c0.859,0,1.716-0.269,2.439-0.792c1.274-0.928,1.914-2.495,1.651-4.05 l-1.913-11.374l8.234-8.077c1.126-1.103,1.527-2.749,1.044-4.247c-0.485-1.497-1.783-2.593-3.341-2.823l-11.41-1.692 l-5.139-10.329c-0.697-1.41-2.141-2.303-3.716-2.303c-1.572,0-3.015,0.893-3.718,2.303l-5.134,10.329l-11.41,1.691 c-1.561,0.23-2.853,1.326-3.339,2.823c-0.486,1.498-0.086,3.146,1.042,4.247L48.083,80.355z"/><path d="M111.443,13.269H98.378V6.022C98.378,2.696,95.682,0,92.355,0H91.4c-3.326,0-6.021,2.696-6.021,6.022v7.247H39.282V6.022 C39.282,2.696,36.586,0,33.261,0h-0.956c-3.326,0-6.021,2.696-6.021,6.022v7.247H13.371c-6.833,0-12.394,5.559-12.394,12.394 v86.757c0,6.831,5.561,12.394,12.394,12.394h98.073c6.832,0,12.394-5.562,12.394-12.394V25.663 C123.837,18.828,118.275,13.269,111.443,13.269z M109.826,110.803H14.988V43.268h94.838V110.803z"/></g></g></svg>
                                </div>
                                <div class=" rounded px-2 w-full">
                                    <div class="px-3 text-white">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold">Mathematics</p>
                                            <p class="text-sm font-semibold">7-C</p>
                                        </div>
                                        <p class="text-xs">2.30 PM - 3.15 PM, HB1</p>
                                    </div>
                                </div>
                            </div> -->
                            <!-- ***** -->

                            <!-- ****** -->
                            <!-- <div class="flex items-start bg-purple-400 px-3 py-3 my-1">
                                <div class="my-1">
                                    <svg class="w-4 h-4 fill-current text-white" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="124.813px" height="124.813px" viewBox="0 0 124.813 124.813" style="enable-background:new 0 0 124.813 124.813;" xml:space="preserve"><g><g><path d="M48.083,80.355l-1.915,11.374c-0.261,1.555,0.377,3.122,1.65,4.05c1.275,0.926,2.968,1.05,4.361,0.32l10.226-5.338 L72.631,96.1c0.605,0.314,1.268,0.472,1.924,0.472c0.859,0,1.716-0.269,2.439-0.792c1.274-0.928,1.914-2.495,1.651-4.05 l-1.913-11.374l8.234-8.077c1.126-1.103,1.527-2.749,1.044-4.247c-0.485-1.497-1.783-2.593-3.341-2.823l-11.41-1.692 l-5.139-10.329c-0.697-1.41-2.141-2.303-3.716-2.303c-1.572,0-3.015,0.893-3.718,2.303l-5.134,10.329l-11.41,1.691 c-1.561,0.23-2.853,1.326-3.339,2.823c-0.486,1.498-0.086,3.146,1.042,4.247L48.083,80.355z"/><path d="M111.443,13.269H98.378V6.022C98.378,2.696,95.682,0,92.355,0H91.4c-3.326,0-6.021,2.696-6.021,6.022v7.247H39.282V6.022 C39.282,2.696,36.586,0,33.261,0h-0.956c-3.326,0-6.021,2.696-6.021,6.022v7.247H13.371c-6.833,0-12.394,5.559-12.394,12.394 v86.757c0,6.831,5.561,12.394,12.394,12.394h98.073c6.832,0,12.394-5.562,12.394-12.394V25.663 C123.837,18.828,118.275,13.269,111.443,13.269z M109.826,110.803H14.988V43.268h94.838V110.803z"/></g></g></svg>
                                </div>
                                <div class="rounded px-2 w-full">
                                    <div class="px-3 text-white">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold">English</p>
                                            <p class="text-sm font-semibold">6-A</p>
                                        </div>
                                        <p class="text-xs">3.15 PM - 4.00 PM, HB1</p>
                                    </div>
                                </div>
                            </div> -->
                            <!-- ***** -->

                            <!-- ****** -->
                            <!-- <div class="flex items-start bg-purple-400 px-3 py-3 my-1">
                                <div class="my-1">
                                    <svg class="w-4 h-4 fill-current text-white" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="124.813px" height="124.813px" viewBox="0 0 124.813 124.813" style="enable-background:new 0 0 124.813 124.813;" xml:space="preserve"><g><g><path d="M48.083,80.355l-1.915,11.374c-0.261,1.555,0.377,3.122,1.65,4.05c1.275,0.926,2.968,1.05,4.361,0.32l10.226-5.338 L72.631,96.1c0.605,0.314,1.268,0.472,1.924,0.472c0.859,0,1.716-0.269,2.439-0.792c1.274-0.928,1.914-2.495,1.651-4.05 l-1.913-11.374l8.234-8.077c1.126-1.103,1.527-2.749,1.044-4.247c-0.485-1.497-1.783-2.593-3.341-2.823l-11.41-1.692 l-5.139-10.329c-0.697-1.41-2.141-2.303-3.716-2.303c-1.572,0-3.015,0.893-3.718,2.303l-5.134,10.329l-11.41,1.691 c-1.561,0.23-2.853,1.326-3.339,2.823c-0.486,1.498-0.086,3.146,1.042,4.247L48.083,80.355z"/><path d="M111.443,13.269H98.378V6.022C98.378,2.696,95.682,0,92.355,0H91.4c-3.326,0-6.021,2.696-6.021,6.022v7.247H39.282V6.022 C39.282,2.696,36.586,0,33.261,0h-0.956c-3.326,0-6.021,2.696-6.021,6.022v7.247H13.371c-6.833,0-12.394,5.559-12.394,12.394 v86.757c0,6.831,5.561,12.394,12.394,12.394h98.073c6.832,0,12.394-5.562,12.394-12.394V25.663 C123.837,18.828,118.275,13.269,111.443,13.269z M109.826,110.803H14.988V43.268h94.838V110.803z"/></g></g></svg>
                                </div>
                                <div class=" rounded px-2 w-full">
                                    <div class="px-3 text-white">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold">Geography</p>
                                            <p class="text-sm font-semibold">8-B</p>
                                        </div>
                                        <p class="text-xs">4.00 PM - 4.45 PM, HB1</p>
                                    </div>
                                </div>
                            </div> -->
                            <!-- ***** -->
                        <!-- </div> -->
                    <!-- </div> -->
                </div>
            </div>
            @endif
            <!-- end -->
        </div>
        <!--end-->
    </div>
@endsection
