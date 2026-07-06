{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')


<div class="relative px-4 md:px-6 py-4">
	<div class="ds-page-head">
		<div>
		   <h1 class="ds-page-head-title">Promotion</h1>
		</div>
	</div>
	@include('partials.message')
	<create-promotion url="{{ url('/') }}"></create-promotion>  

</div>

@endsection



