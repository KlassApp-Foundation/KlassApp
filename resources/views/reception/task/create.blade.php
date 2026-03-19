{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.reception.layout')

@section('content')
	<div class="relative">
    
      @include('partials.message')
      <add-task url="{{ url('/') }}" searchquery="{{$query}}" mode="receptionist"></add-task>
   </div>
@endsection