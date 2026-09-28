<div class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-emerald-700">Class roster</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Classes &amp; streams</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    A read-only view of the classes and teaching assignments you are authorized to see.
                </p>
            </div>
            @if($selectedYear)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Academic year</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $selectedYear->name }}</p>
                </div>
            @endif
        </div>

        @if($years->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                <p class="text-lg font-semibold text-slate-900">No academic year is available yet</p>
                <p class="mt-2 text-sm text-slate-500">Classes will appear here once an academic year is set up.</p>
            </div>
        @else
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-wrap items-end gap-x-3 gap-y-3" data-testid="ct-filter-form">
                    <label class="block max-sm:w-full">
                        <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Academic year</span>
                        <select wire:model.live="selectedAcademicYearId" class="max-sm:w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 sm:w-40 h-10" data-testid="ct-filter-year">
                            @foreach($years as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block max-sm:w-full">
                        <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Level</span>
                        <select wire:model.live="levelId" class="max-sm:w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 sm:w-32 h-10" data-testid="ct-filter-level">
                            <option value="">All levels</option>
                            @foreach($levels as $level)
                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block max-sm:w-full">
                        <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Assignment</span>
                        <select wire:model.live="assignmentStatus" class="max-sm:w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 sm:w-44 h-10" data-testid="ct-filter-assignment">
                            <option value="all">All classes</option>
                            <option value="assigned">With a teacher</option>
                            <option value="unassigned">Needs assignment</option>
                        </select>
                    </label>
                    <label class="block max-sm:w-full">
                        <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Find a class</span>
                        <span class="relative block">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
                            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search P.1, P.7..." class="max-sm:w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 sm:w-56 h-10" data-testid="ct-filter-search">
                        </span>
                    </label>
                    <button wire:click="clearFilters" type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 h-10 text-xs font-bold uppercase tracking-wider text-slate-500 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700" data-testid="ct-filter-clear">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear filters
                    </button>
                </div>
            </div>

            @if($sections->count())
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($sections as $section)
                        @php
                            $streams = $section->standardLink;
                            $assignedStreams = $streams->filter(fn ($stream) => $stream->class_teacher_id !== null)->count();
                            $level = $streams->first()?->standard?->name;
                            $sectionTeacher = $section->classTeacher;
                            $showRoute = auth()->user()->usergroup_id === 3
                                ? route('admin.classes.show', ['section' => $section->id, 'academic_year_id' => $selectedYear->id])
                                : route('teacher.classes.show', ['section' => $section->id, 'academic_year_id' => $selectedYear->id]);
                        @endphp
                        <a href="{{ $showRoute }}" class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">{{ $level ?: 'Level pending' }}</p>
                                    <h2 class="mt-2 text-xl font-bold text-slate-950">{{ $section->name }}</h2>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $streams->count() }} {{ \Illuminate\Support\Str::plural('stream', $streams->count()) }}</span>
                            </div>
                            <div class="mt-6 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Assigned streams</p>
                                    <p class="mt-1 text-lg font-bold text-slate-800">{{ $assignedStreams }}/{{ $streams->count() }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Class teacher</p>
                                    <p class="mt-1 truncate text-sm font-semibold text-slate-700">{{ $sectionTeacher?->name ?: 'Not assigned' }}</p>
                                </div>
                            </div>
                            <div class="mt-5 flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-400 group-hover:text-emerald-700">
                                <span>Open roster</span>
                                <span aria-hidden="true" class="text-lg transition group-hover:translate-x-1">→</span>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div>{{ $sections->links() }}</div>
            @else
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <p class="text-lg font-semibold text-slate-900">No classes match these filters</p>
                    <p class="mt-2 text-sm text-slate-500">Try another academic year or clear the filters.</p>
                </div>
            @endif
        @endif
    </div>
</div>
