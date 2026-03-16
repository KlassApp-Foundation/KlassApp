{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('content')
  <div style="max-width: 28em;">
    <p>Convert recorded audio to:</p>
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
@endsection 
@push('scripts')
 <style type='text/css'>
    ul { list-style: none; }
    #recordingslist audio { display: block; margin-bottom: 10px; }
  </style>
   <link href="{{url('css/audio-style.css')}}" rel="stylesheet" type="text/css" />
  <script src="{{url('audio/WebAudioRecorder.min.js')}}"></script>
  <script src="{{url('audio/app.js')}}"></script>
@endpush