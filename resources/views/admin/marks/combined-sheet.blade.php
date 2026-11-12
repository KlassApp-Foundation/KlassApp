<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" data-testid="exams-marks">

    @if(($filterRoute ?? '') === 'admin.marks.filter')
        @include('partials.ka-marks-nav', ['marksNav' => 'sheet'])
        @include('partials.ka-lifecycle', ['current' => 'saved'])
    @endif
    <div class="ds-page-head" data-testid="exams-page-head">
        <div>
            <h1 class="ds-page-head-title">{{ ($type?->name ?? null) ? (($type->name === 'End of Term' || str_contains(strtolower($type->name), 'end')) ? 'End of term marks' : ($type->name.' marks')) : 'Marks' }}</h1>
            <p class="ds-page-head-sub" data-testid="exams-page-sub">{{ $marksSubtitle }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap ka-sheet-actions">
            @if($filtered && $students->isNotEmpty() && $missingSubjects->isEmpty())
                <span class="ds-save-indicator ds-save-indicator--saved" data-testid="exams-save-indicator">
                    <span class="ds-save-indicator__dot" aria-hidden="true"></span>
                    All marks saved
                </span>
            @endif
            @if($filtered && $students->isNotEmpty())
                <a href="{{ route($downloadRoute, request()->query()) }}" class="ds-btn ds-btn-ghost text-sm" data-testid="marks-sheet-download">Download mark sheet</a>
            @endif
            <a href="{{ route($releaseRoute) }}" class="ds-btn ds-btn-primary text-sm" data-testid="marks-go-release">Go to Marks Release</a>
        </div>
    </div>

    @include('partials.message')

    @if(($hasAnyExams ?? true) === false)
        <div class="ds-card ds-card-padding-default mb-4" data-testid="admin-marks-empty">
            <p class="text-sm" style="color: var(--d-muted);">No exams yet: create this term's exam set.</p>
            <a href="{{ route('admin.exam-sets.create') }}" class="ds-btn ds-btn-primary ds-btn-sm mt-3 inline-flex" data-testid="admin-exam-set-link">Create this term's exam set</a>
        </div>
    @endif

    <div data-testid="exams-filter-card" class="mb-4">
        @include('admin.marks.filter-form')
    </div>

    @if($examSetTabs !== [])
        <nav class="ka-tabs ka-exam-tabs" aria-label="Exam set" data-testid="marks-exam-tabs">
            @foreach ($examSetTabs as $tab)
                @php
                    $tabQuery = array_filter([
                        'year' => request('year'),
                        'term' => request('term'),
                        'class' => request('class'),
                        'stream' => request('stream'),
                        'examType' => $tab->id,
                    ], fn ($value) => $value !== null && $value !== '');
                @endphp
                <a href="{{ route($filterRoute, $tabQuery) }}"
                   data-testid="marks-exam-tab"
                   data-exam-set="{{ mb_strtolower(trim($tab->name)) }}"
                   @if((string) request('examType') === (string) $tab->id) aria-current="page" @endif>{{ $tab->name }}</a>
            @endforeach
        </nav>
    @endif

    <form method="GET" action="{{ route($filterRoute) }}" class="ka-other-exam" data-testid="marks-other-exam">
        <input type="hidden" name="year" value="{{ request('year') }}">
        <input type="hidden" name="term" value="{{ request('term') }}">
        <input type="hidden" name="class" value="{{ request('class') }}">
        <input type="hidden" name="stream" value="{{ request('stream') }}">
        <label for="examType" class="ds-label">Other exam</label>
        <select name="examType" id="examType" class="ds-form-select">
            <option value="">Other exam</option>
            @foreach ($otherExamTypes as $otherType)
                <option value="{{ $otherType->id }}" @selected((string) request('examType') === (string) $otherType->id)>
                    {{ $otherType->name }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="ds-btn ds-btn-outline">Show exam</button>
    </form>

    {{-- design review: invalid year and term uses the shared error alert; no mark-sheet error screen was drawn --}}
    @if($marksError)
        <div class="ka-alert ka-alert-error ka-marks-error" role="alert" data-testid="marks-sheet-error">
            {{ $marksError }}
        </div>
    @endif

    @if($filtered)
        <div class="dashboard-kpi-grid mb-4" data-testid="exams-kpi-grid">
            <x-ds-kpi-card icon="users" :value="(string) $students->total()" label="Students" color="blue" />
            <x-ds-kpi-card icon="book" :value="(string) $subjectsCovered" label="Subjects covered" color="green" />
            <x-ds-kpi-card icon="exam" :value="(string) $subjects->count()" label="Subjects on grid" color="amber" />
        </div>
    @endif

    @if($filtered && $missingSubjects->isNotEmpty())
        <div class="ds-reminder-banner" data-testid="exams-reminder-banner" style="margin-bottom: 16px;">
            <div style="font-family: var(--d-font-display, 'Sora', sans-serif); font-weight: 600; font-size: 14px; color: var(--d-dark);">
                {{ $missingSubjects->count() }} {{ \Illuminate\Support\Str::plural('subject', $missingSubjects->count()) }} still missing marks
            </div>
            <div style="font-size: 13px; color: var(--d-text-secondary); margin-top: 4px;">
                {{ $missingSubjects->pluck('name')->join(', ', ' and ') }}
                {{ $missingSubjects->count() === 1 ? 'has' : 'have' }} no submitted entries
                @if($class)
                    for {{ $class->name }}
                @endif.
                Publishing now would send incomplete report cards to parents.
            </div>
            <div style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="{{ url('/admin/exams') }}" class="ds-btn ds-btn-primary text-sm">Remind subject teachers</a>
            </div>
        </div>
    @endif

    <div data-testid="exams-grid-card">
        <x-card padding="none">
            @if($marksError)
                {{-- The alert above is the error. A missing class, term or exam set stays the empty state. --}}
            @elseif(! $filtered)
                <div class="ds-table-empty ds-empty-state" data-testid="exams-empty">
                    <p class="ds-empty-state-title">Ready when you are</p>
                    <p class="ds-empty-state-desc">Select class, term and exam type above to view the marks grid.</p>
                </div>
            @elseif($students->isEmpty())
                <div class="ds-table-empty ds-empty-state" data-testid="exams-empty">
                    <p class="ds-empty-state-title">No marks found</p>
                    <p class="ds-empty-state-desc">Try adjusting the filters or check if data exists for this combination.</p>
                </div>
            @else
                <div data-testid="exams-marks-grid">
                    @include('admin.marks.results-table2')
                </div>
                @if(($type->name ?? '') === 'End Of Year')
                    <div class="px-4 py-4 flex items-center justify-end gap-2 flex-wrap">
                        <div class="flex items-center gap-1">
                            <span class="text-red-400 underline font-semibold">NOTE:</span>
                            <p class="text-sm">Download student report cards before finalizing promotion.</p>
                        </div>
                        @if($class)
                            @include('admin.marks.promotion')
                        @endif
                    </div>
                @endif
                <div class="dt-pagination px-4 ka-sheet-pages">
                    {{ $students->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>

</div>
