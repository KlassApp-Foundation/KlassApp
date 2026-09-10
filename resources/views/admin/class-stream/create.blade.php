{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
   <div class="relative">
      <div class="flex flex-wrap lg:flex-row justify-between">
         <div class="">
            <h1 class="admin-h1 my-3">Add stream</h1>
         </div>
         <div class="relative flex items-center w-1/4 lg:justify-end">
            <div class="flex items-center">
               <a href="{{ route('admin.classes') }}" class="ds-btn ds-btn-ghost ds-btn-sm">
                  ← Back to Classes
               </a>
            </div>
         </div>
      </div>

      @include('partials.message')

      <div class="ds-card ds-card-body max-w-2xl">
         <p class="text-gray-600 mb-4">
            Add a stream under <strong>{{ $base->name }}</strong>.
            The undivided base class stays; the new stream is named
            <strong>{{ $base->name }} &lt;stream&gt;</strong>
            (for example {{ $base->name }} A).
         </p>

         <form method="POST" action="{{ route('admin.class-stream.store', $section) }}" data-testid="admin-add-stream-form">
            @csrf

            <div class="mb-4">
               <label class="block text-sm font-semibold mb-1" for="stream">Stream name</label>
               <input id="stream" type="text" name="stream" value="{{ old('stream') }}"
                      class="form-control w-full @error('stream') border-red-500 @enderror"
                      placeholder="e.g. A, East, Science" required
                      data-testid="admin-stream-name">
               @error('stream')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
               @enderror
               <p class="text-xs text-gray-500 mt-1">
                  Leave students on the base class until you assign them to a stream.
               </p>
            </div>

            <button type="submit" class="ds-btn ds-btn-primary" data-testid="admin-stream-submit">
               Add stream
            </button>
         </form>
      </div>
   </div>
@endsection
