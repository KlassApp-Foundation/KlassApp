{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.accountant.layout')
@section('content')
    <div class="flex items-center justify-between">
        <h1 class="accountant-h1 my-3">Feed</h1>

        <a href="{{ url('/accountant/feeds') }}" class="no-underline text-white px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center">
        <span class="mx-1 text-sm font-semibold">Clear Filter</span>
        </a> 
    </div>
            
<div class="py-2">
<div class="flex flex-wrap justify-between">
  <form method="GET" action="{{url('/accountant/feed/filter')}}" role="search" enctype="multipart/form-data">
  <div class="w-full lg:w-40 md:w-40">
  <div>
    <p class="font-semibold text-red-600">Filter By Source</p>

    <ul class="list-reset leading-loose py-2">
      <li class="text-sm py-1"><a href="{{url('/accountant/feed/filter?search=achievement')}}">Achivements / Kudos</a></li>
      <li class="text-sm py-1"><a href="{{url('/accountant/feed/filter?search=birthday')}}" value="birthday">Birthdays</a></li>
      <li class="text-sm py-1"><a href="{{url('/accountant/feed/filter?search=workanniversary')}}" value="workanniversary">Work Anniversaries</a></li>
      <li class="text-sm py-1"><a href="{{url('/accountant/feed/filter?search=teacher')}}" value="teacher">By Teacher / Staff</a></li>
      <li class="text-sm py-1"><a href="{{url('/accountant/feed/filter?search=student')}}" value="student">By Students</a></li>

    </ul>
  </div>
  <div class="py-3">
    <p class="font-semibold text-red-600">Filter By Source</p>

    <ul class="list-reset leading-loose py-2">
      <li class="text-sm py-1"><a href="{{url('/accountant/feed/filter?list=school')}}" value="school">School</a></li>
      <li class="text-sm py-1"><a href="{{url('/accountant/feed/filter?list=all_class')}}" value="all_class">All Classes</a></li>
      <li class="text-sm py-1"><a href="{{url('/accountant/feed/filter?list=select_class')}}" value="select_class">My Class Only</a></li>

    </ul>
  </div>
  </div>
    </form>
  <div class="w-full lg:w-2/3 md:w-full">
   
  <div class="relative">
    
      @include('partials.message')
       <add-post url="{{ url('/') }}" entity_id="{{ $entity_id }}" entity_name="{{ $entity_name }}" mode="accountant"></add-post>

   </div>
 @if(count($feeds)!=NULL)
    <div class="my-3 bg-white px-3 py-3" style="height: 700px;overflow: auto;">
    <!-- feed template -->
  
      @foreach($feeds as $feed)
    <div class="">
  
      <div class="relative border rounded py-2 px-2">
      @if($feed->tag=='Birthday')
          <img src="{{$birthday}}" class="w-full rounded h-64">
      @elseif($feed->tag=='Workanniversary')
        <img src="{{$anniversary}}" class="w-full rounded h-64">
      @else
          <img src="{{$feed->CoverPath}}" class="w-full rounded h-64">
      @endif
          <div>
          
            <p class="py-3 font-semibold">{{$feed->description}}</p>
          </div>
        </div>
    
      <div> 
      <show-feed url="{{ url('/') }}" id="{{ $feed->id }}" mode="accountant" auth_id="{{ \Auth::id() }}"></show-feed> 
        
      </div>  
    </div>
@endforeach
 
    <!-- feed template -->

  
    </div>
     @else
      <div class="bg-white shadow border-b-8 p-4">
        <p class="flex items-center">No Records Found</p>
      </div>
    @endif

  </div>
  <div class="w-full lg:w-1/6 md:w-full">
    <div class="py-3 bg-white border border-gray-400 rounded px-3">
        <p class="font-semibold text-red-600">Guidelines for posting</p>
        <ul class="list-disc text-xs leading-loose px-4 py-2">
          <li class="py-1">Lorem Ipsum is simply dummy text</li>
          <li class="py-1">Various versions have evolved over the years</li>
          <li class="py-1">Lorem Ipsum is simply dummy text</li>
          <li class="py-1">Various versions have evolved over the years</li>
          <li class="py-1">Lorem Ipsum is simply dummy text</li>
        </ul>
    </div>
    <div class="py-3 bg-white border border-gray-400 rounded px-3 my-2">
        <p class="font-semibold text-red-600">Tags</p>
        <ul class="flex flex-wrap text-sm">
        @foreach($tags as $tag)
          @if($tag->tag_name!=NULL)
          <li class="px-2 py-1 border mr-1 my-1"><a href="{{url('/accountant/feeds?search='.$tag->tag_name)}}">{{$tag->tag_name}}</a>({{$tag->getTag($tag->tag_name)}})</li>
          @endif
        @endforeach
        </ul>
    </div>
  </div>
  </div>


</div>
@endsection

@push('scripts')
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.6/js/bootstrap.min.js"></script> -->
<script src="https://cdn.jsdelivr.net/bootstrap.tagsinput/0.4.2/bootstrap-tagsinput.min.js"></script>
<!--  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" rel="stylesheet"> -->
 <link href="https://cdn.jsdelivr.net/bootstrap.tagsinput/0.4.2/bootstrap-tagsinput.css" rel="stylesheet">
<script>
$(document).ready(function() {

  $('input[name="input"]').tagsinput({
    trimValue: true,
    confirmKeys: [13, 44, 32],
    focusClass: 'my-focus-class'
  });
  
  $('.bootstrap-tagsinput input').on('focus', function() {
    $(this).closest('.bootstrap-tagsinput').addClass('has-focus');
  }).on('blur', function() {
    $(this).closest('.bootstrap-tagsinput').removeClass('has-focus');
  });
  
});

</script>
<style>
.create-post-overlay {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.7);
  transition: opacity 500ms;
  visibility: hidden !important;
  opacity: 0;
}
.create-post-overlay:target {
  visibility: visible !important;
  opacity: 1;
}
.create-post-popup {
  margin: 70px auto;
  /*padding: 20px;*/
  background: #fff;
  border-radius: 5px;
  width: 30%;
  position: relative;
  transition: all 2s ease-in-out;
}
@media(max-width: 997px) {
  .create-post-popup {
    width: 80%;
  }
}

/**********/
/*add tag css*/
.bootstrap-tagsinput {
  margin: 0;
  width: 100%;
  padding: 0.5rem 0.75rem 0;
  font-size: 1rem;
  line-height: 1.25;
  transition: border-color 0.15s ease-in-out;
  font-size: 0.875em;
}
.bootstrap-tagsinput.has-focus {
  background-color: #fff;
  border-color: #5cb3fd;
}
.bootstrap-tagsinput .label-info {
  display: inline-block;
  background-color: #636c72;
  padding: 0 .4em .15em;
  border-radius: .25rem;
  margin-bottom: 0.4em;
}
.bootstrap-tagsinput input {
  margin-bottom: 0.5em;
}

.bootstrap-tagsinput .tag [data-role="remove"]:after {
  content: '\00d7';
}
/*add tag css*/
/**********/

</style>

@endpush