{{-- SPDX-License-Identifier: MIT --}}
<div class="">
    <div class="flex flex-row items-center justify-between">
        <div class="flex items-center mt-4">
            <div class="bg-gray-200 p-1 rounded-full">
                <a href="{{ url('superadmin/academics/schools') }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
		  	<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
			</svg></a>
            </div>

            <div class="text-xl ml-3 font-bold ">
               School Details
            </div>
		</div>

<div class="flex items-center">
             <div class=" flex items-center mx-2 mt-4">

                <a href="{{ url('/superadmin/academics/school/admin/create',['id'=>$schoolDetail->id]) }}"  title="Edit" class="px-2 py-1 rounded ml-1 menuclick text-white bg-green-600 text-xs">
                  Create Admin
                </a>

                <a href="{{ url('/superadmin/academics/school/update',['id'=>$schoolDetail->id]) }}"  title="Edit" class="px-2 py-1 rounded ml-1 menuclick text-white bg-blue-500 text-xs">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
				  <path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" />
				  <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" />
				</svg>
                </a>
                
                {{-- <button title="Delete" class="px-2 py-1 ml-1 rounded menuclick text-white bg-red-600 text-xs" title="Delete" wire:click="removeNews('{{$schoolDetail->id}}')" >
                  <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20" aria-labelledby="delete" role="presentation" class="w-4 h-4"><path fill-rule="nonzero" d="M6 4V2a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2h5a1 1 0 0 1 0 2h-1v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6H1a1 1 0 1 1 0-2h5zM4 6v12h12V6H4zm8-2V2H8v2h4zM8 8a1 1 0 0 1 1 1v6a1 1 0 0 1-2 0V9a1 1 0 0 1 1-1zm4 0a1 1 0 0 1 1 1v6a1 1 0 0 1-2 0V9a1 1 0 0 1 1-1z"/></svg>
                </button> --}}
              </div> 
</div>

        </div>
   
   <div class="my-3 p-3 w-full bg-white shadow">
    <div class="">                      
        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Name</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ ucfirst($schoolDetail->name) }}</p>
            </div>
        </div> 
        
        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Email</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $schoolDetail->email }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Phone</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $schoolDetail->phone }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Address</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $schoolDetail->address }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>City</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $schoolDetail->city->name }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>State</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $schoolDetail->state->name }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Country</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $schoolDetail->country->name }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Pincode</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                <p class="leading-loose txt-gray-light">{{ $schoolDetail->pincode }}</p>
            </div>
        </div>
                
        <div class="flex flex-col lg:flex-row md:flex-row px-2 py-2">
            <div class="font-bold text-sm lg:w-1/4 md:w-1/4">
                <p>Status</p>
            </div>
            <div class="text-sm lg:w-3/4 md:w-3/4">
                @if($schoolDetail->status==1)
                 <span class="bg-green-500 text-white text-xs font-medium px-2 py-1 rounded-full dark:bg-green-900 dark:text-green-300">Active</span>
                @else
                   <span class="bg-red-500 text-white text-xs font-medium px-2 py-1 rounded-full dark:bg-red-900 dark:text-red-300">Inactive</span>
                @endif
            </div>
        </div> 
        
    </div>
</div>

<div class="text-xl ml-3 font-bold">Admin</div>
    <livewire:superadmin.academics.admin-list :id="$schoolDetail->id" />


{{--<div class="flex items-center justify-between">
<div class="text-xl ml-3 font-bold mt-3">User</div>

<a href="{{ url('/superadmin/academics/school/admin/create',['id'=>$schoolDetail->id]) }}"  title="Edit" class="px-2 py-1 rounded ml-1 menuclick text-white bg-green-600 text-xs">
                  Create User
                </a>
            </div>
    <livewire:superadmin.academics.user-list :id="$schoolDetail->id" /> --}}

</div>



