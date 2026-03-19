{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
    <div class="w-full">
        <div>
            <h1 class="admin-h1 my-3 flex items-center">
                <a href="{{ url('/admin/showvideo') }}" title="Back" class="rounded-full bg-gray-100 p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 492 492" xml:space="preserve" width="512px" height="512px" class="w-3 h-3 fill-current text-gray-700"><g><g><g><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124 c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844    L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412 c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008 c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788    C492,219.198,479.172,207.418,464.344,207.418z" data-original="#000000" fill="" class="active-path"></path></g></g></g></svg>
                </a>   
                <span class="mx-3">Media Files Upload</span>
            </h1>
        </div>

        @include('partials.message')
        <div class="bg-white shadow my-5">
            @if($count < $subscription->plan->no_of_videos)
                <div class="px-3 py-3 mx-2">
                    <form method="post" action="{{ url('/admin/videos') }}" enctype="multipart/form-data" id="media">
                        @csrf
                        <div>
                            <div>
                                <div class="flex">
                                    <div class="w-48 flex items-center py-2"> 
                                        <input type="radio" name="media" id="uploadmedia" value="uploadmedia" checked>
                                        <span class="text-sm mx-1">Media Upload</span>
                                    </div>
                                    <div class="w-48 flex items-center py-2"> 
                                        <input type="radio" name="media" id="studymedia" value="studymedia">
                                        <span class="text-sm mx-1">Study Material</span>
                                    </div>
                                    <div class="w-48 flex items-center py-2"> 
                                        <input type="radio" name="media" id="value_education" value="value_education">
                                        <span class="text-sm mx-1">Value Education</span>
                                    </div>
                                </div>
                                <span class="text-danger text-xs">{{$errors->first('media')}}</span>
                            </div>
                        </div>

                        <div class="hidden" id="standardlink">
                            <div class="my-3">
                                <div class="w-full lg:w-1/4">
                                    <label for="standardLink_id" class="tw-form-label">Select Class</label>
                                </div>
                                <div class="w-full lg:w-2/5 my-2">
                                    <select name="standardLink_id" class="tw-form-control w-full">
                                        <option value="">Select Class</option>
                                        @foreach($standardLinks as $standardLink)
                                            <option value="{{ $standardLink->id }}">{{ $standardLink->standard_section }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-red-500 text-xs font-semibold">{{$errors->first('standardLink_id')}}</span>
                                </div>
                            </div>
                        </div>

                        <div class="my-3">
                            <div class="">
                                <div class="w-full lg:w-1/4">
                                    <label for="name" class="tw-form-label">Title</label>
                                </div>
                                <div class="w-full lg:w-2/5 my-2">
                                    <input type="text" name="name" id="name" class="tw-form-control w-full" placeholder="Title">
                                    <span class="text-danger text-xs">{{$errors->first('name')}}</span>
                                </div>
                            </div>
                        </div>

                        <div class="my-3">
                            <div class="">
                                <div class="w-full lg:w-1/4">
                                    <label for="description" class="tw-form-label">Description</label>
                                </div>
                                <div class="w-full lg:w-2/5 my-2">
                                    <textarea type="text" name="description" id="description" class="tw-form-control w-full" rows="3" placeholder="Description"></textarea>
                                    <span class="text-danger text-xs">{{$errors->first('description')}}</span>
                                </div>
                            </div>
                        </div>

                        <div class="my-3">
                            <div class="">
                                <div class="w-full lg:w-1/4">
                                    <label class="tw-form-label">Media Type</label>
                                </div>
                                <div class="w-full lg:w-2/5 my-2">
                                    <select name="type" id="type" class="tw-form-control w-full" onchange="show(this.value)">
                                        <option value="">Select Media Type</option>
                                        <option value="video" id="video">Video</option>
                                        <option value="audio" id="audio">Audio</option>
                                        <option value="image" id="image">Image</option>
                                    </select> 
                                    <span class="text-danger text-xs my-1">{{$errors->first('type')}}</span>
                                </div>
                            </div>
                        </div>

                        <div class="w-full lg:w-1/2 md:w-1/2 lg:mr-8 md:mr-8 hidden" id="select_audio">
                            <div>
                                <div class="flex">
                                    <div class="w-1/2 lg:w-1/3 md:w-1/3 flex items-center tw-form-control mr-1 lg:mr-8 md:mr-8"> 
                                        <input type="radio" name="audio_type" id="attachaudio" value="attach">
                                        <span class="text-sm mx-1">Attach</span>
                                    </div>
                                    <div class="w-1/2 lg:w-1/3 md:w-1/3 flex items-center tw-form-control lg:mr-8 md:mr-8"> 
                                        <input type="radio" name="audio_type" id="recordaudio" value="record">
                                        <span class="text-sm mx-1">Record</span>
                                    </div>
                                </div>
                            </div>

                            <div id="attach">
                                <div class="my-6">
                                    <div class="w-full items-center"> 
                                        <input type="file" name="audiofile" class="tw-form-control w-full lg:w-3/4 md:w-3/4">
                                        <span class="text-danger text-xs">{{$errors->first('audiofile')}}</span>
                                    </div>
                                </div>
                            </div>

                            <div id="record" class="hidden" style="max-width: 28em;">
                                <div id="controls">
                                    <button id="recordButton">Record</button>
                                    <button id="stopButton" disabled>Stop</button>
                                </div> 
                            </div>
                        </div>

                        <div class="my-3 hidden" id="select_image">
                            <div class="">
                                <div class="w-1/4">
                                    <label class="tw-form-label">Select File</label>
                                </div>
                                <div class="my-2 w-2/5">
                                    <input type="file" name="url[]" id="url" multiple="multiple">
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
                        </div>

                        <div class="my-3 hidden" id="select_video">
                            <div class="flex">
                                <div class="w-1/2 lg:w-1/3 md:w-1/3 flex items-center tw-form-control mr-1 lg:mr-8 md:mr-8"> 
                                    <input type="radio" name="video_type" id="applyurl" value="url">
                                    <span class="text-sm mx-1">Url</span>
                                </div>
                                <div class="w-1/2 lg:w-1/3 md:w-1/3 flex items-center tw-form-control lg:mr-8 md:mr-8"> 
                                    <input type="radio" name="video_type" id="pastevideo" value="upload">
                                    <span class="text-sm mx-1">Upload</span>
                                </div>
                            </div>
                            <div class="" id="apply">
                                <div class="w-full lg:w-1/4">
                                    <label class="tw-form-label">URL</label>
                                </div>
                                <div class="my-2 w-full lg:w-2/5 flex flex-col">
                                    <input type="text" name="url" id="url" multiple="multiple" class="tw-form-control w-full">
                                    <span class="text-danger text-xs">{{$errors->first('url')}}</span>
                                </div>
                            </div>

                            <div id="upload_video" class="hidden">  
                                <add-video url="{{ url('/') }}" csrf="{{ csrf_token() }}"></add-video>
                            </div>
                        </div>

                        <div class="mt-6 mb-4">
                            <button class="btn btn-primary blue-bg text-white rounded px-3 py-1 text-sm font-medium" id="submit">Submit</button>
                        </div>
                    </form>
                </div>
            @else
                <a href="{{ url('/pricing') }}"> 
                    <button type="submit" class="no-underline text-white  px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center">Upgrade Plan to Add More Videos</button>
                </a>
            @endif
        </div>
    </div>
@endsection


@push('scripts')
    <link href="{{asset('css/audio-style.css')}}" rel="stylesheet" type="text/css" />
    <script src="{{asset('audio/WebAudioRecorder.min.js')}}"></script>
    <script src="{{asset('audio/app.js')}}"></script>
    <script type="text/javascript">
        var url="{{ url('/') }}";
        var posturl="{{ url('/admin/sessionsave') }}";
    </script>

    <script type="text/javascript">
        function show(val)
        {
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

        $('#uploadmedia').on('click', function(){
            $('#standardlink').removeClass('block').addClass('hidden');
        });

        $('#studymedia').on('click', function(){
            $('#standardlink').removeClass('hidden').addClass('block');
        });

        $('#value_education').on('click', function(){
            $('#standardlink').removeClass('hidden').addClass('block');
        });

        $('#attachaudio').on('click', function(){
            if($('#attach').hasClass('hidden'))
            {
                $('#attach').removeClass('hidden').addClass('block');
                $('#record').removeClass('block').addClass('hidden');
            }
        });

        $('#recordaudio').on('click', function(){
            if($('#record').hasClass('hidden'))
            {
                $('#record').removeClass('hidden').addClass('block');
                $('#attach').removeClass('block').addClass('hidden');
            }
        });

        $('#applyurl').on('click', function(){
            if($('#apply').hasClass('hidden'))
            {
                $('#apply').removeClass('hidden').addClass('block');
                $('#upload_video').removeClass('block').addClass('hidden');
            }
        });

        $('#pastevideo').on('click', function(){
            if($('#upload_video').hasClass('hidden'))
            {
                $('#upload_video').removeClass('hidden').addClass('block');
                $('#apply').removeClass('block').addClass('hidden');
            }
        });
    </script>
@endpush