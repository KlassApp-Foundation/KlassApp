{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
	<!-- <div class="relative">
      	<div class="flex flex-wrap lg:flex-row justify-between">
         	<div class="">
            	<h1 class="admin-h1 my-3">Notifications</h1>
         	</div>
      	</div>
      	@include('partials.message') -->
      	<notification-list url="{{ url('/') }}" mode="teacher"></notification-list>
   	<!-- </div> -->
@endsection