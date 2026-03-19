{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.full-width-layout')


@section('content')
    <div class="container mx-auto p-4">
        <div class="flex justify-between my-2">
            <h1 class="text-2xl">Live Stream</h1>
            <button id="start" type="submit" class="btn bg-blue-700 rounded px-3 py-2 text-white">Start Playback</button>
        </div>
        <div id="playerContainer">
            <video id="dashjs" class="player" controls autoplay="" ></video>
        </div>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.dashjs.org/latest/dash.all.min.js"></script>
<script>
    $('#start').click(function() {
        console.log('Start button clicked');


        kinesisVideoArchivedContent.getDASHStreamingSessionURL({
            StreamName: streamName,
            PlaybackMode: $('#playbackMode').val(),
            DASHFragmentSelector: {
            FragmentSelectorType: $('#fragmentSelectorType').val(),
            TimestampRange: $('#playbackMode').val() === "LIVE" ? undefined : {
            StartTimestamp: new Date($('#startTimestamp').val()),
            EndTimestamp: new Date($('#endTimestamp').val())
                }
        },
        DisplayFragmentTimestamp: $('#displayFragmentTimestamp').val(),
        DisplayFragmentNumber: $('#displayFragmentNumber').val(),
        MaxManifestFragmentResults: parseInt($('#maxResults').val()),
        Expires: parseInt($('#expires').val())
        }

        var playerElement = $('#dashjs');
        var player = dashjs.MediaPlayer().create();
        player.initialize(document.querySelector('#dashjs'), response.DASHStreamingSessionURL, true);

        console.log('Created DASH.js Player');
    });
</script>
@endpush

