{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="relative">

<livewire:admin.addon.detail :id="$id"/> 
   
</div>

 @endsection