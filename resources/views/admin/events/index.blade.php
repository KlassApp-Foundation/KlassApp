{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Calendar',
    'subtitle' => 'Schedule and manage school events, holidays, and important dates.',
    'actions' => '<span class="text-sm text-gray-500 font-medium">' . $count . ' events</span>'
])

<div class="relative mt-4">
    <create-event url="{{url('/')}}" count="{{ $count }}" no_of_events="{{ $subscription->plan->no_of_events }}" standard="{{$standard}}"></create-event>
    <edit-event url="{{url('/')}}"></edit-event>
    <div class="py-5">
        <show-event url="{{url('/')}}" count="{{ $count }}" no_of_events="{{ $subscription->plan->no_of_events }}" events="{{ $events }}"></show-event>
    </div>
    <event-popup :url="this.url" mode="admin"></event-popup>
    <portal-target name="eventpopup"></portal-target>
@endsection