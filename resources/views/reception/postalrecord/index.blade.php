{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.reception.layout')

@section('content')
	<div class="relative">
    
      @include('partials.message')
      <list-postal-record url="{{ url('/') }}" mode="receptionist"></list-postal-record>
   </div>
@endsection