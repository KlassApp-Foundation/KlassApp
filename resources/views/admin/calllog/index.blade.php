{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
	<div class="relative">
    
      @include('partials.message')
      <list-call-log url="{{ url('/') }}" mode="admin"></list-call-log>
   </div>
@endsection