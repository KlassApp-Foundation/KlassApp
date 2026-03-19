{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <create-event url="{{url('/')}}" count="{{ $count }}" no_of_events="{{ $subscription->plan->no_of_events }}" standard="{{$standard}}"></create-event>
    <edit-event url="{{url('/')}}"></edit-event>
    <div class="py-5">
        <show-event url="{{url('/')}}" count="{{ $count }}" no_of_events="{{ $subscription->plan->no_of_events }}" events="{{ $events }}"></show-event>
    </div>
    <event-popup :url="this.url" mode="admin"></event-popup>
    <portal-target name="eventpopup"></portal-target>
@endsection