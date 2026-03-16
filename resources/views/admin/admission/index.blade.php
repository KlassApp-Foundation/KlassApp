{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
   	<admission-list url="{{ url('/') }}"  slug="{{ $slug }}"></admission-list>
@endsection