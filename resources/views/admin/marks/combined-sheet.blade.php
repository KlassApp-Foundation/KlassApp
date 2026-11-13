@php
    $historyQuery = array_filter([
        'section' => request('class'),
        'term' => request('term'),
        'examType' => request('examType'),
    ], fn ($value) => $value !== null && $value !== '');
    $historyUrl = url('/admin/marks/history').($historyQuery === [] ? '' : '?'.http_build_query($historyQuery));
    $approvedSubjects = collect($subjects)->filter(fn ($subject) => ($subject['submission'] ?? '') === 'Approved')->count();
    $savedSubjects = collect($subjects)->filter(fn ($subject) => ($subject['submission'] ?? '') === 'Saved, not submitted')->pluck('name')->values();
    $pillClass = function (string $label): string {
        return match ($label) {
            'Approved' => 'ka-life-approved',
            'Submitted', 'Locked' => 'ka-life-submitted',
            'Returned' => 'ka-life-returned',
            'Saved, not submitted' => 'ka-life-saved',
            default => 'ka-life-muted',
        };
    };
@endphp
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4 ka-marks-layout" data-testid="exams-marks">

    <div class="ka-block-title ds-page-head" data-testid="exams-page-head">
        <div>
            <h1 class="ds-page-head-title ka-h1">Marks</h1>
        </div>
    </div>

    @if(($filterRoute ?? '') === 'admin.marks.filter')
        <div class="ka-block-nav">
            @include('partials.ka-marks-nav', ['marksNav' => 'sheet'])
        </div>
        <div class="ka-block-life">
            @include('partials.ka-lifecycle', ['current' => 'saved'])
        </div>
    @endif

    <div class="ka-block-message">
        @include('partials.message')
    </div>

    @if(($hasAnyExams ?? true) === false)
        <div class="ds-card ds-card-padding-default ka-block-empty" data-testid="admin-marks-empty">
            <p class="text-sm" style="color: var(--d-muted);">No exams yet: create this term's exam set.</p>
            <a href="{{ route('admin.exam-sets.create') }}" class="ds-btn ds-btn-primary ds-btn-sm mt-3 inline-flex" data-testid="admin-exam-set-link">Create this term's exam set</a>
        </div>
    @endif

    <div data-testid="exams-filter-card" class="ka-block-filters">
        @include('admin.marks.filter-form')
    </div>

    <div class="ka-block-tabs">
        @if($examSetTabs !== [])
            <div class="ka-exam-tabs" role="tablist" aria-label="Exam set" data-testid="marks-exam-tabs">
                @foreach ($examSetTabs as $tab)
                    @php
                        $tabType = $tab['type'];
                        $tabOn = (string) request('examType') === (string) $tabType->id;
                        $tabQuery = array_filter([
                            'year' => request('year'),
                            'term' => request('term'),
                            'class' => request('class'),
                            'stream' => request('stream'),
                            'examType' => $tabType->id,
                        ], fn ($value) => $value !== null && $value !== '');
                    @endphp
                    <a href="{{ route($filterRoute, $tabQuery) }}"
                       role="tab"
                       data-testid="marks-exam-tab"
                       data-exam-set="{{ mb_strtolower(trim($tabType->name)) }}"
                       data-tab-status="{{ $tab['key'] }}"
                       @if($tab['locked']) data-locked="1" @endif
                       aria-selected="{{ $tabOn ? 'true' : 'false' }}"
                       @if($tabOn) aria-current="page" @endif
                       class="ka-exam-tab {{ $tabOn ? 'is-on' : '' }} ka-status-{{ $tab['key'] }}">
                        <span class="ka-exam-name">
                            <span class="ka-exam-long">{{ trim($tabType->name) }}</span>
                            <span class="ka-exam-short">{{ $tab['short'] }}</span>
                            @if($tab['locked'])
                                <svg class="ka-lock" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Locked"><rect x="4" y="11" width="16" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 018 0v4"></path></svg>
                            @endif
                        </span>
                        <span class="ka-exam-status">
                            <span class="ka-exam-status-long ka-status-pill">{{ $tab['desktop'] }}</span>
                            <span class="ka-exam-status-short">{{ $tab['phone'] }}</span>
                        </span>
                        @if($tab['note'] !== '')
                            <span class="ka-exam-note">{{ $tab['note'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if($marksError)
        <div class="ka-alert ka-alert-error ka-marks-error ka-block-error" role="alert" data-testid="marks-sheet-error">
            {{ $marksError }}
        </div>
    @endif

    <div class="ka-block-legacy ka-legacy-kpis">
        @if($filtered)
            <div class="dashboard-kpi-grid mb-4" data-testid="exams-kpi-grid">
                <x-ds-kpi-card icon="users" :value="(string) $students->total()" label="Students" color="blue" />
                <x-ds-kpi-card icon="book" :value="(string) $subjectsCovered" label="Subjects covered" color="green" />
                <x-ds-kpi-card icon="exam" :value="(string) $subjects->count()" label="Subjects on grid" color="amber" />
            </div>
        @endif
        @if($filtered && $missingSubjects->isNotEmpty())
            <div class="ds-reminder-banner" data-testid="exams-reminder-banner">
                <div>{{ $missingSubjects->count() }} {{ \Illuminate\Support\Str::plural('subject', $missingSubjects->count()) }} still missing marks</div>
            </div>
        @endif
    </div>

    @if($filtered && $type)
        <div class="ka-block-meta">
            <div class="ka-sheet-summary ka-block-summary">
                <strong>{{ trim($type->name) }}</strong>
                · out of 100 per subject
                @if(collect($subjects)->count() > 0)
                    · {{ $approvedSubjects }} of {{ collect($subjects)->count() }} subjects approved
                @endif
                <span class="ka-summary-phone"> · by position · totals count approved subjects</span>
            </div>
            <div class="ka-sheet-actions ka-block-actions">
                <a href="{{ $historyUrl }}" class="ds-btn ds-btn-outline" data-testid="marks-history">History</a>
                <a href="{{ route($releaseRoute) }}" class="ds-btn ds-btn-primary" data-testid="marks-go-release">Go to Marks Release</a>
            </div>
        </div>
    @else
        <div class="ka-block-actions ka-sheet-actions">
            <a href="{{ $historyUrl }}" class="ds-btn ds-btn-outline" data-testid="marks-history">History</a>
            <a href="{{ route($releaseRoute) }}" class="ds-btn ds-btn-primary" data-testid="marks-go-release">Go to Marks Release</a>
        </div>
    @endif

    @if($filtered && collect($subjects)->isNotEmpty())
        <div class="ka-subject-chips ka-block-chips">
            @foreach ($subjects as $subject)
                <span class="ka-subject-chip">
                    <strong>{{ $subject['name'] }}</strong>
                    <span class="ka-life-pill {{ $pillClass((string) ($subject['submission'] ?? 'Not started')) }}" data-testid="marks-subject-state">
                        @if(($subject['submission'] ?? '') === 'Saved, not submitted')
                            <span class="ka-life-long">Saved, not submitted</span>
                            <span class="ka-life-short">Saved</span>
                        @else
                            {{ $subject['submission'] ?? 'Not started' }}
                        @endif
                    </span>
                </span>
            @endforeach
        </div>
    @endif

    <div data-testid="exams-grid-card" class="ka-block-grid">
        <x-card padding="none">
            @if($marksError)
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

    @if($filtered && ! $marksError)
        <p class="ka-sheet-foot ka-block-foot" data-testid="marks-sheet-note">
            Totals and positions count approved subjects. A dash means the subject's marks haven't been submitted yet. ABS means the learner was absent.
            @if($savedSubjects->isNotEmpty())
                {{ $savedSubjects->join(', ', ' and ') }} {{ $savedSubjects->count() === 1 ? 'is' : 'are' }} saved but not submitted.
            @endif
            Showing {{ $students->count() }} of {{ $students->total() }}.
        </p>
    @endif

</div>
