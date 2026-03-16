{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')

<div class="w-full lg:mx-2">
  <div class="flex flex-wrap lg:flex-row justify-between my-3">
    <div class="">
      <h1 class="admin-h1 my-3 flex items-center">
        <a href="{{ url('/admin/reports') }}" title="Back" class="rounded-full bg-gray-100 p-2">
          <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 492 492" xml:space="preserve" width="512px" height="512px" class="w-3 h-3 fill-current text-gray-700"><g><g><g><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124    c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844 L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412 c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008 c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788 C492,219.198,479.172,207.418,464.344,207.418z" data-original="#000000" fill="" class="active-path"></path></g></g></g></svg>
        </a>
        <span class="mx-3">Holidays List</span>
      </h1>
    </div>
    <div class="relative flex items-center w-1/4 lg:justify-end">
      <div class="">
        <a href="{{ url('/admin/report/exportHolidays'.$query) }}" id="export-button" class="no-underline text-white  px-4 my-3 mx-1 flex items-center custom-green py-1">
          <span class="mx-1 text-sm font-semibold">Export</span>
        </a> 
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading border border-gray-400 bg-white">
      <div class="flex flex-col">
        <div class="mt-3">
          <a href="{{ url('admin/report/downloadholidayformat') }}" id="sample" class="no-underline text-white px-4 my-3 mx-3 flex items-center custom-green py-1 w-11/12 lg:w-1/5">Download Sample Format</a>
        </div>
      </div>
      <form style="padding: 10px;margin-bottom: unset;" action="{{ url('admin/report/importHolidays') }}" class="form-horizontal" method="post" enctype="multipart/form-data">
        @csrf
        @include('partials.message')
        <div class="flex flex-col">
          <div>
            <input type="file" id="file" name="import_file">
          </div>
          <span class="text-red-500 text-xs font-semibold">{{$errors->first('import_file')}}</span> 
          <div class="mt-3">
            <button class="btn btn-primary submit-btn" id="import">Import</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection