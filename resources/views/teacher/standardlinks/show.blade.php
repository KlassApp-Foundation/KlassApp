{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
   <div class="relative">
      <div class="flex items-center justify-between  mb-2">
         <h1 class="admin-h1 flex items-center my-3">
            <a href="{{ url('/admin/standardlinks') }}" title="Back" class="rounded-full bg-gray-100 p-2">
               <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 492 492" xml:space="preserve" width="512px" height="512px" class="w-3 h-3 fill-current text-gray-700"><g><g><g><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124    c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844 L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412 c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008 c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788    C492,219.198,479.172,207.418,464.344,207.418z" data-original="#000000" fill="" class="active-path"></path></g></g></g></svg>
            </a>
            <span class="mx-3">Class {{ $standardLink->StandardSection }}</span>
         </h1>
         <div class="flex items-center">
            <svg class="w-4 h-4 mr-3 fill-current text-gray-700" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.008 512.008" style="enable-background:new 0 0 512.008 512.008;" xml:space="preserve"><g><g><path d="M384.001,53.333V10.667c0-4.354-2.646-8.281-6.688-9.896C376.022,0.25,374.668,0,373.335,0 c-2.854,0-5.646,1.146-7.708,3.292L130.96,248.625c-3.937,4.125-3.937,10.625,0,14.75l234.667,245.333 c3.021,3.146,7.646,4.167,11.688,2.521c4.042-1.615,6.688-5.542,6.688-9.896v-42.667c0-2.729-1.042-5.354-2.917-7.333L196.022,256 L381.085,60.667C382.96,58.688,384.001,56.063,384.001,53.333z"/></g></g></svg>
            <svg class="w-4 h-4 fill-current text-gray-700" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.008 512.008" style="enable-background:new 0 0 512.008 512.008;" xml:space="preserve"><g><g><path d="M381.048,248.633L146.381,3.299c-3.021-3.146-7.646-4.167-11.688-2.521c-4.042,1.615-6.688,5.542-6.688,9.896v42.667 c0,2.729,1.042,5.354,2.917,7.333l185.063,195.333L130.923,451.341c-1.875,1.979-2.917,4.604-2.917,7.333v42.667 c0,4.354,2.646,8.281,6.688,9.896c1.292,0.521,2.646,0.771,3.979,0.771c2.854,0,5.646-1.146,7.708-3.292l234.667-245.333 C384.986,259.258,384.986,252.758,381.048,248.633z"/></g></g></svg>
         </div>
      </div>
      @include('partials.message')
      @include('teacher.standardlinks.show_form')
   </div>
@endsection

   