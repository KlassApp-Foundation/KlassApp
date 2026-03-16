{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
  	<portal-target name="approve_leave"></portal-target>
  	@include('partials.message')
  	@if($user_type == 'apply')
    	<leave-teacher-list url="{{ url('/') }}" type="{{ $user_type }}"></leave-teacher-list>
  	@elseif($user_type == 'check')
    	<leave-teacher-list url="{{ url('/') }}" type="{{ $user_type }}" searchquery="{{ $query }}"></leave-teacher-list>
  	@endif
@endsection