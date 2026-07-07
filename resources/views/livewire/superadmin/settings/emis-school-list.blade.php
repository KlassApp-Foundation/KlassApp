<div>
    <div class="mb-4">
        <input type="text"
            class="tw-form-control w-full lg:w-1/2"
            placeholder="Search by EMIS code, school name, or district..."
            wire:model.live.debounce.300ms="search">
    </div>

    <div class="bg-white shadow overflow-x-auto rounded">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-gray-50 text-left">
                    <th class="px-4 py-3 font-semibold text-gray-600">EMIS Code</th>
                    <th class="px-4 py-3 font-semibold text-gray-600">School Name</th>
                    <th class="px-4 py-3 font-semibold text-gray-600">District</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schools as $school)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-blue-600">{{ $school->emis_code }}</td>
                        <td class="px-4 py-3">{{ $school->school_name }}</td>
                        <td class="px-4 py-3">{{ $school->district }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                            @if($search)
                                No schools match "{{ $search }}"
                            @else
                                No EMIS records loaded.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $schools->links() }}
    </div>
</div>
