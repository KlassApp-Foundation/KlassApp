{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
	<div class="relative">
    
      @include('partials.message')
      <list-postal-record url="{{ url('/') }}" mode="admin"></list-postal-record>
   </div>
@endsection