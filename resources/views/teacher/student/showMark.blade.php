{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
	<div class="">
	    <h1 class="admin-h1 my-3 flex items-center">
	      <span class="">Mark Sheet</span>
	    </h1>
	    @include('partials.message')
	    <show-mark user_id="{{ $user_id }}" standard_id="{{ $standard_id }}" mode="teacher"></show-mark>  
	</div>
@endsection