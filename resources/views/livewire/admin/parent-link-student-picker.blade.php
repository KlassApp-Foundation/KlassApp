<div class="relative min-w-[14rem] max-w-xs" wire:key="parent-link-student-picker">
    <input type="hidden" name="matched_student_id" value="{{ $selectedStudentId }}" required>

    @if($selectedStudentId)
        <div class="flex items-center gap-1">
            <span class="text-xs text-gray-800 border rounded px-2 py-1 bg-gray-50 truncate max-w-[11rem]" title="{{ $selectedLabel }}">
                {{ $selectedLabel }}
            </span>
            <button type="button"
                    wire:click="clearSelection"
                    class="text-xs text-gray-500 underline">
                Change
            </button>
        </div>
    @else
        <input type="search"
               wire:model.live.debounce.300ms="query"
               placeholder="Search student name…"
               autocomplete="off"
               class="text-xs border rounded px-2 py-1 w-full">

        <div wire:loading.delay class="text-[11px] text-gray-400 mt-1">Searching…</div>

        @if(mb_strlen(trim($query)) >= 2)
            <ul class="absolute z-20 mt-1 w-full max-h-48 overflow-y-auto bg-white border border-gray-200 rounded shadow-sm text-xs">
                @forelse($results as $student)
                    <li wire:key="plp-result-{{ $student->id }}">
                        <button type="button"
                                wire:click="selectStudent({{ $student->id }})"
                                class="w-full text-left px-2 py-1.5 hover:bg-emerald-50">
                            <span class="font-medium text-gray-800">{{ $student->displayName ?: $student->name }}</span>
                            <span class="text-gray-500 block">
                                {{ $student->studentAcademicLatest?->standardLink?->StandardSection ?? 'class n/a' }}
                                @if($showSchoolName && $student->school)
                                    · {{ $student->school->name }}
                                @endif
                            </span>
                        </button>
                    </li>
                @empty
                    <li class="px-2 py-1.5 text-gray-400">No matching students</li>
                @endforelse
            </ul>
        @endif
    @endif
</div>
