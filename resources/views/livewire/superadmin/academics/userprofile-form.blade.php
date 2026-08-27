SPDX-License-Identifier: MIT

  <div>

    <div class="flex items-center">
        <div class="bg-gray-200 p-1 rounded-full">
            <a href="{{ url('superadmin/academics/schools') }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg></a>
        </div>

        <div class="text-xl ml-3 font-bold">
           @if($schoolId!='')
             Edit School
            @else
             Create Userprofile
            @endif
        </div>
    </div>


    <div class="bg-white shadow px-4 py-3">

 <form wire:submit.prevent="submitUserprofile" method="POST" class="mt-6 space-y-6">
        @csrf

    <div class="flex flex-col lg:flex-col">

     <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="relation" class="tw-form-label">School<span class="text-red-500">*</span></label>
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

      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="school_id" class="tw-form-label">User<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full cursor-not-allowed" wire:model.live="user">
              <option value="{{ $user->id}}" disabled>{{ $user->name }}</option>
            </select>
            @error('user')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div>
        </div>
      </div>

     <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="relation" class="tw-form-label">Usergroup<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="usergroup">
              <option value="">Select Usergroup</option>
              @foreach($usergroups as $value)
            <option value="{{ $value->id }}">{{ ucfirst($value->name) }}</option>
            @endforeach
            </select>
            @error('usergroup')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div>
        </div>
      </div>

     <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Firstname<span class="text-red-500">*</span></label>
        </div>
          <input name="firstname" value="" type="text" placeholder="Firstname" class="tw-form-control w-full" wire:model.live="firstname">
          @error('firstname')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Lastname</label>
        </div>
          <input name="lastname" value="" type="text" placeholder="Lastname" class="tw-form-control w-full" wire:model.live="lastname">
          @error('lastname')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Alternate No</label>
        </div>
          <input name="alternate_no" value="" type="text" placeholder="Alternate No" class="tw-form-control w-full" wire:model.live="alternate_no">
          @error('alternate_no')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2 mt-2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="relation" class="tw-form-label">Gender<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="gender">
              <option value="">Select Gender</option>
              <option value="male">Boy</option>
              <option value="female">Girl</option>
          </select>
            @error('gender')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row pt-2">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Date Of Birth<span class="text-red-500">*</span></label>
        </div>
          <input name="dob" value="" type="date" placeholder="Date Of Birth" class="tw-form-control w-full" wire:model.live="dob" />
          @error('dob')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="relation" class="tw-form-label">Blood Group<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="blood_group">
              <option value="">Select Blood Group</option>

	            <option value="a+">A+</option>
	            <option value="b+">B+</option>
	            <option value="o+">O+</option>
	            <option value="ab+">AB+</option>
	            <option value="a-">A-</option>
	            <option value="b-">B-</option>
	            <option value="o-">O-</option>
	            <option value="ab-">AB-</option>

            </select>
            @error('blood_group')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Birth Place<span class="text-red-500">*</span></label>
        </div>
          <input name="birth_place" value="" type="text" placeholder="Birth Place" class="tw-form-control w-full" wire:model.live="birth_place">
          @error('birth_place')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Native Place<span class="text-red-500">*</span></label>
        </div>
          <input name="native_place" value="" type="text" placeholder="Native Place" class="tw-form-control w-full" wire:model.live="native_place">
          @error('native_place')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Mother Tongue<span class="text-red-500">*</span></label>
        </div>
          <input name="mother_tongue" value="" type="text" placeholder="Mother Tongue" class="tw-form-control w-full" wire:model.live="mother_tongue">
          @error('mother_tongue')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="relation" class="tw-form-label">Caste<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="caste">
            <option value="others">Select caste</option>
            <option value="bc">BC</option>
            <option value="bcm">BCM</option>
            <option value="fc">FC</option>
            <option value="mbc">MBC</option>
            <option value="obc">OBC</option>
            <option value="others">OTHERS</option>
            <option value="sc">SC</option>
            <option value="sca">SCA</option>
            <option value="st">ST</option>

            </select>
            @error('caste')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Address<span class="text-red-500">*</span></label>
        </div>
          <input name="address" value="" type="text" placeholder="Address" class="tw-form-control w-full" wire:model.live="address">
          @error('address')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="relation" class="tw-form-label">District<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="city">
              <option value="">Select District</option>
              @foreach($cities as $value)
            <option value="{{ $value->id }}">{{ ucfirst($value->name) }}</option>
            @endforeach
            </select>
            @error('city')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="relation" class="tw-form-label"> Country<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="country">
            <option value="">Select Country</option>
            @foreach($countries as $value)
            <option value="{{ $value->id }}">{{ ucfirst($value->name) }}</option>
            @endforeach
            </select>
            @error('country')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div>
        </div>
      </div>

   	  <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Pincode<span class="text-red-500">*</span></label>
        </div>
          <input name="pincode" value="" type="text" placeholder="Pincode" class="tw-form-control w-full" wire:model.live="pincode">
          @error('pincode')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Aadhar Number<span class="text-red-500">*</span></label>
        </div>
          <input name="aadhar_number" value="" type="number" placeholder="Aadhar Number" class="tw-form-control w-full" wire:model.live="aadhar_number">
          @error('aadhar_number')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Registration Number <span class="text-xs text-gray-400 font-normal">(KLS ID — system-managed)</span><span class="text-red-500">*</span></label>
        </div>
          <input name="registration_number" value="" type="text" pattern="KLS\d{7}" title="KlassApp Student ID format: KLS followed by 7 digits (e.g. KLS0010427)" placeholder="e.g. KLS0010427" class="tw-form-control w-full bg-gray-50" wire:model.live="registration_number" readonly>
          @error('registration_number')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">LIN Number<span class="text-red-500">*</span></label>
        </div>
          <input name="lin" value="" type="text" placeholder="LIN Number" class="tw-form-control w-full" wire:model.live="lin">
          @error('lin')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Joining Date<span class="text-red-500">*</span></label>
        </div>
          <input name="joining_date" value="" type="date" placeholder="Joining Date" class="tw-form-control w-full" wire:model.live="joining_date">
          @error('joining_date')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row">
        <div class="w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8 mb-2">
        <div class="mb-2">
        <label for="" class="tw-form-label">Notes</label>
        </div>
          <textarea wire:model.live="notes" class="tw-form-control w-full"></textarea>
          @error('notes')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
        </div>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2 mt-2">
        <div class="lg:mr-8 ">
          <div class="mb-2">
            <label for="relation" class="tw-form-label">Status<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" wire:model.live="status">
              <option value="">Select Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="exit">Exit</option>
            @error('status')<span class="text-red-600 text-xs font-normal"><strong>{{ $message }}</strong></span>@enderror
          </div>
        </div>
      </div>

      <div class="my-6">
      	{{-- <button wire:click="submitSchool" class="btn btn-primary submit-btn">Submit</button> --}}

     <input type="submit" class="btn btn-primary submit-btn" value="Submit"/>

        <a href="#" class="btn btn-reset reset-btn">Reset</a>
    </div>
  </div>
</form>
</div>
</div>
