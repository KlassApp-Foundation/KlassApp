{{-- SPDX-License-Identifier: MIT --}}

    {{--<div class="container mx-auto h-full">
        <div class="w-full p-8 flex flex-col lg:flex-row items-center h-full">
            <div class="w-full lg:w-2/3">
                <!-- <h1 class="text-left mt-8 mb-4 text-white" style="font-size: 48px; line-height: 48px;">School Management Software as Service</h1> -->
                <h1 class="text-left mt-8 mb-4 text-white text-5xl font-bold leading-normal">School Management Software as Service</h1>
                <h2 class="text-xl text-left text-white">Manage your school students easily and also manage the Events, Activities and School Website.</h2>
                <div class="py-8 flex mx-auto justify-start">
                    @if( (Auth::user()->usergroup_id == 3) || (Auth::user()->usergroup_id == 5) || (Auth::user()->usergroup_id == 7) )
                        <a href="{{ url('/admin/dashboard') }}" class="button p-4 bg-white border-l-4 border-blue-500 mr-2 font-bold">Go To Dashboard</a>
                    @elseif(Auth::user()->usergroup_id == 6)
                        <a href="{{ url('/student/dashboard') }}" class="button p-4 bg-white border-l-4 border-blue-500 mr-2 font-bold">Go To Dashboard</a>
                    @else
                        <a href="{{ url('/register') }}" class="button px-8 py-4 bg-white border-l-4 border-blue-500 mr-2 font-bold">Register as School</a>
                    @endif
                
                        <!-- <a href="{{ url('/register') }}" class="button p-4 bg-white rounded ml-2">Register as Church Member</a> -->
                </div>
            </div>
           <!--  <div class="w-full lg:w-1/2 image-section">
                <img src="{{ url('uploads/static/homebanner.png') }}" alt="welcome to church membership pro" style="min-height: 400px">
            </div> -->
        </div>
    </div>--}}
    <div class="container-wrapper  homebanner_sec  parallax relative">
    <div class="overlay flex">
    <div class="container mx-auto flex flex-col items-center justify-center">
        <div class="text-center w-4/5 mx-auto">
            <h1 class="text-6xl font-bold text-white">School Management to manage the entire campus Operations and Administration</h1>
            <!-- <h1 class="text-6xl font-bold text-white">Improve Your Skill & Learn Excilence In Teaching</h1> -->
        </div>
         <div class="py-8 flex mx-auto justify-start">
                    @if( (Auth::user()->usergroup_id == 3) || (Auth::user()->usergroup_id == 5) || (Auth::user()->usergroup_id == 7) )
                        <a href="{{ url('/admin/dashboard') }}" class="button p-4 bg-white border-l-4 border-blue-500 mr-2 font-bold">Go To Dashboard</a>
                    @elseif(Auth::user()->usergroup_id == 6)
                        <a href="{{ url('/student/dashboard') }}" class="button p-4 bg-white border-l-4 border-blue-500 mr-2 font-bold">Go To Dashboard</a>
                    @else
                        <a href="{{ url('/register') }}" class="button px-8 py-4 bg-white border-l-4 border-blue-500 mr-2 font-bold">Register as School</a>
                    @endif
                
                        <!-- <a href="{{ url('/register') }}" class="button p-4 bg-white rounded ml-2">Register as Church Member</a> -->
                </div>
    </div>
</div>
</div>