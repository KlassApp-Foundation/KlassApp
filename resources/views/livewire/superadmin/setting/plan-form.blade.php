{{-- SPDX-License-Identifier: MIT --}}
  
  <div>

    <div class="flex items-center py-3">
        <div class="bg-gray-200 p-1 rounded-full">
            <a href="{{ url('superadmin/setting/plans') }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg></a>
        </div>

        <div class="text-xl ml-3 font-bold">
           @if($planEditId!='')
             Update Plan
            @else
             Create Plan
            @endif 
        </div>
    </div>


    <div class="bg-white shadow px-4 py-3">
   
 <form wire:submit.prevent="submitPlan" method="POST" class="">
        @csrf 

    <div class="flex flex-col lg:flex-col">

     <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Cycle<span class="text-red-500">*</span></label>
        </div>
          <input name="cycle" value="" type="number" placeholder="Cycle" class="tw-form-control w-full" wire:model.live="cycle">
          @error('cycle')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Name<span class="text-red-500">*</span></label>
        </div>
          <input name="name" value="" type="text" placeholder="Name" class="tw-form-control w-full" wire:model.live="name">
          @error('name')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Order<span class="text-red-500">*</span></label>
        </div>
          <input name="order" value="" type="number" placeholder="Order" class="tw-form-control w-full" wire:model.live="order">
          @error('order')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2">
       <div class="lg:mr-8 md:mr-8 ">
        <div>
          <label class="text-sm font-semibold">Status<span class="text-red-500">*</span></label>
          <div class="p-2 border border-gray-300 rounded text-sm mt-1">
           <div class="flex gap-3">
            <div class="flex items-center">
            <div><input  name="status" type="radio" class="block" wire:model="status" value='1' /></div>
            <span class="mx-2">Active </span> 
        </div>
        <div class="flex items-center">
         <div><input name="status" type="radio" class="mt-1 block" wire:model="status" value='0'/></div>
         <span class="mx-2">Inactive</span>
       </div>
    	</div>
       </div>
       @error('status') 
        <span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span> 
        @enderror
        </div>
      </div>
    </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Amount<span class="text-red-500">*</span></label>
        </div>
          <input name="amount" value="" type="number" placeholder="Amount" class="tw-form-control w-full" wire:model.live="amount">
          @error('amount')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Number of Members<span class="text-red-500">*</span></label>
        </div>
          <input name="no_of_members" value="" type="number" placeholder="Number of Members" class="tw-form-control w-full" wire:model.live="no_of_members">
          @error('no_of_members')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Number of Events<span class="text-red-500">*</span></label>
        </div>
          <input name="no_of_events" value="" type="number" placeholder="Number of Events" class="tw-form-control w-full" wire:model.live="no_of_events">
          @error('no_of_events')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Number of Folders<span class="text-red-500">*</span></label>
        </div>
          <input name="no_of_folders" value="" type="number" placeholder="Number of Folders" class="tw-form-control w-full" wire:model.live="no_of_folders">
          @error('no_of_folders')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Number of Files<span class="text-red-500">*</span></label>
        </div>
          <input name="no_of_files" value="" type="number" placeholder="Number of Files" class="tw-form-control w-full" wire:model.live="no_of_files">
          @error('no_of_files')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Number of Videos<span class="text-red-500">*</span></label>
        </div>
          <input name="no_of_videos" value="" type="number" placeholder="Number of Videos" class="tw-form-control w-full" wire:model.live="no_of_videos">
          @error('no_of_videos')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

       <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Number of Audios<span class="text-red-500">*</span></label>
        </div>
          <input name="no_of_audios" value="" type="number" placeholder="Number of Audios" class="tw-form-control w-full" wire:model.live="no_of_audios">
          @error('no_of_audios')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

       <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Number of Bulletins<span class="text-red-500">*</span></label>
        </div>
          <input name="no_of_bulletins" value="" type="number" placeholder="Number of Bulletins" class="tw-form-control w-full" wire:model.live="no_of_bulletins">
          @error('no_of_bulletins')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

       <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Number of Groups<span class="text-red-500">*</span></label>
        </div>
          <input name="no_of_groups" value="" type="number" placeholder="Number of Groups" class="tw-form-control w-full" wire:model.live="no_of_groups">
          @error('no_of_groups')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      {{-- <div class="my-6">
        <input type="submit" class="btn btn-primary submit-btn cursor-pointer" value="Submit"/> 
        <a href="#" class="btn btn-reset reset-btn">Reset</a>
      </div> --}}

      <div class="my-5 pb-5 flex items-center">
      <div wire:loading.attr="disabled" class="submit-btn w-max mr-2 btn btn-primary">
      <svg wire:loading wire:target="submitPlan"
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

  </div>
</form>
</div>
</div>