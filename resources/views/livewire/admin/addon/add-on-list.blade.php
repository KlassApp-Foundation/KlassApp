{{-- SPDX-License-Identifier: MIT --}}
<div>
    {{-- Be like water. --}}
<div class="bg-gray-50">
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Section Header -->
            <div class="text-center mb-12">
                <h2 class="mt-2 text-3xl font-bold text-gray-900 sm:text-4xl">Buy Modules</h2>
                {{-- <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-600">
                    Discover what makes our product stand out from the competition.
                </p> --}}
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Feature 1 -->
                @if(count($addonsList)>0)
                @foreach($addonsList as $addon)
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between">
                    <div class="w-14 h-14  rounded-lg flex items-center justify-start mb-6">
                        {{-- <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg> --}}
                        <img src="{{$addon['image']}}" class="h-16 w-16 text-blue-600">
                    </div>
                    <div class=" px-3 py-2 rounded text-white" style="background-color: #9b2c2c"><p class="text-xl font-semibold" >${{$addon['price']}}</p></div> 
                  </div>
                    <a href="{{ url('admin/addon/'.$addon['name'].'/detail') }}">
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ ucfirst($addon['title']) }}</h3>
                    </a>
                    <p class="text-gray-600">
                        {{ ucfirst($addon['description']) }}.
                    </p>
                    <a href="{{ url('admin/addon/'.$addon['name'].'/detail') }}">
                    <div class="bg-gray-700 px-3 py-2 rounded text-white mx-auto mt-10 " style="width:fit-content"><p class="text-xl font-semibold">Buy now</p></div></a>
                </div>
                @endforeach
                @else
                <div><p>No records found</p></div>

                @endif

                <!-- Feature 2 -->
                {{-- <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Secure by Design</h3>
                    <p class="text-gray-600">
                        Enterprise-grade security with end-to-end encryption and regular penetration testing.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="w-14 h-14 bg-purple-100 rounded-lg flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Intuitive Interface</h3>
                    <p class="text-gray-600">
                        Designed for humans with thoughtful UX patterns that reduce learning curves.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="w-14 h-14 bg-yellow-100 rounded-lg flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">24/7 Support</h3>
                    <p class="text-gray-600">
                        Our award-winning support team is available around the clock to assist you.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="w-14 h-14 bg-red-100 rounded-lg flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Regular Updates</h3>
                    <p class="text-gray-600">
                        Continuous improvements with new features released every two weeks.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="w-14 h-14 bg-indigo-100 rounded-lg flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">99.9% Uptime</h3>
                    <p class="text-gray-600">
                        Guaranteed reliability with our distributed cloud infrastructure.
                    </p>
                </div>--}}
            </div>
        </div>
    </section>
</div>
</div>
