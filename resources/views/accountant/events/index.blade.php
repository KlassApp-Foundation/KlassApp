{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.accountant.layout')

@section('content')
    <div class="py-5">
        <show-event url="{{ url('/') }}" count="{{ $count }}" no_of_events="{{ $subscription->plan->no_of_events }}" events="{{ $events }}" mode="accountant"></show-event>
    </div>
    <event-popup url="{{ url('/') }}" mode="accountant"></event-popup>
    <portal-target name="eventpopup"></portal-target></div>
@endsection