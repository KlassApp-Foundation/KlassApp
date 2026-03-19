{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
	<div class="relative">
    
      @include('partials.message')
      <add-student-task url="{{ url('/') }}" searchquery="{{$query}}"></add-student-task>
   </div>
@endsection