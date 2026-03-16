<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
@extends('layouts.admin.layout')

@section('content')

<div class="flex flex-col lg:flex-row my-4">
  <div class="w-full lg:w-1/4 px-2 relative h-full my-2 lg:my-0"><a href="{{url('admin/students')}}">
    <div class="bg-1 px-3 py-3 flex justify-between items-center rounded ">
      <div class="text-white  w-full overflow-hidden">
        <div class="flex justify-between items-center">
          <p class="text-4xl font-bold">{{ $memberCount }}</p>
          <img src="{{asset('uploads/icons/user-avatar.svg')}}" class="w-10 h-10 opacity-75">
        </div>
        <div class="mt-4">
          <p class="text-sm">Total Students</p> 
        </div>
      </div>
      <div class="absolute right-0 overflow-hidden">
        <img src="{{asset('uploads/icons/circular-shape.svg')}}" class="w-24 h-24 circular-1">
        <img src="{{asset('uploads/icons/circular-shape.svg')}}" class="w-24 h-24 circular-2">
      </div>
    </div></a>
  </div>
<!--Modified-->
<!--start-->
  <div class="w-full lg:w-1/4 px-2 my-2 lg:my-0"><a href="{{url('admin/students?gender=male')}}">
    <div class="bg-2 px-2 flex justify-between items-center rounded relative h-full overflow-hidden">
      <div class="text-white w-full ">
        <div class=" flex justify-between items-center">
          <p class="text-4xl font-bold">{{ $maleCount }}</p>
          <img src="{{asset('uploads/icons/male.svg')}}" class="w-10 h-10 opacity-75">
        </div>
        <div class="mt-4"><p class="text-sm">Boys</p></div>
      </div>
      <div class="absolute right-0 overflow-hidden">
        <img src="{{asset('uploads/icons/circular-shape.svg')}}" class="w-24 h-24 circular-1">
        <img src="{{asset('uploads/icons/circular-shape.svg')}}" class="w-24 h-24 circular-2">
      </div>
    </div></a>
  </div>
  <div class="w-full lg:w-1/4 px-2 my-2 lg:my-0"><a href="{{url('admin/students?gender=female')}}">
    <div class="bg-4 px-2  flex justify-between items-center rounded relative h-full overflow-hidden">
      <div class="text-white w-full ">
        <div class="flex justify-between items-center">
          <p class="text-4xl font-semibold">{{ $femaleCount }}</p>
          <img src="{{asset('uploads/icons/female.svg')}}" class="w-10 h-10 opacity-75">
        </div>
        <div class="mt-4"><p class="text-sm">Girls</p></div>
      </div>
      <div class="absolute right-0 overflow-hidden">
        <img src="{{asset('uploads/icons/circular-shape.svg')}}" class="w-24 h-24 circular-1">
        <img src="{{asset('uploads/icons/circular-shape.svg')}}" class="w-24 h-24 circular-2">
      </div>
    </div></a>
  </div>
  
  <div class="w-full lg:w-1/4 px-2 my-2 lg:my-0 dashboard-filter">
      <portal-target name="search" ></portal-target>
      <portal-target name="memberfilter" ></portal-target>
      <search-filter url="{{url('/')}}" searchquery="{{$query}}"></search-filter>
  </div>
<!--end-->

</div>
<!-- end -->
<!-- start -->
<div class="flex flex-col lg:flex-row my-4">
  <div class="w-full lg:w-3/4">
    <!-- start -->
    <div class="flex flex-col lg:flex-row">
    <!-- statistics start -->
      <div class="w-full lg:w-1/3  my-2 px-3">
        <div>
          <h1 class="text-sm uppercase text-gray-800 font-semibold mb-2">Statistics</h1>
        </div>
        <div class="shadow px-3 py-3 my-2">
          <div class="flex justify-between flex-col py-2">
            <div class=" leading-relaxed flex justify-between"> 
              <p class="text-xs text-black font-semibold">Students</p>
              <p class="text-xs text-gray-500">{{ $subscription->plan->no_of_members-$memberCount }}/{{ $subscription->plan->no_of_members }}</p>
            </div>
            <div class="progress w-1/2 my-1">
              <div class="bar" style="width:{{ $memberCount }}%">
                <p class="percent"></p>
              </div>
            </div>
          </div>  
          <div class="flex justify-between flex-col py-2">
            <div class="leading-relaxed flex justify-between"> 
              <p class="text-xs text-black font-semibold">Events</p>
              <p class="text-xs text-gray-500">{{ $subscription->plan->no_of_events-$eventCount }}/{{ $subscription->plan->no_of_events }}</p>
            </div>
            <div class="progress w-1/2 my-1">
              <div class="bar" style="width:{{ $eventCount }}%">
                <p class="percent"></p>
              </div>
            </div>
          </div>   
               
          <div class="flex justify-between flex-col py-2">
            <div class="leading-relaxed flex justify-between"> 
              <p class="text-xs text-black font-semibold">Files ( Videos,Magazine )</p>
              <p class="text-xs text-gray-500">{{ $subscription->plan->no_of_files-$fileCount +  $subscription->plan->no_of_videos-$videoCount  +  $subscription->plan->no_of_bulletins-$bulletinCount }}/{{ $subscription->plan->no_of_files + $subscription->plan->no_of_videos + $subscription->plan->no_of_bulletins }}</p>
            </div>
            <div class="progress w-1/2 my-1">
              <div class="bar" style="width:{{ $fileCount + $galleryCount + $videoCount + $bulletinCount }}%">
                <p class="percent"></p>
              </div>
            </div>
          </div>  
        </div>
      </div>
      <!-- statistics end -->
      <div class="w-full lg:w-2/3 my-2">


         <div class="">
      <h1 class="text-sm uppercase text-gray-800 font-semibold mb-2">Upcoming Events</h1>
      <event url="{{url('/')}}"></event>
    </div>









      <!-- recent member start -->
            
<!--       <div class="flex flex-col lg:flex-row my-2">
        <div class="w-full lg:w-2/5 custom-table my-2 px-3"> 
          <div>
            <div>
              <h1 class="text-sm text-gray-800 uppercase font-semibold mb-2">Recently Added Members</h1>
            </div>
            <div class="shadow px-3 py-1">
            @if(count($recentMember) > 0) 
              @foreach($recentMember as $recentMembers)
                <div class="flex py-2 items-center">
                  <div class="w-1/5">
                    <img src="{{url($recentMembers->userprofile->avatar)}}" class="w-8 h-8 rounded-full">
                  </div>
                  <div class="mx-2 text-gray-700 w-4/5 flex justify-between items-center">
                    <p class="font-semibold text-sm ">
                      <a href="{{ url('/admin/member/show/'.$recentMembers->name) }}">{{ ucwords($recentMembers->userprofile->firstname) }}</a>
                    </p>
                    <p class="text-gray-600 text-xs">{{ date('M-d',strtotime($recentMembers->created_at)) }}</p>
                  </div>
                </div>
              @endforeach
            @else
              <div class="flex py-2 items-center">
                <p class="font-semibold text-sm w-full" style="text-align: center;">No Records Found</p>
              </div>
            @endif
            </div>
          </div>
        </div> -->
      <!-- recent member end -->
      
        <!-- absent member start -->
<!--         <div class="w-full lg:w-3/5 lg:flex-row my-2">
          <div>
            <h1 class="text-sm uppercase text-gray-800 font-semibold mb-2">Not Attended Members
            <span class="text-gray-600 px-10"><u><a href="{{ url('/admin/dashboard/absent') }}">See All</a></u></span></h1>
          </div>
          <div class="shadow px-3 py-1">
            @if(count($absentMembers) > 0)   
              @foreach($absentMembers as $absentMember)
                <div class="flex py-2 items-center">
                  <div class="w-1/6">
                    <img src="{{url($absentMember->user->userprofile->avatar)}}" class="w-8 h-8 rounded-full">
                  </div>
                  <div class="mx-2 text-gray-700 flex justify-between items-center w-5/6">
                    <p class="font-semibold text-sm w-1/3">
                      <a href="{{ url('/admin/member/show/'.$absentMember->user->name) }}">{{ ucwords($absentMember->user->userprofile->firstname) }}</a>
                    </p>
                    <p class="text-gray-600 text-xs w-1/3">
                      <a href="{{ url('/admin/events/show/details/'.$absentMember->entity_id) }}"> {{ $absentMember->events->title }}</a>
                    </p>
                    <p class="text-gray-600 text-xs w-1/3">{{ date('M-d Y',strtotime($absentMember->date)) }} </p>
                  </div>
                </div>
              @endforeach
            @else
              <div class="flex py-2 items-center">
                <p class="font-semibold text-sm w-full" style="text-align: center;">No Records Found</p>
              </div>
            @endif
          </div>
        </div> -->
       <!-- absent member end -->
      </div>

    </div>

  </div>
  
    <!-- end -->
  <!-- start -->
      <!-- funds start -->
      {{--<div>
        <h1 class="text-sm uppercase text-gray-800 font-semibold mb-2">Offering Collection</h1>
      
      <div class="flex flex-col lg:flex-row my-4">
        <!-- <div id="chartContainer" class="w-full lg:w-1/2 px-3 " style="height: 300px;" ></div> -->
        <div class="w-full lg:w-2/5 px-3 lg:mx-3">
          <ul class="w-full list-reset my-3 shadow">
              <!-- <p style="text-align: center; background-color: #FBFBDA"> Total Offerings = Rs . {{ number_format(20000+17000+12500+30000+11700+14000+16500+12000+35300+11700+18300+10200+15500+$total_fund,0) }}</p> -->
              <p style="text-align: center; background-color: #FBFBDA"> Total Offerings = Rs . {{ number_format($total_fund,0) }}</p>
            @if(count($fund) != 0)
              @foreach($fund as $funds)
                <li class=" py-2 px-2 flex justify-between items-center">
                  <div class="w-1/2 flex items-center">
                    @if($funds->membership=="member")
                      <a href="{{ url('/admin/member/show/'.$funds->user->name) }}" class="text-sm text-gray-800">{{ ucwords($funds->user->userprofile->firstname) }}</a>
                      <p class="fund-method text-white mx-2 rounded-full px-2 {{ ucwords($funds->method) }}">{{ ucwords($funds->method) }}</p>
                    @else
                       <a href="#" class="text-sm text-gray-800">{{ ucwords($funds[data]['first_name']) }}</a>
                      <p class="fund-method text-white mx-2 rounded-full px-2 {{ ucwords($funds->method) }}">{{ ucwords($funds->method) }}</p>
                    @endif
                  </div>
                  <div class=" w-1/2">
                    <p class="text-gray-700 text-sm font-semibold flex items-center justify-end">
                      <img src="{{asset('uploads/icons/rupee-indian.svg')}}" class="w-3 h-3 mx-1"> 
                      <span>{{ $funds->amount }}</span>
                    </p>
                  </div>
                </li>
              @endforeach
            @else
              <p class="text-gray-700 mx-2 rounded-full px-2">No Offerings Records Found</p>
            @endif
          </ul>
        </div>
      <!-- funds end -->
      </div>
      </div>--}}
  <!-- end -->

  <div class="w-full lg:w-1/4 px-3 my-2">
  <!-- birthday start -->
    <view-birthday url="{{url('/')}}"></view-birthday>
<!--birthday  end -->
<!-- anniversary start -->
    <!--<view-anniversary url="{{url('/')}}"></view-anniversary>-->
<!-- anniversary end -->
<!-- upcoming events start -->
   <!--  <div class="my-5">
      <h1 class="text-sm uppercase text-gray-800 font-semibold mb-2">Upcoming Events</h1>
      <event url="{{url('/')}}"></event>
    </div> -->
<!-- upcoming events end -->
<!-- sermons start -->
   <!--  <div class="my-5">
      <h1 class="text-sm uppercase text-gray-800 font-semibold mb-2">Meeting</h1>
      <sermon url="{{url('/')}}"></sermon>
    </div> -->
<!-- sermons end -->
  </div>
    </div>
</div>
<!-- end -->
@endsection

@push('scripts')

<!-- <script>
  window.onload = function () {

var chart = new CanvasJS.Chart("chartContainer", {
  animationEnabled: true,  
  title:{
    text: ""
  },
  axisY: {
    title: "",
    valueFormatString: "",
    suffix: "K",
    prefix: ""
  },
  data: [{
    type: "splineArea",
    color: "rgba(54,158,173,.7)",
    markerSize: 5,
    xValueFormatString: "",
    yValueFormatString: "$#,##0.##",
    dataPoints: [
      { y: 14, label: "Nov-10" },
      { y: 16, label: "Nov-11" },
      { y: 12, label: "Nov-14" },
      { y: 35, label: "Nov-15" },
      { y: 11, label: "Nov-17" },
      { y: 18, label: "Nov-18" },
      { y: 9, label:  "Nov-20" }
    ]
  }]
  });
chart.render();

}
</script> -->

<!-- <script>
  window.onload = function () {

var chart = new CanvasJS.Chart("chartContainer", {
  animationEnabled: true,  
  title:{
    text: ""
  },
  axisY: {
    title: "",
    valueFormatString: "",
    suffix: "K",
    prefix: ""
  },
  data: [{
    type: "column",
    color: "rgba(54,158,173,.7)",
    markerSize: 5,
    xValueFormatString: "",
    yValueFormatString: "Rs#,##0.##",
    dataPoints: [
      { y: 20000, label: "Jan-19" },
      { y: 17000, label: "Feb-19" },
      { y: 12500, label: "Mar-19" },
      { y: 30000, label: "Apr-19" },
      { y: 11700, label: "May-19" },
      { y: 14000, label: "Jun-19" },
      { y: 16500, label: "Jul-19" },
      { y: 12000, label: "Aug-19" },
      { y: 35300, label: "Sep-19" },
      { y: 11700, label: "Oct-19" },
      { y: 18300, label: "Nov-19" },
      { y: 10200, label: "Dec-19" },
      { y: 15500, label: "Jan-20" }
    ]
  }]
  });
chart.render();

}
</script> -->

@endpush