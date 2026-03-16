{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
	<div class="relative">
    
      @include('partials.message')
      <list-teacher-postal-record url="{{ url('/') }}"  mode="teacher"></list-teacher-postal-record>
   </div>
@endsection