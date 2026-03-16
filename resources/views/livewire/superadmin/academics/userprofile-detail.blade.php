{{-- SPDX-License-Identifier: MIT --}}
<div class="">
    <div class="flex flex-row items-center justify-between">
        <div class="flex items-center">
            <div class="bg-gray-200 p-1 rounded-full">
                <a href="{{ url('superadmin/academics/schools') }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
		  	<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
			</svg></a>
            </div>

            <div class="text-xl ml-3 font-bold">
               Userprofile Details
            </div>
		</div>

        <div class="flex items-center">
            <div class=" flex items-center mx-2">
                <a href="{{ url('/superadmin/academics/school/userprofile/update',['id'=>$userprofileDetail->id]) }}"  title="Edit" class="px-2 py-1 rounded ml-1 menuclick text-white bg-blue-500 text-xs">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
				  <path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" />
				  <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" />
				</svg>
                </a>
            </div> 
        </div>

        </div>
   
   <div class="my-3 p-3 w-full bg-white shadow">
    <div class="">                      
        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>School</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->school->name }}</p>
            </div>
        </div> 
        
        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>User</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->user->name }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Usergroup</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->usergroup->name }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Firstname</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->firstname }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Lastname</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
            	@if($userprofileDetail->lastname != '')
                	<p class="leading-loose txt-gray-light">{{ $userprofileDetail->lastname }}</p>
                @else
                	<p class="leading-loose txt-gray-light">--</p>
                @endif
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Alternate No.</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
            	@if($userprofileDetail->alternate_no != '')
                	<p class="leading-loose txt-gray-light">{{ $userprofileDetail->alternate_no }}</p>
                @else
                	<p class="leading-loose txt-gray-light">--</p>
                @endif

            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Gender</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
            	@if($userprofileDetail->gender == 'male')
                	<p class="leading-loose txt-gray-light">Boy</p>
                @else
                	<p class="leading-loose txt-gray-light">Girl</p>
                @endif
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Date Of Birth</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->date_of_birth }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Blood Group</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ strtoupper($userprofileDetail->blood_group) }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Birth Place</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->birth_place }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Native Place</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->native_place }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Mother Tongue</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->mother_tongue }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Caste</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->caste }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Address</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->address }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>City</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->city->name }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>State</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->state->name }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Country</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->country->name }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Pincode</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->pincode }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Aadhar Number</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->aadhar_number }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Registration Number</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->registration_number }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>EMIS Number</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->EMIS_number }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Joining Date</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $userprofileDetail->joining_date }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Notes</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
            	@if($userprofileDetail->notes != '')
                	<p class="leading-loose txt-gray-light">{{ $userprofileDetail->notes }}</p>
                @else
                	<p class="leading-loose txt-gray-light">--</p>
                @endif
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Status</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                @if($userprofileDetail->status == 'active')
                 <span class="bg-green-500 text-white text-xs font-medium px-2 py-1 rounded-full dark:bg-green-900 dark:text-green-300">Active</span>
                @elseif($userprofileDetail->status == 'inactive')
                   <span class="bg-red-500 text-white text-xs font-medium px-2 py-1 rounded-full dark:bg-red-900 dark:text-red-300">Inactive</span>
                @else
                	<span class="bg-orange-500 text-white text-xs font-medium px-2 py-1 rounded-full dark:bg-orange-900 dark:text-orange-300">Exit</span>
                @endif
            </div>
        </div>

    </div>
</div>




</div>



