{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
	<div class="">
	    <portal-target name="student_homework_list"></portal-target>
	    @include('partials.message')
	    <show-homework url="{{ url('/') }}" id="{{ $homework->id }}" mode="teacher"></show-homework>  
	</div>
@endsection