{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
	<div class="relative">
    
      @include('partials.message')
      <list-teacher-call-log url="{{ url('/') }}"  mode="teacher"></list-teacher-call-log>
   </div>
@endsection