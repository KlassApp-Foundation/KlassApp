{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.reception.layout')

@section('content')
	<div class="relative">
    
      @include('partials.message')
      <list-call-log url="{{ url('/') }}" mode="receptionist"></list-call-log>
   </div>
@endsection