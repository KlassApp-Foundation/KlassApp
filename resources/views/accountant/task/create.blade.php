{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.accountant.layout')

@section('content')
	<div class="relative">
      	@include('partials.message')
      	<add-task url="{{ url('/') }}" searchquery="{{ $query }}" mode="accountant"></add-task>
   </div>
@endsection