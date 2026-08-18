<div class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <a href="{{ auth()->user()->usergroup_id === 3 ? route('admin.classes.index') : route('teacher.classes.index') }}" class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700 hover:text-emerald-900">← All classes</a>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $section->name }} roster</h1>
                <p class="mt-2 text-sm text-slate-500">Streams, effective class teachers, and the current read-only student roster.</p>
            </div>
            <label class="block min-w-52">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400">Academic year</span>
                <select wire:model.live="selectedAcademicYearId" class="w-full rounded-xl border-slate-200 bg-white text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach($years as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Class-level assignment</p>
                <p class="mt-1 text-sm text-slate-600">Fallback teacher: <span class="font-semibold text-slate-900">{{ $sectionTeacher?->name ?: 'Not assigned' }}</span></p>
                </div>
                @if($streams->isEmpty())
                    <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700">No streams configured</span>
                @endif
            </div>

            @if($streams->isNotEmpty())
                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($streams as $row)
                        @php($stream = $row['stream'])
                        <button wire:click="selectStream({{ $stream->id }})" type="button" class="text-left rounded-2xl border p-4 transition {{ $selectedStream?->id === $stream->id ? 'border-emerald-500 bg-emerald-50 shadow-sm' : 'border-slate-200 bg-slate-50 hover:border-emerald-300' }}">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-lg font-bold text-slate-950">{{ $stream->stream ?: 'Main stream' }}</span>
                                <span class="rounded-full bg-white px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $stream->standard?->name ?: 'Level pending' }}</span>
                            </div>
                            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-slate-400">Effective teacher</p>
                            <p class="mt-1 truncate text-sm font-semibold text-slate-800">{{ $row['effectiveTeacher']?->name ?: 'Not assigned' }}</p>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @forelse($row['teacherLinks'] as $teacherLink)
                                    <span class="rounded-full bg-white px-2 py-1 text-[10px] font-semibold text-slate-600">{{ $teacherLink->subject?->name ?: 'Subject pending' }}</span>
                                @empty
                                    <span class="text-xs text-slate-400">No subject assignments visible</span>
                                @endforelse
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        @if($selectedStream)
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-5 md:flex-row md:items-end">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">{{ $selectedStream->stream ?: 'Main stream' }} · student roster</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $students->total() }} {{ \Illuminate\Support\Str::plural('student', $students->total()) }}</h2>
                        @if(!$fullStudentDetail)
                            <p class="mt-2 text-xs text-amber-700">Subject view: student details are limited to your assigned subject context.</p>
                        @endif
                    </div>
                    <input wire:model.live.debounce.300ms="studentSearch" type="search" placeholder="Search students..." class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm md:w-64 focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                @if($students->count())
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                            <thead>
                                <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                    <th class="px-3 py-3">Student</th>
                                    <th class="px-3 py-3">Status</th>
                                    @if($fullStudentDetail)
                                        <th class="px-3 py-3">Academic status</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($students as $studentAcademic)
                                    <tr wire:key="student-{{ $studentAcademic->id }}" class="text-slate-700">
                                        <td class="px-3 py-3 font-semibold text-slate-900">{{ $studentAcademic->user?->name ?: 'Unnamed student' }}</td>
                                        <td class="px-3 py-3"><span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">{{ ucfirst($studentAcademic->user?->status ?: 'unknown') }}</span></td>
                                        @if($fullStudentDetail)
                                            <td class="px-3 py-3 text-slate-500">{{ ucfirst($studentAcademic->academic_status ?: 'Not recorded') }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-5">{{ $students->links() }}</div>
                @else
                    <div class="mt-5 rounded-2xl border border-dashed border-slate-300 px-5 py-12 text-center">
                        <p class="font-semibold text-slate-900">No students in this stream yet</p>
                        <p class="mt-1 text-sm text-slate-500">The class can be built incrementally; an empty roster is valid.</p>
                    </div>
                @endif
            </div>
        @elseif($streams->isNotEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <p class="font-semibold text-slate-900">Choose a stream to view its roster</p>
            </div>
        @endif
    </div>
</div>
