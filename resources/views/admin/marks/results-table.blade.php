<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    ID
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Student
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Subject
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Marks (%)
                </th>
                
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Grade
                </th>
                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($marks as $mark)
                <tr class="hover:bg-gray-50 transition">
                    {{-- id --}}
                    <td>
                        <div class="">
                        {{ $loop->iteration }}
                    </div>
                    </td>
                    {{-- ===== student name ======== --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $mark->student?->name ?? '—' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $mark->student?->admission_number ?? '' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    {{-- =========== subject name =========== --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $mark->subject?->name ?? '—' }}
                    </td>
                    {{-- ============ marks ========== --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        {{ $mark->marks ?? '—' }}
                        {{-- @if($mark->marks !== null)
                            <span class="text-xs text-gray-500">/{{ $mark->exam?->max_marks ?? '?' }}</span>
                        @endif --}}
                    </td>
                   
                    {{-- =========== grade ============ --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $mark->grade ?? '—' }}
                    </td>

                    {{-- ============== actions ============= --}}
                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 flex gap-3 font-semibold">
                        <a href="#" class="bg-green-400 px-2 rounded text-white ">Edit</a>
                        <a href="{{ route("admin.marks.student.class", [$mark->student, $class->id]) }}" class="bg-blue-400 px-2 rounded text-white ">View</a>
                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
</div>