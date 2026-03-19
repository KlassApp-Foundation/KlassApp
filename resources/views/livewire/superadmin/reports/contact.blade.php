{{-- SPDX-License-Identifier: MIT --}}
<div>

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
                        <th class="tw-form-label px-2 py-2 text-left">School Name</th>
                        <th class="tw-form-label px-2 py-2 text-left">Email</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                @if(count($contacts) > 0)
                @foreach ($contacts as $contact)
                    <tr class="border-b">
                        <td class="py-3 px-2">
                            <label class="">{{ $contact->name }}</label>
                        </td>

                        <td class="py-3 px-2">
                            <label class="">{{ $contact->school_name }}</label>
                        </td>

                        <td class="py-3 px-2">
                            <label class="">{{ $contact->email }}</label>
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
{{ $contacts->links() }}
</div>




