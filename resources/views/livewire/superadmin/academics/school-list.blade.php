{{-- SPDX-License-Identifier: MIT --}}
<div>
    <div class="flex items-center justify-between mt-2 py-2">
<div class="text-xl ml-3 font-bold">
        School List
    </div>
    <a href="{{ url('/superadmin/academics/school/create') }}" title="Create" class="px-2 py-1 rounded ml-1 menuclick text-white bg-green-600 text-xs">
        Create School
    </a>
</div>

    <div class="bg-white shadow px-4 py-3">

        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        
        <div class="">
            <table class="w-full border my-3"> 
                <thead class="bg-gray-400">
                    <tr class="border-b">
                        <th class="tw-form-label px-2 py-2 text-left">Name</th>
                        <th class="tw-form-label px-2 py-2 text-left">Email</th>
                        <th class="tw-form-label px-2 py-2 text-left">Phone</th>
                        <th class="tw-form-label px-2 py-2 text-left">Status</th>
                        <th class="tw-form-label px-2 py-2 text-center" width="10%">Action</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                @if(count($schools) > 0)
                @foreach ($schools as $school)
                    <tr class="border-b">
                        <td class="py-3 px-2">
                            <label class="">{{ $school->name }}</label>
                        </td>

                        <td class="py-3 px-2">
                            <label class="">{{ $school->email }}</label>
                        </td>

                        <td class="py-3 px-2">
                            <label class="">{{ $school->phone }}</label>
                        </td>
                        <td class="py-3 px-2">
			                @if($school->status == 1)
			                  <span class="bg-green-500 text-white text-xs font-medium px-2 py-1 rounded-full dark:bg-green-900 dark:text-green-300">Active</span>
			                @else
			                  <span class="bg-red-500 text-white text-xs font-medium px-2 py-1 rounded-full dark:bg-red-900 dark:text-red-300">Inactive</span>
			                @endif
			             </td>
			            <td class="py-3 px-2">
			              <div class="flex items-center justify-center">

			              	<a href="{{ url('/superadmin/academics/school/detail',['id'=>$school->id]) }}"  title="View" class="px-2 py-1 ml-1 rounded menuclick flex items-center justify-center w-fit text-white bg-gray-500 text-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
                 </a>

			                <a href="{{url('/superadmin/academics/school/update',['id'=>$school->id])}}"  title="Edit" class="px-1 py-1 mx-1 rounded menuclick bg-slate-500 text-xs text-white bg-blue-500">
			                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
								  <path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" />
								  <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" />
								</svg>
			                </a>
			              </div>
          				</td> 
                    </tr>
                @endforeach
                @else
                <tr><td class="py-3 px-2 text-center" colspan="6">No Records Found</td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
{{ $schools->links() }}
</div>




