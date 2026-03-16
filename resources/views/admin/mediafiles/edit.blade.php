{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')

    <div class="w-full">
        <div>
            <h1 class="admin-h1 my-3 flex items-center">
                <a href="{{ url('/admin/files') }}" title="Back" class="rounded-full bg-gray-100 p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 492 492" xml:space="preserve" width="512px" height="512px" class="w-3 h-3 fill-current text-gray-700"><g><g><g><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124 c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844    L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412 c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008 c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788    C492,219.198,479.172,207.418,464.344,207.418z" data-original="#000000" fill="" class="active-path"></path></g></g></g></svg>
                </a>
                <span class="mx-3">Edit Media Files Upload</span>
            </h1>
        </div>

        @include('partials.message')

        <form method="POST" action="{{ url('/admin/file/edit/'.$videos->id) }}" enctype="multipart/form-data">
            @csrf
            <div class="bg-white shadow px-4 py-3">
                <div>
                    <div class="mb-2">
                        <label for="media" class="tw-form-label">Media Category<span class="text-red-500">*</span></label>
                    </div>
                    <div class="flex">
                        <div class="w-48 flex items-center py-2"> 
                            <input type="radio" name="media" id="uploadmedia" value="media_upload" {{$videos->media =='media_upload' ? 'checked':''}}>
                            <span class="text-sm mx-1">Media Upload</span>
                        </div>
                        <div class="w-48 flex items-center py-2"> 
                            <input type="radio" name="media" id="studymedia" value="study_material" {{$videos->media =='study_material' ? 'checked':''}}>
                            <span class="text-sm mx-1">Study Material</span>
                        </div>
                        <div class="w-48 flex items-center py-2"> 
                            <input type="radio" name="media" id="value_education" value="value_education" {{$videos->media =='value_education' ? 'checked':''}}>
                            <span class="text-sm mx-1">Value Education</span>
                        </div>
                    </div>
                </div>

                <div class="hidden" id="standardlink">
                    <div class="my-3">
                        <div class="w-full lg:w-1/4">
                            <label for="standardLink_id" class="tw-form-label hidden" id="study_standard">Select Class<span class="text-red-500">*</span></label>
                            <label for="standardLink_id" class="tw-form-label" id="value_standard">Select Class</label>
                        </div>
                        <div class="w-full lg:w-2/5 my-2">
                            <select name="standardLink_id" class="tw-form-control w-full" value="{{ $videos->standardLink_id }}">
                                <option value="">Select Class</option>
                                @foreach($standardLinks as $standardLink)
                                    <option value="{{ $standardLink->id }}" {{ $videos->standardLink_id == $standardLink->id ? 'selected="selected"' : '' }}>{{ $standardLink->StandardSection }}</option>
                                @endforeach
                            </select>
                            <span class="text-red-500 text-xs font-semibold">{{$errors->first('standardLink_id')}}</span>
                        </div>
                    </div>
                </div>

                <div class="my-3">
                    <div class="">
                        <div class="w-full lg:w-1/4">
                            <label for="name" class="tw-form-label">Title<span class="text-red-500">*</span></label>
                        </div>
                        <div class="w-full lg:w-2/5 my-2">
                            <input type="text" name="name" id="name" class="tw-form-control w-full" value="{{ $videos->name }}" placeholder="Title">
                            <span class="text-danger text-xs">{{ $errors->first('name') }}</span>
                        </div>
                    </div>
                </div>

                <div class="my-3">
                    <div class="">
                        <div class="w-full lg:w-1/4">
                            <label class="tw-form-label">Description<span class="text-red-500">*</span></label>
                        </div>
                        <div class="w-full lg:w-2/5 my-2">
                            <textarea type="textarea" name="description" id="description" class="tw-form-control w-full" rows="3" placeholder="Description">{{ $videos->description }}</textarea>
                            <span class="text-danger text-xs">{{ $errors->first('description') }}</span>
                        </div>
                    </div>
                </div>

                <div class="my-3">
                    <div class="">
                        <div class="w-full lg:w-1/4">
                            <label class="tw-form-label">Type<span class="text-red-500">*</span></label>
                        </div>
                        <div class="w-full lg:w-2/5 my-2">
                            <select name="type" id="type" class="tw-form-control w-full" value="{{$videos->type}}" onchange="show(this.value)" >
                                <option value="">Select Type</option>
                                <option value="video" id="video" {{ $videos->type == 'video' ? 'selected="selected"' : '' }}>Video</option>
                                <option value="audio" id="audio" {{ $videos->type == 'audio' ? 'selected="selected"' : '' }}>Audio</option>
                                <option value="image" id="image" {{ $videos->type == 'image' ? 'selected="selected"' : '' }}>Image</option>
                            </select> 
                            <span class="text-danger text-xs my-1">{{ $errors->first('type') }}</span>
                        </div>
                    </div>
                </div>

                <div class="my-3 hidden" id="select_audio">
                    <div>
                        <div class="mb-2">
                            <label for="audio_type" class="tw-form-label">Audio<span class="text-red-500">*</span></label>
                        </div>
                        <div class="flex w-full lg:w-2/5 md:w-2/5">
                            <div class="w-1/2 lg:w-1/2 md:w-1/2 flex items-center tw-form-control lg:mr-8 md:mr-8"> 
                                <input type="radio" name="audio_type" id="attachaudio" value="attach" {{ $videos->media_type =='attach' ? 'checked':''}}>
                                <span class="text-sm mx-1">Attach</span>
                            </div>
                            <div class="w-1/2 lg:w-1/2 md:w-1/2 flex items-center tw-form-control"> 
                                <input type="radio" name="audio_type" id="recordaudio" value="record" {{ $videos->media_type =='record' ? 'checked':''}}>
                                <span class="text-sm mx-1">Record</span>
                            </div>
                        </div>
                    </div>

                    <div id="attach">
                        <div class="my-6">
                            <div class="w-full lg:w-1/4">
                                <label class="tw-form-label">Attach<span class="text-red-500">*</span></label>
                            </div>
                            <div class="w-full items-center"> 
                                <input type="file" name="audiofile" class="tw-form-control w-2/5">
                                <span class="text-danger text-xs">{{$errors->first('audiofile')}}</span>
                            </div>
                        </div>
                    </div>

                    <div id="record" class="hidden" style="max-width: 28em;">
                        <div style="max-width: 28em; display:none;"> <!-- Do not remove this div.If removed recording won't work -->
                            <p>Convert recorded audio to:</p>
                            <select id="encodingTypeSelect" style="display:none">
                                <option value="mp3">MP3 (MPEG-1 Audio Layer III) (.mp3)</option>
                                <option value="wav">Waveform Audio (.wav)</option>
                                <option value="ogg">Ogg Vorbis (.ogg)</option>
                            </select>
                            <div id="formats" style="display:none"></div>
                            {{--   <pre>Log</pre> --}}
                            <pre id="log" style="display:none"></pre>
                        </div>
                        <div id="controls">
                            <button id="recordButton">Record</button>
                            <button id="stopButton" disabled>Stop</button>
                        </div>
                    </div>
                </div>

                <div class="my-3 hidden" id="select_image">
                    <div class="">
                        <div class="w-1/4">
                            <label class="tw-form-label ">Select Images<span class="text-red-500">*</span></label>
                        </div>
                        <div class="my-2 w-2/5">
                            <input type="file" name="images[]" id="images" multiple="multiple" value="{{ $videos->url }}">
                        </div>
                        <span class="text-danger text-xs">{{$errors->first('file')}}</span>
                        @foreach($errors->all() as $error)
                            @if($error=="File Extension Error. Select jpg,jpeg,png Files")
                                <div>
                                    <span class="text-danger text-xs">{{$error}}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="relative flex items-center">
                        <img src="{{ $videos->AttachmentPath }}" class="w-25 h-25">
                    </div>
                </div>

                <div class="my-3 hidden" id="select_video">
                    <div class="my-3">
                        <div class="w-full lg:w-1/4">
                            <label class="tw-form-label">Video Type<span class="text-red-500">*</span></label>
                        </div>
                    </div>
                    <div class="flex w-full lg:w-2/5 md:w-2/5">
                        <div class="w-1/2 lg:w-1/2 md:w-1/2 flex items-center tw-form-control mr-1 lg:mr-8 md:mr-8"> 
                            <input type="radio" name="video_type" id="applyurl" value="url" {{ $videos->media_type == 'url' ? 'checked' : ''}}>
                            <span class="text-sm mx-1">Url</span>
                        </div>
                        <div class="w-1/2 lg:w-1/2 md:w-1/2 flex items-center tw-form-control"> 
                            <input type="radio" name="video_type" id="pastevideo" value="upload" {{ $videos->media_type == 'upload' ? 'checked' : ''}}>
                            <span class="text-sm mx-1">Upload</span>
                        </div>
                    </div>
                    <span class="text-danger text-xs">{{ $errors->first('video_type') }}</span>
                    <div class="" id="apply">
                        <div class="my-6">
                            <div class="w-full lg:w-1/4">
                                <label class="tw-form-label">URL<span class="text-red-500">*</span></label>
                            </div>
                            <div class="my-2 w-full lg:w-2/5 flex flex-col">
                                <input type="text" name="url" id="url" class="tw-form-control w-full" value="{{ $videos->url }}">
                                <span class="text-danger text-xs">{{$errors->first('url')}}</span>
                            </div>
                        </div>
                    </div>

                    <div id="upload_video" class="hidden">  
                        <add-video url="{{ url('/') }}" csrf="{{ csrf_token() }}"></add-video>
                        <span class="text-danger text-xs">{{ $errors->first('uploadvideo') }}</span>
                    </div>
                </div>

                <div class="mt-6 mb-4">
                    <button class="btn btn-primary blue-bg text-white rounded px-3 py-1 text-sm font-medium" id="submit">Submit</button>
                </div>
            </div>
        </form>
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
            $('#study_standard').removeClass('hidden').addClass('block');
            $('#value_standard').removeClass('block').addClass('hidden');
        });

        $('#value_education').on('click', function(){
            $('#standardlink').removeClass('hidden').addClass('block');
            $('#value_standard').removeClass('hidden').addClass('block');
            $('#study_standard').removeClass('block').addClass('hidden');
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

        $(document).ready(function(){
            var type = document.getElementById("type");
            show(type.value);

            var media = {!! json_encode($videos->media) !!};

            if(media == 'media_upload')
            {
                $('#standardlink').removeClass('block').addClass('hidden');
            }

            if(media == 'study_material')
            {
                $('#standardlink').removeClass('hidden').addClass('block');
                $('#study_standard').removeClass('hidden').addClass('block');
                $('#value_standard').removeClass('block').addClass('hidden');
            }

            if(media == 'value_education')
            {
                $('#standardlink').removeClass('hidden').addClass('block');
                $('#value_standard').removeClass('hidden').addClass('block');
                $('#study_standard').removeClass('block').addClass('hidden');
            }

            var attachaudio = document.getElementById("attachaudio");
            if(attachaudio.checked)
            {
                $('#attach').removeClass('hidden').addClass('block');
                $('#record').removeClass('block').addClass('hidden');
            }

            var recordaudio = document.getElementById("recordaudio");
            if(recordaudio.checked)
            {
                $('#record').removeClass('hidden').addClass('block');
                $('#attach').removeClass('block').addClass('hidden');
            }

            var applyurl = document.getElementById("applyurl");
            if(applyurl.checked)
            {
                $('#apply').removeClass('hidden').addClass('block');
                $('#upload_video').removeClass('block').addClass('hidden');
            }

            var pastevideo = document.getElementById("pastevideo");
            if(pastevideo.checked)
            {
                $('#upload_video').removeClass('hidden').addClass('block');
                $('#apply').removeClass('block').addClass('hidden');
            }
        });
    </script>
@endpush