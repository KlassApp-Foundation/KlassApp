{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')

<div class="w-full">
    <div >
      <h1 class="admin-h1 my-3 flex items-center">
       <a href="{{ url('/admin/showvideo') }}" title="Back" class="rounded-full bg-gray-100 p-2">
          <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 492 492" xml:space="preserve" width="512px" height="512px" class="w-3 h-3 fill-current text-gray-700"><g><g><g><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124    c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844    L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412    c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008    c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788    C492,219.198,479.172,207.418,464.344,207.418z" data-original="#000000" fill="" class="active-path"></path></g></g></g></svg>
      </a>
      
  <span class="mx-3">Edit Media Files Upload</span>
      </h1>
    </div>

    @include('partials.message')

    <form method="POST" action="{{url('/admin/videos/update/'.$videos->id)}}" enctype="multipart/form-data">
        @csrf

       
    	

  <!--radio button-->

   <div class="bg-white shadow px-4 py-3">
     
    <div >
      <div class="flex">
        <div class="w-48 flex items-center py-2"> 
          <input type="radio" name="media" id="uploadmedia" value="uploadmedia" {{$videos->media =='uploadmedia' ? 'checked':''}}>
          <span class="text-sm mx-1">Media Upload</span>
        </div>
        <div class="w-48 flex items-center py-2"> 
          <input type="radio" name="media" id="studymedia" value="studymedia" {{$videos->media =='studymedia' ? 'checked':''}}>
          <span class="text-sm mx-1">Study Material</span>
        </div>
      </div>
    </div>
   

  <!--radio button-->

  <!--class drop down-->
  <div class="hidden" id="study">
      <div class="my-3">
       
            <div class="w-full lg:w-1/4">
              <label for="standardLink_id" class="tw-form-label">Select Class</label>
            </div>
            <div class="w-full lg:w-2/5 my-2">
              <select name="standardLink_id" class="tw-form-control w-full" value="{{$videos->standardLink_id}}">
                <option value="">{{$videos->standardLink_id}}</option>
                @foreach($standardLinks as $standardLink)
                 <option value="{{ $standardLink->id }}">{{ $standardLink->StandardSection }}</option>
               @endforeach
              </select>
               <span class="text-red-500 text-xs font-semibold">{{$errors->first('standardLink_id')}}</span>
            </div>
          </div>
        </div>
    
  <!--end class drop down-->

   <div class="my-3">
         <div class="">
          <div class="w-full lg:w-1/4">
            <label for="name" class="tw-form-label">Title</label>
            </div>
            <div class="w-full lg:w-2/5 my-2">
               <input type="text" name="name" id="name" class="tw-form-control w-full" value="{{$videos->name}}">
                 <span class="text-danger text-xs">{{$errors->first('name')}}</span>
              
             </div>
      </div>
  </div>

   <div class="my-3">
       <div class="">
           <div class="w-full lg:w-1/4">
           <label class="tw-form-label ">Description</label>
           </div>
           <div class="w-full lg:w-2/5 my-2">
               <textarea type="textarea" name="description" id="description" class="tw-form-control w-full" rows="3">{{$videos->description}} </textarea>
                <span class="text-danger text-xs">{{$errors->first('description')}}</span>
           </div>
       </div>
   </div>

      <!--radio button-->

   <div>
     
    <div>
      <div class="flex">
        <div class="w-1/4 lg:w-1/4">
           <label class="tw-form-label ">Value Education</label>
           </div>
        <div class="w-48 flex items-center py-2"> 
          <input type="radio" name="value_education" id="yes" value="yes" {{$videos->value_education =='yes' ? 'checked':''}}>
          <span class="text-sm mx-1">Yes</span>
        </div>
        <div class="w-48 flex items-center py-2"> 
          <input type="radio" name="value_education" id="no" value="no" {{$videos->value_education =='no' ? 'checked':''}}>
          <span class="text-sm mx-1">No</span>
        </div>
         
      </div>
        <span class="text-danger text-xs">{{$errors->first('value_education')}}</span>
    </div>
    </div>

  <!--radio button-->

<div class="my-3">
        <div class="">
        <div class="w-full lg:w-1/4">
            <label class="tw-form-label ">Type</label>
        </div>
        <div class="w-full lg:w-2/5 my-2">

        <select name="type" id="type" class="tw-form-control w-full" value="{{$videos->type}}" onchange="show(this.value)" >

            <option value="{{$videos->type}}">{{$videos->type}} </option>
             <option value="video" id="video">Video</option>
              <option value="audio" id="audio">Audio</option>
              <option value="image" id="image">Image</option>
              </select> 
               <span class="text-danger text-xs my-1">{{$errors->first('type')}}</span>
              </div>

          </div>
   </div>

<!--audio start-->

  <div class="w-1/2 lg:mr-8 md:mr-8 hidden" id="select_audio">


    <div>
      <div class="mb-2">
        <label for="audio" class="tw-form-label">Audio</label>
      </div>
      <div class="flex">
        <div class="w-1/3 flex items-center tw-form-control lg:mr-8 md:mr-8"> 
          <input type="radio" name="audio" id="attachaudio" value="attachaudio" >
          <span class="text-sm mx-1">Attach</span>
        </div>
        <div class="w-1/3 flex items-center tw-form-control lg:mr-8 md:mr-8"> 
          <input type="radio" name="audio" id="recordaudio" value="recordaudio">
          <span class="text-sm mx-1">Record</span>
        </div>
      </div>
    </div>
   <!-- <button class="capitalize text-white blue-bg rounded px-2 py-1 ml-2 font-medium" onclick="showAttach()">Attach Audio</button> -->
   <div id="attach">
      {{--<form method="POST" action="{{url('/admin/prayer_requests/audio/store/'.$prayer->id)}}" enctype="multipart/form-data">--}}
        @csrf
        <div class="my-6">
          <div class="w-full items-center"> 
            <input type="file" name="audiofile" class="tw-form-control w-3/4">
            <span class="text-danger text-xs">{{$errors->first('audiofile')}}</span>
          </div>
        </div>
      <!--   <div class="my-6">
          <input type="submit" value="Submit" name="submit" dusk="submit" class="btn btn-primary submit-btn cursor-pointer">
        </div> -->
      {{--</form>--}}
   </div>

  <div id="record" class="hidden" style="max-width: 28em;">
    <!-- <p>Convert recorded audio to:</p> -->
    <select id="encodingTypeSelect" style="display:none">
      <option value="mp3">MP3 (MPEG-1 Audio Layer III) (.mp3)</option>
      <option value="wav">Waveform Audio (.wav)</option>
    
      <option value="ogg">Ogg Vorbis (.ogg)</option>
    </select>
    <div id="controls">
      <button id="recordButton">Record</button>
      <button id="stopButton" disabled>Stop</button>
    </div>
    <div id="formats" style="display:none"></div>
  {{--   <pre>Log</pre> --}}
    <pre id="log" style="display:none"></pre>

   {{--  <pre>Recordings</pre> --}}
    <ol id="recordingsList" ></ol>
  </div>
  </div>

<!--audio end-->
<!--image-->

<div class="my-3 hidden" id="select_image">

       <div class="">
             <div class="w-1/4">
             <label class="tw-form-label ">Select File</label>
             </div>
             <div class="my-2 w-2/5">
               <input type="file" name="url[]" id="url" multiple="multiple" value="{{$videos->url}}">
            </div>
              <span class="text-danger text-xs">{{$errors->first('file')}}</span>
               @foreach($errors->all() as $error)
                 @if($error=="File extension error")
                 <div>
                   <span class="text-danger text-xs">{{$error}}</span>
                  </div>
                 @endif
               @endforeach
   </div>

   <div class="relative flex items-center">
    <img src="{{ $videos->AttachmentPath}}" class="w-10 h-10">
   </div>
</div>
<!--image-->





   <div class="my-3 hidden" id="select_video">
       <div class="">
             <div class="w-full lg:w-1/4">
             <label class="tw-form-label ">URL</label>
             </div>
             <div class="my-2 w-full lg:w-2/5 flex flex-col">
               <input type="text" name="url" id="url" multiple="multiple" class="tw-form-control w-full" value="{{$videos->url}}">
               <span class="text-danger text-xs">{{$errors->first('url')}}</span>
            </div>
       </div>
   </div>


   <div class="mt-6 mb-4">
       <button class="btn btn-primary blue-bg text-white rounded px-3 py-1 text-sm font-medium" id="submit">Submit</button>
   </div>
    </div>
@endsection


@push('scripts')

 <style type='text/css'>
    ul { list-style: none; }
    #recordingslist audio { display: block; margin-bottom: 10px; }
  </style>
   <link href="{{asset('css/audio-style.css')}}" rel="stylesheet" type="text/css" />
  <script src="{{asset('audio/WebAudioRecorder.min.js')}}"></script>
  <script src="{{asset('audio/app.js')}}"></script>
<script type="text/javascript">
  var url="{{ url('/') }}";
 // var posturl = $('#media').attr('action');
 var posturl="{{url('/admin/sessionsave')}}";
</script>

<script type="text/javascript">//audio
  function show(val){
   
    if(val=='audio')
    {
      $('#select_audio').removeClass('hidden').addClass('block');
      $('#select_video').removeClass('block').addClass('hidden');
      $('#select_image').removeClass('block').addClass('hidden');
    }

    else if(val=='video')
    {
      $('#select_video').removeClass('hidden').addClass('block');
      $('#select_audio').removeClass('block').addClass('hidden');
      $('#select_image').removeClass('block').addClass('hidden');
    }
    else if(val=='image')
    {
      $('#select_image').removeClass('hidden').addClass('block');
      $('#select_audio').removeClass('block').addClass('hidden');
      $('#select_video').removeClass('block').addClass('hidden');
    }
  }
</script>

<!-- <script type="text/javascript">//video
  $('#video').on('click', function(){
    if($('#select_video').hasClass('hidden'))
    {
      $('#select_video').removeClass('hidden').addClass('block');
      $('#select_audio').removeClass('block').addClass('hidden');
    }
  });
</script> -->

<!--url edit-->

<script type="text/javascript">
  var typeValue = document.getElementById("type").value;
  //console.log(typeValue)
  if(typeValue=='audio')
    {
      $('#select_audio').removeClass('hidden').addClass('block');
      $('#select_video').removeClass('block').addClass('hidden');
      $('#select_image').removeClass('block').addClass('hidden');
    }
    else if(typeValue=='video')
    {
      $('#select_video').removeClass('hidden').addClass('block');
      $('#select_audio').removeClass('block').addClass('hidden');
      $('#select_image').removeClass('block').addClass('hidden');
    }
    else if(typeValue=='image')
    {
      $('#select_image').removeClass('hidden').addClass('block');
      $('#select_audio').removeClass('block').addClass('hidden');
      $('#select_video').removeClass('block').addClass('hidden');
    }
</script>







<!--end url-->

<script type="text/javascript">
  $('#uploadmedia').on('click', function(){
   
      $('#upload').removeClass('hidden').addClass('block');
      $('#study').removeClass('block').addClass('hidden');
    
  });
</script>

<script type="text/javascript">
  $('#studymedia').on('click', function(){
   
      $('#study').removeClass('hidden').addClass('block');
      $('#upload').removeClass('block').addClass('hidden');
    
  });
</script>
 
<script type="text/javascript">
  $('#attachaudio').on('click', function(){
    if($('#attach').hasClass('hidden'))
    {
      $('#attach').removeClass('hidden').addClass('block');
      $('#record').removeClass('block').addClass('hidden');
    }
  });
</script>

<script type="text/javascript">
  $('#recordaudio').on('click', function(){
    if($('#record').hasClass('hidden'))
    {
      $('#record').removeClass('hidden').addClass('block');
      $('#attach').removeClass('block').addClass('hidden');
    }
  });
</script>
@endpush
 <style>
 .text-danger
 {
  color:red;
 }
 </style>