{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
	<div class="relative">
      	@include('partials.message')
      	<add-task url="{{ url('/') }}" searchquery="{{ $query }}" mode="admin"></add-task>
   </div>
@endsection