{{-- SPDX-License-Identifier: MIT --}}
  <div>

    <div class="flex items-center">
        <div class="bg-gray-200 p-1 rounded-full">
            <a href="{{ url('superadmin/reports/subscriptions') }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg></a>
        </div>

        <div class="text-xl ml-3 font-bold">
           @if($subscriptionEditId != '')
             Update Subscription
            @else
             Create Subscription
            @endif 
        </div>
    </div>


    <div class="bg-white shadow px-4 py-3">
   
 <form wire:submit.prevent="submitSubscription" method="POST" class="mt-6 space-y-6">
        @csrf 

    <div class="flex flex-col lg:flex-col">
     
     <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="school_id" class="tw-form-label">User<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="user">
            	<option value="">Select User</option>
	            @foreach($users as $value)
	            <option value="{{ $value->id }}">{{ ucfirst($value->name) }}</option>
	            @endforeach
            </select>
            @error('user')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div> 
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="school_id" class="tw-form-label">Plan<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="plan">
            	<option value="">Select Plan</option>
	            @foreach($plans as $value)
	            <option value="{{ $value->id }}">{{ ucfirst($value->name) }}</option>
	            @endforeach
            </select>
            @error('plan')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div> 
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="school_id" class="tw-form-label">School<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="school">
            	<option value="">Select School</option>
	            @foreach($schools as $value)
	            <option value="{{ $value->id }}">{{ ucfirst($value->name) }}</option>
	            @endforeach
            </select>
            @error('school')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div> 
        </div>
      </div>

     <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Payment Details</label>
        </div>
          <textarea wire:model.live="payment_details" class="tw-form-control w-full"></textarea> 
          @error('payment_details')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Plan Details</label>
        </div>
          <textarea wire:model.live="plan_details" class="tw-form-control w-full"></textarea> 
          @error('plan_details')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="school_id" class="tw-form-label">Status<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="status">
            	<option value="">Select Status</option>
	            <option value="pending">Pending</option>
	            <option value="approve">Approveed</option>
	            <option value="cancel">Cancelled</option>
	            <option value="expired">Expired</option>
            </select>
            @error('status')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div> 
        </div>
      </div>

      {{-- <div class="my-6">
        <input type="submit" class="btn btn-primary submit-btn" value="Submit"/> 
        <a href="#" class="btn btn-reset reset-btn">Reset</a>
      </div> --}}

      <div class="my-5 pb-5 flex items-center">
      <div wire:loading.attr="disabled" class="submit-btn w-max mr-2 btn btn-primary">
      <svg wire:loading wire:target="submitSubscription"
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