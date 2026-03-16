{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
    <div class="py-5">
        <show-event url="{{url('/')}}" count="{{ $count }}" no_of_events="{{ $subscription->plan->no_of_events }}" events="{{ $events }}"></show-event>
    </div>
    <event-popup :url="this.url" mode="teacher"></event-popup>
    <portal-target name="eventpopup"></portal-target>
@endsection