{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--admin">
        <div class="dashboard-heading">
            <div>
                <h1 class="dashboard-section-title">Admin</h1>
                <p class="dashboard-subtitle">Live school activity, enrollment pulse, and updates in one place.</p>
            </div>
        </div>
        @include('partials.message')
        @include('partials.onboarding-reminder')
        @include('partials.plan-banner')

        <div class="flex flex-wrap my-2 dashboard-topfold">
            <div class="w-full xl:w-1/3 lg:w-2/3 my-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ url('/admin/students') }}">
                            <div class="bg-white custom-shadow px-5 py-4 border dashboard-kpi-card">
                                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto dashboard-kpi-icon" style="background: rgba(22,163,74,0.10); color: #16A34A;">
                                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800 dashboard-kpi-value">{{ $dashboard['studentCount'] }}</p>
                                    <p class="text-base item-title dashboard-kpi-label">Students</p>
                                </div>
                            </div>
                        </a>
                    <a href="{{ url('/admin/teachers') }}">
                            <div class="bg-white custom-shadow px-5 py-4 border dashboard-kpi-card">
                                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto dashboard-kpi-icon" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg><path d="M228,136a12,12,0,1,0,12,12A12.013,12.013,0,0,0,228,136Z"/><path d="M300,136a12,12,0,1,0,12,12A12.013,12.013,0,0,0,300,136Z"/><path d="M264,184a8.009,8.009,0,0,1-8-8H240a24,24,0,0,0,48,0H272A8.009,8.009,0,0,1,264,184Z"/><path d="M493.171,305.9a8.011,8.011,0,0,0-6.486-1.788l-72.327,12.055a36.4,36.4,0,0,0-13.4,5.078l-5.513-13.92a53.285,53.285,0,0,0-9.18-15.327l2.087-18.917a138.572,138.572,0,0,0,.844-14.517,130.564,130.564,0,0,0-10.645-51.547,115.553,115.553,0,0,1-9.125-51.1l1.361-27.691c.083-1.745.169-3.55.169-5.341A107.041,107.041,0,0,0,264,16,106.941,106.941,0,0,0,157.21,128.232l1.356,27.6c.08,2.061.154,4.007.154,5.933a114.046,114.046,0,0,1-9.273,45.249,130.724,130.724,0,0,0-9.8,66.118l2.081,18.656a54.31,54.31,0,0,0-6.336,9.162l-10.921-27.3a23.563,23.563,0,1,0-42.952,19.289L83.056,296H67.331a40.2,40.2,0,0,1-17.888-4.223l-7.972-3.986A17.6,17.6,0,0,0,16,303.533v22.578a23.868,23.868,0,0,0,13.267,21.466l13.021,6.511A56.284,56.284,0,0,0,67.331,360H72v23.691L66.253,458.4A34.925,34.925,0,0,0,101.075,496q.888,0,1.766-.045c.51.024,1.019.044,1.526.044a32.2,32.2,0,0,0,30.25-21.151l36.519-67.9A69.974,69.974,0,0,0,184,434.8V496h16V432a8,8,0,0,0-1.854-5.121,54.544,54.544,0,0,1,6.976-76.733l-10.244-12.292A70.515,70.515,0,0,0,184.939,348a8.225,8.225,0,0,0-.827,1l-.018-.01-.142.249c-.036.056-.065.118-.1.175a70.053,70.053,0,0,0-10.4,19.443L136,438.5V341.307L147.368,313a38.344,38.344,0,0,1,8.419-12.912A40.489,40.489,0,0,1,184.48,288h26.771l45.726,83.831a8,8,0,0,0,14.046,0L288,340.707V448a8,8,0,0,0,6.431,7.845L328,462.559V496h16V465.758l30.608,6.113c.2.035,1.127.129,1.392.129h15.135l1.536,2.862A32.409,32.409,0,0,0,423.04,496a30.12,30.12,0,0,0,5.273-.436,32.423,32.423,0,0,0,25.041-20.889l21.276-3.546h0a15.934,15.934,0,0,0,13.333-14.963l1.6-.321A8,8,0,0,0,496,448V312A8,8,0,0,0,493.171,305.9Zm-264.6-19.555A19.216,19.216,0,0,0,240,268.8V244.317a80.039,80.039,0,0,0,48,0V268.8a19.216,19.216,0,0,0,11.428,17.541L289.8,304H238.2ZM264,232a64.072,64.072,0,0,1-64-64V132.184a116.869,116.869,0,0,0,34.624-39.637A207.224,207.224,0,0,0,328,134.82V168A64.072,64.072,0,0,1,264,232Zm108.115,68.225.148.152a37.073,37.073,0,0,1,8.3,12.81L384,321.864a36.387,36.387,0,0,0-14.357-5.7l-62.567-10.428L316.749,288H343.44A39.635,39.635,0,0,1,372.115,300.225ZM376.792,344l-36.4-7.279-36.4-7.28v-8l63.012,10.5A20.263,20.263,0,0,1,382.376,344Zm40.2-12.054L480,321.443v8l-37.627,7.526L407.208,344h-5.584A20.263,20.263,0,0,1,416.989,331.946ZM49.443,339.777l-13.021-6.51A7.958,7.958,0,0,1,32,326.111V303.533a1.534,1.534,0,0,1,.759-1.361,1.526,1.526,0,0,1,1.556-.07l7.973,3.986A56.284,56.284,0,0,0,67.331,312H96a8,8,0,0,0,7.155-11.578l-7.323-14.648a7.56,7.56,0,0,1,4.929-10.717h0a7.589,7.589,0,0,1,8.855,4.527L120,305.541V344H67.331A40.2,40.2,0,0,1,49.443,339.777ZM120,360v16H88V360ZM82.207,459.624,87.408,392H120v69.075a18.936,18.936,0,0,1-17.555,18.856,8.169,8.169,0,0,0-.9.045c-.158,0-.311.024-.47.024a18.922,18.922,0,0,1-18.868-20.376ZM184.128,349.01a8,8,0,0,1,.509-.618c-.164.209-.323.421-.485.631Zm-27.662-69.452-.917-8.221a114.7,114.7,0,0,1,8.6-58.021A129.949,129.949,0,0,0,174.72,161.76c0-2.235-.084-4.427-.17-6.633l-1.36-27.684a90.941,90.941,0,1,1,181.77-4.563c0,1.41-.073,2.949-.15,4.567l-1.359,27.662A131.612,131.612,0,0,0,363.843,213.3,114.657,114.657,0,0,1,373.2,258.56a122.558,122.558,0,0,1-.752,12.8l-.909,8.244A55.951,55.951,0,0,0,343.44,272H307.2a3.243,3.243,0,0,1-3.2-3.2V237.237A80.026,80.026,0,0,0,344,168V128a8,8,0,0,0-6.869-7.92,191.273,191.273,0,0,1-95.466-42.439l-4.543-3.787a8,8,0,0,0-12.277,2.568l-3.1,6.2a100.785,100.785,0,0,1-34.182,38.721A8,8,0,0,0,184,128v40a80.026,80.026,0,0,0,40,69.237V268.8a3.243,3.243,0,0,1-3.2,3.2H184.48A56.358,56.358,0,0,0,156.466,279.558ZM264,351.293,246.931,320h34.138Zm40-5.534,64,12.8v95.682l-64-12.8ZM384,360h16v96H384Zm41.568,119.8a14.308,14.308,0,0,1-2.528.2,16.39,16.39,0,0,1-14.1-8.061c.151-.017.3-.042.449-.068.059-.011.118-.015.177-.026l.013,0,.055-.011,16.445-3.289a15.791,15.791,0,0,0,8.056,7.015A16.678,16.678,0,0,1,425.568,479.8ZM472,455.346h0l-32,5.334V430.945a2.408,2.408,0,0,1,2.017-2.381l27.173-4.529A2.621,2.621,0,0,1,469.6,424a2.416,2.416,0,0,1,2.4,2.416l.006,28.928Zm-5.442-47.093-27.172,4.528A18.357,18.357,0,0,0,424,430.945v21.7l-8,1.6V358.559l64-12.8v65.479A18.361,18.361,0,0,0,466.559,408.253Z"/></g></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800 dashboard-kpi-value">{{ $dashboard['teacherCount'] }}</p>
                                    <p class="text-base item-title dashboard-kpi-label">Teachers</p>
                                </div>
                            </div>
                        </a>
                    <a href="{{ url('/admin/parents') }}">
                            <div class="bg-white custom-shadow px-5 py-4 border dashboard-kpi-card">
                                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto dashboard-kpi-icon" style="background: rgba(217,119,6,0.10); color: #D97706;">
                                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M20 3.13a4 4 0 010 7.75"/><path d="M21 9l-2 2-1-1"/></svg><g><g><path d="M71.069,354.63c-1.859-1.86-4.439-2.92-7.069-2.92s-5.21,1.06-7.07,2.92c-1.86,1.86-2.93,4.439-2.93,7.08 c0,2.63,1.069,5.2,2.93,7.06c1.86,1.87,4.44,2.94,7.07,2.94s5.21-1.07,7.069-2.94c1.861-1.86,2.931-4.44,2.931-7.06 C74,359.08,72.93,356.5,71.069,354.63z"></path></g></g><g><g><path d="M484.095,282.754l-0.716-0.743c-3.845-3.965-10.177-4.063-14.141-0.217c-3.965,3.845-4.062,10.176-0.217,14.141 l0.594,0.616c1.966,2.063,4.602,3.102,7.242,3.102c2.478,0,4.96-0.916,6.896-2.76 C487.751,293.083,487.904,286.753,484.095,282.754z"></path></g></g><g><g><path d="M505.89,317.818c-1.895-5.188-7.635-7.857-12.823-5.964c-5.188,1.895-7.858,7.636-5.964,12.823 c3.25,8.899,4.897,18.265,4.897,27.835V465h-34V361.705c0-5.522-4.478-10-10-10c-5.522,0-10,4.478-10,10V465h-79.464v-77.749 c0-33.581-22.735-62.688-55.289-70.781c-0.137-0.034-0.273-0.065-0.411-0.094l-0.786-0.161 c8.261-8.487,14.198-19.236,16.772-31.222l34.632,55.38c1.828,2.923,5.032,4.698,8.479,4.698c3.447,0,6.651-1.776,8.479-4.699 l43.097-68.946c8.763,0.281,17.332,1.934,25.502,4.957c1.145,0.423,2.316,0.624,3.469,0.624c4.065,0,7.887-2.498,9.38-6.533 c1.916-5.181-0.729-10.933-5.909-12.849c-9.599-3.551-19.628-5.592-29.877-6.112c18.239-15.421,29.85-38.448,29.85-64.146v-31.431 v-17.814V133c0-0.933-0.137-1.832-0.376-2.688c-3.955-42.668-39.941-76.189-83.624-76.189h-0.001 c-43.683,0-79.668,33.521-83.623,76.189c-0.239,0.856-0.376,1.755-0.376,2.688v5.123v17.814v31.431 c0,4.391,0.342,8.702,0.994,12.913c-5.805-2.224-12.01-3.625-18.478-4.069v-58.468C260.44,76.679,210.762,27,149.697,27 S38.953,76.679,38.953,137.744v135.059C15.259,291.331,0,320.172,0,352.513V475c0,5.522,4.478,10,10,10h492 c5.522,0,10-4.478,10-10V352.513C512,340.595,509.944,328.922,505.89,317.818z M361.931,316.208l-32.013-51.191 c9.871,4.084,20.677,6.351,32.006,6.351h28.036L361.931,316.208z M297.925,138.123c-0.001-35.29,28.709-64,63.999-64 c35.29,0,64,28.71,64,64v0.341l-34.438-20.232c-4.211-2.475-9.593-1.544-12.73,2.199c-13.583,16.209-33.502,25.506-54.65,25.506 h-26.181V138.123z M297.924,165.937h26.181c24.218,0,47.177-9.521,64.2-26.378l37.619,22.101v25.708c0,35.29-28.711,64-64.001,64 c-35.289,0-63.999-28.71-63.999-64V165.937z M150.076,251.368c-35.29,0-64-28.71-64-64v-21.431h26.182 c24.218,0,47.177-9.521,64.2-26.378l37.618,22.101v25.708C214.076,222.658,185.366,251.368,150.076,251.368z M181.578,265.216 l-31.874,50.992l-28.041-44.84h28.413C161.215,271.368,171.843,269.17,181.578,265.216z M211.742,261.333H243.6 c6.688,0,13.166-1.783,18.829-4.999l17.178,4.652c0.853,0.231,1.731,0.349,2.614,0.349h18.037v10.19 c0,24.356-19.854,44.172-44.258,44.172s-44.258-19.815-44.258-44.172V261.333z M295.98,241.334h-12.429l-5.961-1.614 c0.94-4.127-0.824-8.56-4.663-10.828c-4.755-2.809-10.886-1.232-13.696,3.522c-3.251,5.502-9.24,8.92-15.631,8.92h-27.58 c7.116-14.941,22.359-25.293,39.98-25.293C273.621,216.041,288.864,226.394,295.98,241.334z M58.953,137.744 C58.953,87.708,99.661,47,149.697,47s90.743,40.708,90.743,90.744v60.212c-2.515,0.628-4.969,1.409-7.358,2.325 c0.652-4.21,0.994-8.522,0.994-12.913v-31.431c0-3.497-1.92-6.851-4.935-8.622l-49.503-29.083 c-4.211-2.474-9.592-1.544-12.73,2.199c-13.583,16.209-33.502,25.506-54.65,25.506H76.076c-5.522,0-10,4.477-10,10v31.431 c0,25.692,11.606,48.714,29.837,64.135c-13.133,0.672-25.609,3.866-36.96,9.098V137.744z M153.464,465H74v-59.333 c0-5.522-4.478-10-10-10c-5.522,0-10,4.478-10,10V465H20V352.513c0-43.73,34.776-79.471,78.123-81.068l43.104,68.928 c1.828,2.923,5.032,4.698,8.479,4.698c3.447,0,6.651-1.776,8.479-4.699l34.891-55.818c2.522,12.161,8.509,23.064,16.868,31.655 l-0.86,0.183c-0.111,0.024-0.222,0.05-0.331,0.077c-32.554,8.094-55.289,37.2-55.289,70.781V465z M338.536,465h-29.229v-62 c0-5.522-4.478-10-10-10c-5.522,0-10,4.478-10,10v62h-76v-62c0-5.522-4.478-10-10-10c-5.522,0-10,4.478-10,10v62h-19.844v-77.749 c0-24.319,16.423-45.404,39.957-51.333l20.106-4.281c6.999,2.618,14.57,4.059,22.473,4.059c7.793,0,15.266-1.395,22.183-3.945 l20.433,4.176c23.517,5.94,39.921,27.016,39.921,51.324V465z"></path></g></g><g><g><path d="M337.16,175.83c-1.86-1.86-4.44-2.93-7.07-2.93c-2.64,0-5.21,1.07-7.07,2.93c-1.87,1.86-2.93,4.44-2.93,7.07 c0,2.64,1.06,5.21,2.93,7.07c1.86,1.87,4.431,2.93,7.07,2.93c2.63,0,5.21-1.06,7.07-2.93c1.859-1.86,2.93-4.43,2.93-7.07 C340.09,180.27,339.02,177.69,337.16,175.83z"></path></g></g><g><g><path d="M400.83,175.83c-1.86-1.86-4.44-2.93-7.07-2.93s-5.21,1.07-7.07,2.93c-1.859,1.86-2.93,4.44-2.93,7.07 c0,2.64,1.07,5.21,2.93,7.07c1.86,1.87,4.44,2.93,7.07,2.93s5.21-1.06,7.07-2.93c1.859-1.86,2.93-4.44,2.93-7.07 S402.69,177.69,400.83,175.83z"></path></g></g><g><g><path d="M380.686,207.885c-3.906-3.905-10.236-3.905-14.142,0c-2.629,2.63-6.908,2.63-9.537,0c-3.906-3.905-10.236-3.905-14.143,0 c-3.905,3.905-3.905,10.237,0,14.143c5.214,5.213,12.063,7.82,18.911,7.82s13.697-2.607,18.911-7.82 C384.591,218.123,384.591,211.791,380.686,207.885z"></path></g></g><g><g><path d="M125.309,175.83c-1.859-1.86-4.439-2.93-7.069-2.93c-2.631,0-5.21,1.07-7.07,2.93c-1.86,1.86-2.93,4.44-2.93,7.07 s1.069,5.21,2.93,7.07c1.86,1.87,4.439,2.93,7.07,2.93c2.63,0,5.21-1.06,7.069-2.93c1.86-1.86,2.931-4.44,2.931-7.07 S127.17,177.69,125.309,175.83z"></path></g></g><g><g><path d="M188.979,175.83c-1.859-1.86-4.43-2.93-7.069-2.93c-2.63,0-5.21,1.07-7.07,2.93c-1.86,1.86-2.93,4.44-2.93,7.07 s1.069,5.21,2.93,7.07c1.861,1.86,4.44,2.93,7.07,2.93c2.64,0,5.21-1.07,7.069-2.93c1.87-1.86,2.931-4.44,2.931-7.07 S190.849,177.69,188.979,175.83z"></path></g></g><g><g><path d="M168.84,207.885c-3.906-3.905-10.236-3.905-14.143,0c-2.629,2.63-6.908,2.63-9.537,0c-3.906-3.905-10.236-3.905-14.143,0 c-3.905,3.906-3.905,10.238,0.001,14.143c5.214,5.213,12.063,7.82,18.911,7.82c6.848,0,13.697-2.607,18.911-7.82 C172.745,218.123,172.745,211.791,168.84,207.885z"></path></g></g><g><g><path d="M274.911,282.7c-3.906-3.904-10.236-3.904-14.143,0c-2.629,2.631-6.908,2.631-9.537,0c-3.906-3.904-10.236-3.904-14.143,0 c-3.905,3.905-3.905,10.237,0,14.143c5.215,5.214,12.063,7.82,18.912,7.82s13.697-2.606,18.911-7.82 C278.816,292.938,278.816,286.606,274.911,282.7z"></path></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800 dashboard-kpi-value">{{ $dashboard['parentCount'] }}</p>
                                    <p class="text-base item-title dashboard-kpi-label">Parents</p>
                                </div>
                            </div>
                        </a>
                    <a href="{{ url('/admin/staffs') }}">
                            <div class="bg-white custom-shadow px-5 py-4 border dashboard-kpi-card">
                                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto dashboard-kpi-icon" style="background: rgba(220,38,38,0.10); color: #DC2626;">
                                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><path d="m456 48h-48v-8a24 24 0 0 0 -48 0v8h-80v-8a24 24 0 0 0 -48 0v8h-80v-8a24 24 0 0 0 -48 0v8h-48a40.045 40.045 0 0 0 -40 40v368a40.045 40.045 0 0 0 40 40h400a40.045 40.045 0 0 0 40-40v-368a40.045 40.045 0 0 0 -40-40zm-80-8a8 8 0 0 1 16 0v48a8 8 0 0 1 -16 0zm-128 0a8 8 0 0 1 16 0v48a8 8 0 0 1 -16 0zm-128 0a8 8 0 0 1 16 0v48a8 8 0 0 1 -16 0zm-64 24h48v24a24 24 0 0 0 48 0v-24h80v24a24 24 0 0 0 48 0v-24h80v24a24 24 0 0 0 48 0v-24h48a24.028 24.028 0 0 1 24 24v56h-448v-56a24.028 24.028 0 0 1 24-24zm400 416h-400a24.028 24.028 0 0 1 -24-24v-296h448v296a24.028 24.028 0 0 1 -24 24z"/><path d="m440 216h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m280 376h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m200 216h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m120 296h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m200 296h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m120 376h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m200 376h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m369.208 248.983-40-6.112-17.966-38.271a8 8 0 0 0 -14.484 0l-17.958 38.271-40 6.112a8 8 0 0 0 -4.518 13.5l29.181 29.907-6.9 42.326a8 8 0 0 0 11.753 8.284l35.684-19.724 35.684 19.724a8 8 0 0 0 11.766-8.289l-6.9-42.326 29.181-29.907a8 8 0 0 0 -4.518-13.5zm-38.934 35.117a8 8 0 0 0 -2.17 6.875l4.9 30.051-25.136-13.9a8 8 0 0 0 -7.74 0l-25.136 13.9 4.9-30.051a8 8 0 0 0 -2.17-6.875l-21.122-21.652 28.833-4.4a8 8 0 0 0 6.033-4.509l12.534-26.711 12.535 26.706a8 8 0 0 0 6.033 4.509l28.833 4.4z"/><path d="m440 296h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m440 376h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/><path d="m360 376h-32a8 8 0 0 0 -8 8v32a8 8 0 0 0 8 8h32a8 8 0 0 0 8-8v-32a8 8 0 0 0 -8-8zm-8 32h-16v-16h16z"/></svg>
                                </div>
                                <div class="text-center py-1">
                                    <p class="text-2xl font-semibold text-gray-800 dashboard-kpi-value">{{ $dashboard['nonteachingCount'] }}</p>
                                    <p class="text-base  item-title dashboard-kpi-label">Non Teaching Staff</p>
                                </div>
                            </div>
                        </a>
                </div>

                <!-- WhatsApp Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white custom-shadow px-5 py-4 border dashboard-kpi-card h-full">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto dashboard-kpi-icon" style="background: rgba(22,163,74,0.10); color: #16A34A;">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21z"/>
                                <path d="M8 9.5c0-.83.68-1.5 1.5-1.5h.38c.47 0 .9.28 1.07.72l.3.74c.24.58.03 1.26-.5 1.57l-.28.17a6.5 6.5 0 002.5 2.5l.17-.28c.31-.53.99-.74 1.57-.5l.74.3c.44.17.72.6.72 1.07v.38c0 .83-.67 1.5-1.5 1.5C10.7 16.5 8 13.8 8 9.5z"/>
                            </svg>
                        </div>
                        <div class="text-center py-1">
                            <p class="text-2xl font-semibold text-gray-800 dashboard-kpi-value">{{ $dashboard['whatsapp']['parentsOptedIn'] }}</p>
                            <p class="text-base item-title dashboard-kpi-label">WhatsApp Parents</p>
                        </div>
                    </div>
                    <div class="bg-white custom-shadow px-5 py-4 border dashboard-kpi-card h-full">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto dashboard-kpi-icon" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </div>
                        <div class="text-center py-1">
                            <p class="text-2xl font-semibold text-gray-800 dashboard-kpi-value">{{ $dashboard['whatsapp']['messagesThisMonth'] }}</p>
                            <p class="text-base item-title dashboard-kpi-label">Messages This Month</p>
                        </div>
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
                        @php
                            $hasGenderData = ($dashboard['femaleCount'] ?? 0) > 0 || ($dashboard['maleCount'] ?? 0) > 0;
                        @endphp
                        <div class="border-r w-1/2 mt-4 bar-bg-blue relative student_count dashboard-gender-stat">
                            <a href="{{ url('/admin/students?gender=female') }}">
                                <p class="text-sm item-title font-semibold">Girls</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $hasGenderData ? $dashboard['femaleCount'] : '—' }}</p>
                            </a>
                        </div>
                        <div class="w-1/2 text-right mt-4 bar-bg-orange relative student_count student_male_count dashboard-gender-stat">
                            <a href="{{ url('/admin/students?gender=male') }}" target="_blank">
                                <p class="text-sm item-title font-semibold ">Boys</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $hasGenderData ? $dashboard['maleCount'] : '—' }}</p>
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
                                    <div class="bg-green-600 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>{{ $noticeboard->title }}</p>
                                    </div>
                                    <div class="text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2" style="background:#c96442;">
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
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="my-4 text-center text-gray-400">No notices yet</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Pending Approvals KPI --}}
        <div class="mb-4">
            <a href="{{ url('admin/approvals') }}" class="bg-white custom-shadow px-5 py-4 border dashboard-kpi-card inline-flex items-center gap-4 hover:shadow-md transition" style="max-width:280px;">
                <div class="w-14 h-14 rounded-full flex items-center justify-center" style="background:#FEF3C7;">
                    <svg class="w-7 h-7" style="color:#D97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-semibold text-gray-800">{{ $pendingApprovals }}</p>
                    <p class="text-sm text-gray-500">Pending Approvals</p>
                </div>
            </a>
        </div>

        <div class="flex flex-col lg:flex-row my-2 gap-4">
            @if(config('gexam.enabled', false))
            <div class="w-full lg:w-2/3">
                <div class="bg-white custom-shadow py-1 border dashboard-panel-card">
                    <div>
                        <h1 class="dashboard-panel-title px-3 py-2 pb-3">Upcoming Exams</h1>
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
                            @if(!empty($dashboard['upcomingExam']) && count($dashboard['upcomingExam']) > 0)
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
                                        <td colspan="4" class="text-center py-6 text-gray-400">No exams yet</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="w-full lg:w-1/3">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border dashboard-panel-card">
                    <div>
                        <h1 class="dashboard-panel-title px-3 py-2 pb-3">Feedbacks</h1>
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

        <div class="flex flex-col lg:flex-row my-2 gap-4">
            <div class="w-full lg:w-1/2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border dashboard-panel-card">
                    <div>
                        <h1 class="dashboard-panel-title px-3 py-2 pb-3">Events</h1>
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
                                        <td colspan="2" class="text-center py-6 text-gray-400">No events yet</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-1/2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border dashboard-panel-card">
                    <div>
                        <h1 class="dashboard-panel-title px-3 py-2 pb-3">Products</h1>
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
                                        <td colspan="3" class="text-center py-6 text-gray-400">No products yet</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row my-2 gap-4">
            <div class="w-full lg:w-1/2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border h-full dashboard-panel-card">
                    <h3 class="dashboard-panel-title mb-3">Today's Absentees — Students</h3>
                    <absentees-student url="{{ url('/') }}"></absentees-student>
                </div>
            </div>
            <div class="w-full lg:w-1/2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border h-full dashboard-panel-card">
                    <h3 class="dashboard-panel-title mb-3">Today's Absentees — Staff</h3>
                    <absentees-staff url="{{ url('/') }}"></absentees-staff>
                </div>
            </div>
            @if(count($dashboard['products']) > 0)
            <div class="w-full xl:w-2/3 lg:w-1/2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border h-full dashboard-panel-card">
                    <div>
                        <h1 class="dashboard-panel-title px-3 py-2 pb-3">Latest Products</h1>
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

        <div class="flex my-2 gap-4">
            <div class="w-full">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-chart-card">
                    <div>
                        <h1 class="text-gray-800 font-semibold text-xl dashboard-panel-title">Students Per Class</h1>
                    </div>
                    <canvas id="barChart" class="dashboard-chart-canvas"></canvas>
                </div>
            </div>
        </div>

        {{-- Fee Collection Trend Chart --}}
        <div class="flex my-2 gap-4">
            <div class="w-full">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-chart-card">
                    <div class="flex flex-wrap items-center justify-between mb-4">
                        <h1 class="text-gray-800 font-semibold text-xl dashboard-panel-title">Fee Collection Trends</h1>
                        <div class="flex gap-1 bg-gray-100 rounded-lg p-0.5" role="group">
                            <a href="{{ request()->fullUrlWithQuery(['period' => 'day']) }}"
                               class="px-3 py-2.5 text-xs font-semibold rounded-md transition-colors duration-150 {{ $trendPeriod === 'day' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                                Days
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['period' => 'week']) }}"
                               class="px-3 py-2.5 text-xs font-semibold rounded-md transition-colors duration-150 {{ $trendPeriod === 'week' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                                Weeks
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['period' => 'month']) }}"
                               class="px-3 py-2.5 text-xs font-semibold rounded-md transition-colors duration-150 {{ $trendPeriod === 'month' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                                Months
                            </a>
                        </div>
                    </div>
                    <canvas id="feeTrendChart" class="dashboard-chart-canvas" style="height:260px;"></canvas>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>
    <script>
        var ctx = document.getElementById('graph').getContext('2d');
        var femaleCount = {!! trans($dashboard['femaleCount'] ?? 0) !!};
        var maleCount = {!! trans($dashboard['maleCount'] ?? 0) !!};
        var totalStudents = femaleCount + maleCount;

        if (totalStudents === 0) {
            var ctx2 = document.getElementById('graph').getContext('2d');
            ctx2.clearRect(0, 0, ctx2.canvas.width, ctx2.canvas.height);
            ctx2.textAlign = 'center';
            ctx2.textBaseline = 'middle';
            ctx2.font = "13px 'DM Sans', sans-serif";
            ctx2.fillStyle = '#94A3B8';
            ctx2.fillText('No gender data', ctx2.canvas.width / 2, ctx2.canvas.height / 2);
        }

        Chart.pluginService.register({
            beforeDraw: function(chart) {
                if (chart.config.type !== 'doughnut') return;
                var width = chart.chart.width,
                    height = chart.chart.height,
                    ctx = chart.chart.ctx;
                ctx.restore();
                var fontSize = (height / 140).toFixed(2);
                ctx.font = "700 " + fontSize + "em 'Sora', sans-serif";
                ctx.textBaseline = "middle";
                ctx.fillStyle = "#4d4c48";
                var text = totalStudents,
                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                    textY = height / 2;
                ctx.fillText(text, textX, textY);
                ctx.save();
            }
        });

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

        // ── Fee Collection Trend Chart ──
        var trendCtx = document.getElementById('feeTrendChart');
        if (trendCtx) {
            var trendData = {!! json_encode($feeTrend ?? []) !!};
            new Chart(trendCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: trendData.map(function (d) { return d.label; }),
                    datasets: [{
                        label: 'Fee Collection',
                        data: trendData.map(function (d) { return d.amount; }),
                        borderColor: '#22C55E',
                        backgroundColor: 'rgba(34,197,94,0.06)',
                        borderWidth: 2,
                        pointBackgroundColor: '#22C55E',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false,
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#0F172A',
                        callbacks: {
                            label: function (tooltipItem, data) {
                                var val = tooltipItem.yLabel;
                                return 'UGX ' + Number(val).toLocaleString();
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                fontFamily: 'DM Sans',
                                fontSize: 11,
                                callback: function (value) {
                                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                                    return value;
                                }
                            },
                            gridLines: {
                                color: '#F1F5F9',
                                drawBorder: false,
                            }
                        }],
                        xAxes: [{
                            ticks: {
                                fontFamily: 'DM Sans',
                                fontSize: 11,
                            },
                            gridLines: {
                                display: false,
                            }
                        }]
                    }
                }
            });
        }

        var ctx = document.getElementById("barChart");
        if (ctx) {
            var standardData = {!! json_encode(($dashboard['standardStudentCounts'] ?? collect())->map(fn($l) => [
                'label' => $l->StandardSection ?? ('Standard ' . $l->id),
                'count' => $l->studentCount ?? 0,
            ])->values()) !!};
            var barChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: standardData.map(function(d) { return d.label; }),
                    datasets: [{
                        label: 'Students',
                        data: standardData.map(function(d) { return d.count; }),
                        backgroundColor: '#c96442',
                        borderRadius: 6,
                    }]
                },
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                stepSize: 1,
                                precision: 0,
                            }
                        }]
                    },
                    legend: { display: false },
                }
            });
        }

    </script>
@endpush
