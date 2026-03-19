{{-- SPDX-License-Identifier: MIT --}}
<div>
  <div class="flex items-center justify-start">
    <div class="bg-gray-200 p-1 rounded-full">
    <a href="{{ url('superadmin/dashboard') }}">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg></a>
    </div>

    <div class="text-xl ml-4 font-bold">
      Change Avatar
    </div>
  </div>

  @if (session()->has('message'))
    <div class="alert alert-success">
        {{ session('message') }}
    </div>
  @endif

<div class="my-5 px-5 lg:px-8 md:px-8 py-3 w-full bg-white shadow">
    <form method="post" wire:submit.prevent="submitAvatar" class="mt-6 space-y-6">
        @csrf
       
        <div>
         {{-- <label class="text-sm font-semibold">Image </label> --}}
          <input  name="image" type="file" class="mt-1 block w-full" wire:model="image"/>
          @error('image') 
            <span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span> 
          @enderror
        </div>

        <div>
          @if($image)
            Photo Preview:
            @if($image->temporaryUrl())
            <img src="{{ $image->temporaryUrl() }}" width="120" height="90">
            @endif
          @endif
        </div>
  	
  	<div class="my-5 pb-5 flex items-center">
      <div wire:loading.attr="disabled" class="submit-btn w-max mr-2 btn btn-primary">
      <svg wire:loading wire:target="submitAvatar"
      class="w-4 h-4 mx-1 text-blue animate-spin"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24">
      <circle class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4">
      </circle>
      <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
      </path>
      </svg>
      <input type="submit" class="text-white bg-transparent cursor-pointer" value="Submit"/>
    </div>
      <a href="#" class="btn btn-reset reset-btn">Reset</a>
    </div>

    </form>
</div>
</div>
