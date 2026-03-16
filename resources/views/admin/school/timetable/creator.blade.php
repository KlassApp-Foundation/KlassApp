{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
    <div class="relative" x-data="{showModalAdmission: false}">
        <div class="flex flex-row justify-between">
            <div class="">
                <h1 class="admin-h1 my-3">TimeTable Creator</h1>
            </div>
        </div>
       
        <livewire:timetable.time-table-creator/> 

	</div>

@push('scripts')
<style>
    .loader {
      border-top-color: #3498DB;
      -webkit-animation: spinner 1.5s linear infinite;
      animation: spinner 1.5s linear infinite;
    }
    @-webkit-keyframes spinner {
      0% {
        -webkit-transform: rotate(0deg);
      }
      100% {
        -webkit-transform: rotate(360deg);
      }
    }
    @keyframes spinner {
      0% {
        transform: rotate(0deg);
      }
      100% {
        transform: rotate(360deg);
      }
    }
</style>

@endpush
@endsection