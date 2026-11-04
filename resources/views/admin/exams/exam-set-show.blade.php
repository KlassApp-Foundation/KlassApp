@extends($layout)

@section('content')
@include('partials.ka-screen')
@php
    $pill = function (string $label): string {
        return match ($label) {
            'Approved' => 'ka-pill ka-pill-green',
            'Submitted', 'Reopened' => 'ka-pill ka-pill-amber',
            'Returned' => 'ka-pill ka-pill-red',
            'In progress' => 'ka-pill ka-pill-blue',
            'Locked' => 'ka-pill ka-pill-solid',
            default => 'ka-pill ka-pill-muted',
        };
    };
@endphp
<div class="dashboard-shell px-4 md:px-6 py-4" data-testid="exam-set-page">
    @include('partials.message')
    <main class="ka-main">
        @if($set && $detail)
            <nav class="ka-crumb" aria-label="Breadcrumb">
                <a href="{{ route($indexRoute) }}">Exam sets</a><span aria-hidden="true">/</span><span>{{ $set->term->name ?? 'Term' }}</span>
            </nav>
            <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:16px">
                <div>
                    <h1 class="ka-h1">{{ $set->term->name ?? 'Term' }} exams</h1>
                    <p class="ka-sub">{{ $detail['classCount'] }} {{ \Illuminate\Support\Str::plural('class', $detail['classCount']) }}@if($detail['creator'] !== '') · created by {{ $detail['creator'] }}@endif</p>
                </div>
                <a href="{{ route($createRoute) }}" class="ds-btn ds-btn-primary" style="min-height:44px" data-testid="exam-set-create-link">New exam set</a>
            </div>

            @if(session('successmessage') && str_contains((string) session('successmessage'), 'already existed'))
                <div class="ka-alert ka-alert-warn" role="status" data-testid="exam-set-skipped">
                    <div><strong>Some exams were already in a set for this term.</strong> Those were skipped. Only new exams were added.</div>
                </div>
            @endif

            <div class="ka-kpis" data-testid="exam-set-kpis">
                <div class="ka-kpi"><div><b>{{ $detail['examCount'] }}</b><span>exams in this set</span></div></div>
                <div class="ka-kpi"><div><b>{{ $detail['markedCount'] }}</b><span>have marks</span></div></div>
                <div class="ka-kpi"><div><b>{{ $detail['noTeacher'] }}</b><span>subjects with no teacher</span></div></div>
            </div>

            <div id="exam-set-filters" data-ka-filters>
                <button type="button" class="ka-filter-launch ds-btn ds-btn-outline" data-ka-open-filters="exam-set-filters" style="min-height:44px">Filters</button>
                <div class="ka-filter-backdrop" data-ka-close-filters></div>
                <form method="GET" action="{{ route($showRoute, ['batch' => $set->id]) }}" class="ka-filter-panel" data-testid="exam-set-filters">
                    <h2 class="ka-h2 ka-filter-launch">Filters</h2>
                    <a href="{{ route($showRoute, ['batch' => $set->id]) }}" class="ka-chip {{ $filterType ? '' : 'is-on' }}">All types</a>
                    @foreach($detail['types'] as $typeId => $typeName)
                        <a href="{{ route($showRoute, ['batch' => $set->id, 'type' => $typeId]) }}" class="ka-chip {{ (int) $filterType === (int) $typeId ? 'is-on' : '' }}">{{ $typeName }}</a>
                    @endforeach
                    <label for="exam-set-class" style="font-size:.8rem;color:#64748B">Class</label>
                    <select name="class" id="exam-set-class" class="ds-form-select" style="min-height:44px" onchange="this.form.submit()">
                        <option value="">All classes</option>
                        @foreach($detail['classOptions'] as $classId => $className)
                            <option value="{{ $classId }}" @selected((int) $filterClass === (int) $classId)>{{ $className }}</option>
                        @endforeach
                    </select>
                    @if($filterType)<input type="hidden" name="type" value="{{ $filterType }}">@endif
                    <button type="submit" class="ds-btn ds-btn-primary ka-filter-launch" style="min-height:48px">Show classes</button>
                </form>
            </div>

            <div data-testid="exam-set-exams" style="display:flex;flex-direction:column;gap:12px">
                @forelse($detail['classes'] as $classId => $class)
                    <section class="ka-card" data-testid="exam-set-class-{{ $classId }}">
                        <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;background:#FAFAF5;flex-wrap:wrap">
                            <h2 class="ka-h2">{{ $class['name'] }}</h2>
                            <span data-testid="exam-set-class-count-{{ $classId }}">{{ $class['count'] }} {{ \Illuminate\Support\Str::plural('exam', $class['count']) }}</span>
                        </div>
                        <div style="overflow-x:auto">
                            <table class="ka-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Subject</th>
                                        <th scope="col">Teacher</th>
                                        @foreach($detail['types'] as $typeId => $typeName)
                                            @if($filterType === null || (int) $filterType === (int) $typeId)
                                                <th scope="col">{{ $typeName }}</th>
                                            @endif
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($class['rows'] as $row)
                                        <tr>
                                            <th scope="row">{{ $row['subject'] }}</th>
                                            <td>
                                                @if($row['teacher'] !== '')
                                                    {{ $row['teacher'] }}
                                                @else
                                                    <span class="ka-pill ka-pill-amber">No teacher</span>
                                                @endif
                                            </td>
                                            @foreach($detail['types'] as $typeId => $typeName)
                                                @if($filterType === null || (int) $filterType === (int) $typeId)
                                                    @php $cell = $row['cells'][$typeId] ?? null; @endphp
                                                    <td>
                                                        @if($cell)
                                                            <span class="{{ $pill($cell['label']) }}">{{ $cell['label'] }}</span>
                                                            <span data-testid="exam-set-teacher-{{ $cell['id'] }}" class="ka-sr">{{ $cell['teacher'] }}</span>
                                                            @if($canRemove && ! $cell['has_marks'])
                                                                <button type="button" class="ds-btn ds-btn-ghost" style="min-height:36px;color:#B91C1C" data-ka-ask-remove="exam-set-remove-dialog-{{ $cell['id'] }}" aria-label="Remove {{ $row['subject'] }} {{ $typeName }}">Remove</button>
                                                                <dialog id="exam-set-remove-dialog-{{ $cell['id'] }}" style="border:1px solid #E2E8F0;border-radius:16px;padding:22px;max-width:420px">
                                                                    <h2 class="ka-h2">Remove {{ $class['name'] }} {{ $row['subject'] }}, {{ $typeName }}?</h2>
                                                                    <p class="ka-sub">It has no marks yet, so nothing is lost.</p>
                                                                    <form method="POST" action="{{ route('admin.exam-sets.exams.destroy', ['batch' => $set->id, 'exam' => $cell['id']]) }}" style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="button" class="ds-btn ds-btn-outline" style="min-height:44px" onclick="this.closest('dialog').close()">Keep exam</button>
                                                                        <button type="submit" class="ds-btn ds-btn-primary" style="min-height:44px;background:#B91C1C;border-color:#B91C1C" data-testid="exam-set-remove-{{ $cell['id'] }}">Remove exam</button>
                                                                    </form>
                                                                </dialog>
                                                            @elseif($cell['has_marks'])
                                                                <span data-testid="exam-set-locked-{{ $cell['id'] }}" title="Has marks, cannot be removed">Has marks</span>
                                                            @endif
                                                        @endif
                                                    </td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @empty
                    <div class="ka-empty" data-testid="exam-set-empty">
                        <h2 class="ka-h2">This set has no exams to show</h2>
                        <p class="ka-sub">They may already have existed for the term, or the filters hide every class.</p>
                    </div>
                @endforelse
            </div>
        @else
            <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:16px">
                <div>
                    <h1 class="ka-h1">Exam sets</h1>
                    <p class="ka-sub">The exams each term, set once for the whole school or for chosen classes.</p>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                    <form method="GET" action="{{ route($indexRoute) }}" id="exam-set-year-filters" data-ka-filters>
                        <button type="button" class="ka-filter-launch ds-btn ds-btn-outline" data-ka-open-filters="exam-set-year-filters">Filters</button>
                        <div class="ka-filter-backdrop" data-ka-close-filters></div>
                        <div class="ka-filter-panel">
                            <h2 class="ka-h2 ka-filter-launch" style="width:100%">Filters</h2>
                            <label for="exam-set-year" style="font-size:.8rem;color:#64748B">Year</label>
                            <select name="year" id="exam-set-year" class="ds-form-select" style="min-height:44px" onchange="this.form.submit()">
                                @foreach($years as $yearOption)
                                    <option value="{{ $yearOption->id }}" @selected((int) $selectedYear === (int) $yearOption->id)>{{ $yearOption->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="ds-btn ds-btn-primary ka-filter-launch" style="min-height:48px">Show this year</button>
                        </div>
                    </form>
                    <a href="{{ route($createRoute) }}" class="ds-btn ds-btn-primary" style="min-height:44px" data-testid="exam-set-create-link">New exam set</a>
                </div>
            </div>
            @if($byTerm->isEmpty())
                <div class="ka-empty" data-testid="exam-set-empty">
                    <h2 class="ka-h2">No exams set for {{ $currentTermName !== '' ? $currentTermName : 'this term' }} yet</h2>
                    <p class="ka-sub" style="max-width:360px">Create one set and every class gets its exams for the term. Teachers then see them under Marks.</p>
                    <a href="{{ route($createRoute) }}" class="ds-btn ds-btn-primary" style="min-height:44px">New exam set</a>
                </div>
            @endif
        @endif

        @if($byTerm->isNotEmpty())
            <div data-testid="exam-set-term-list" style="display:flex;flex-direction:column;gap:18px">
                @if($set)<h2 class="ka-h2">Sets by term</h2>@endif
                @foreach($byTerm as $termName => $termSets)
                    <section data-testid="exam-set-term-{{ \Illuminate\Support\Str::slug($termName) }}" style="display:flex;flex-direction:column;gap:10px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <h2 class="ka-h2">{{ $termName }}@if($years->firstWhere('id', $selectedYear)) , {{ $years->firstWhere('id', $selectedYear)->name }}@endif</h2>
                            @if($currentTermName !== '' && $currentTermName === $termName)
                                <span class="ka-pill ka-pill-green">Current term</span>
                            @endif
                        </div>
                        @foreach($termSets as $row)
                            @php
                                $card = $cards[$row->id] ?? ['classes' => 0, 'types' => [], 'exams' => 0, 'marked' => 0];
                                $pct = $card['exams'] > 0 ? (int) round($card['marked'] / $card['exams'] * 100) : 0;
                                $status = $card['exams'] === 0 ? 'No exams' : ($card['marked'] === 0 ? 'No marks yet' : ($card['marked'] >= $card['exams'] ? 'All have marks' : 'Marks coming in'));
                            @endphp
                            <a href="{{ route($showRoute, ['batch' => $row->id]) }}" class="ka-card ka-card-pad" data-testid="exam-set-link-{{ $row->id }}" style="display:flex;flex-wrap:wrap;align-items:center;gap:16px 24px;text-decoration:none;color:#1E293B">
                                <div style="flex:2 1 220px">
                                    <div style="font-weight:700">{{ $termName }} exams</div>
                                    <div style="font-size:.82rem;color:#64748B">{{ $card['classes'] }} {{ \Illuminate\Support\Str::plural('class', $card['classes']) }}</div>
                                </div>
                                <div style="flex:2 1 180px;display:flex;gap:6px;flex-wrap:wrap">
                                    @foreach($card['types'] as $typeName)
                                        <span class="ka-pill ka-pill-muted">{{ $typeName }}</span>
                                    @endforeach
                                </div>
                                <div style="flex:1 1 160px">
                                    <div style="font-size:.8rem;color:#475569"><strong>{{ $card['marked'] }}</strong> of {{ $card['exams'] }} exams have marks</div>
                                    <div class="ka-bar" aria-hidden="true"><span style="width:{{ $pct }}%"></span></div>
                                </div>
                                <span class="{{ $status === 'All have marks' ? 'ka-pill ka-pill-solid' : ($status === 'Marks coming in' ? 'ka-pill ka-pill-blue' : 'ka-pill ka-pill-muted') }}">{{ $status }}</span>
                            </a>
                        @endforeach
                    </section>
                @endforeach
            </div>
        @endif
    </main>
</div>
@endsection
