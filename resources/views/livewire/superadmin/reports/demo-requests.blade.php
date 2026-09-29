{{-- SPDX-License-Identifier: MIT --}}
<div>
    <div class="bg-white shadow px-4 py-3">
        <div class="">
            <table class="w-full border my-3">
                <thead class="bg-gray-400">
                    <tr class="border-b">
                        <th class="tw-form-label px-2 py-2 text-left">Date</th>
                        <th class="tw-form-label px-2 py-2 text-left">School</th>
                        <th class="tw-form-label px-2 py-2 text-left">Contact</th>
                        <th class="tw-form-label px-2 py-2 text-left">Email</th>
                        <th class="tw-form-label px-2 py-2 text-left">Phone</th>
                        <th class="tw-form-label px-2 py-2 text-left">District</th>
                        <th class="tw-form-label px-2 py-2 text-left">Source</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                @if(count($demoRequests) > 0)
                @foreach ($demoRequests as $demoRequest)
                    <tr class="border-b align-top">
                        <td class="py-3 px-2 whitespace-nowrap">{{ $demoRequest->created_at?->format('j M Y, H:i') }}</td>
                        <td class="py-3 px-2">{{ $demoRequest->school_name }}</td>
                        <td class="py-3 px-2">{{ $demoRequest->contact_name }}</td>
                        <td class="py-3 px-2">{{ $demoRequest->email }}</td>
                        <td class="py-3 px-2 whitespace-nowrap">{{ $demoRequest->phone }}</td>
                        <td class="py-3 px-2">{{ $demoRequest->district ?? '-' }}</td>
                        <td class="py-3 px-2">{{ $demoRequest->source_page ?? '-' }}</td>
                    </tr>
                    @if($demoRequest->message)
                    <tr class="border-b">
                        <td></td>
                        <td class="py-1 px-2 pb-3 text-gray-600 text-xs" colspan="6">{{ \Illuminate\Support\Str::limit($demoRequest->message, 200) }}</td>
                    </tr>
                    @endif
                @endforeach
                @else
                <tr><td class="py-3 px-2 text-center" colspan="7">No Records Found</td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
{{ $demoRequests->links() }}
</div>
