@include('partials.ka-screen')
@php
    $kept = array_filter([
        'year' => request('year') ?: ($selectedYearId ?? null),
        'term' => request('term'),
        'class' => request('class'),
        'stream' => request('stream'),
        'examType' => request('examType'),
    ], fn ($value) => $value !== null && $value !== '');
    $without = function (array $query, string $key): array {
        unset($query[$key]);

        return $query;
    };
@endphp
<div id="marks-sheet-filters" data-ka-filters>
    <form method="GET" action="{{ route($filterRoute) }}" class="ka-sheet-filters" data-testid="exams-filter-form">
        <div class="ka-year-term">
            <div class="ka-field ka-field-year">
                <label for="year" class="ds-label">Academic year</label>
                <select name="year" id="year" class="ds-form-select">
                    <option value="">All years</option>
                    @foreach ($years as $yearOption)
                        <option value="{{ $yearOption->id }}" @selected((string) ($selectedYearId ?? '') === (string) $yearOption->id)>
                            {{ $yearOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="ka-field ka-field-term">
                <label id="term-label" for="term" class="ds-label">Term</label>
                <div class="ka-term-pills" role="radiogroup" aria-labelledby="term-label">
                    @foreach ($terms as $termOption)
                        @php $termOn = (string) request('term') === (string) $termOption->id; @endphp
                        <a role="radio"
                           aria-checked="{{ $termOn ? 'true' : 'false' }}"
                           class="ka-choice {{ $termOn ? 'is-on' : '' }}"
                           href="{{ route($filterRoute, array_merge($without($kept, 'term'), ['term' => $termOption->id])) }}">{{ $termOption->name }}@if($termOption->status === 'current') · current @endif</a>
                    @endforeach
                </div>
                <div class="ka-term-select">
                    <select name="term" id="term" class="ds-form-select">
                        <option value="">All terms</option>
                        @foreach ($terms as $termOption)
                            <option value="{{ $termOption->id }}" @selected((string) request('term') === (string) $termOption->id)>
                                {{ $termOption->name }}@if($termOption->status === 'current') · current @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <button type="button" class="ka-filter-launch ka-class-launch ds-btn ds-btn-outline" data-ka-open-filters="marks-sheet-filters" data-testid="marks-filters-open" aria-haspopup="dialog">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
            {{ $filterLaunch ?? 'All classes · all streams' }}
        </button>
        <div class="ka-filter-backdrop" data-ka-close-filters></div>
        <div class="ka-filter-panel">
            <h2 class="ka-h2 ka-filter-launch">Filters</h2>
            <div class="ka-field ka-field-class">
                <label for="class" class="ds-label">Class</label>
                <select name="class" id="class" class="ds-form-select">
                    <option value="">All classes</option>
                    @foreach ($classes as $classOption)
                        <option value="{{ $classOption->id }}" @selected((string) request('class') === (string) $classOption->id)>
                            {{ $classOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="ka-field ka-field-stream">
                <span id="stream-label" class="ds-label">Stream</span>
                <div class="ka-stream-pills" role="radiogroup" aria-labelledby="stream-label">
                    @php $streamOn = ! request()->filled('stream'); @endphp
                    <a role="radio" aria-checked="{{ $streamOn ? 'true' : 'false' }}" class="ka-choice {{ $streamOn ? 'is-on' : '' }}" href="{{ route($filterRoute, $without($kept, 'stream')) }}">All</a>
                    @foreach ($streams as $streamOption)
                        @php
                            $streamLabel = $streamOption->stream !== null && $streamOption->stream !== '' ? $streamOption->stream : 'Stream';
                            $thisStream = (string) request('stream') === (string) $streamOption->id;
                        @endphp
                        <a role="radio" aria-checked="{{ $thisStream ? 'true' : 'false' }}" class="ka-choice {{ $thisStream ? 'is-on' : '' }}" href="{{ route($filterRoute, array_merge($without($kept, 'stream'), ['stream' => $streamOption->id])) }}">{{ $streamLabel }}</a>
                    @endforeach
                </div>
                <div class="ka-stream-select">
                    <select name="stream" id="stream" class="ds-form-select" data-testid="marks-stream-filter">
                        <option value="">All</option>
                        @foreach ($streams as $streamOption)
                            <option value="{{ $streamOption->id }}" @selected((string) request('stream') === (string) $streamOption->id)>
                                {{ $streamOption->stream !== null && $streamOption->stream !== '' ? $streamOption->stream : 'Stream' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if(request()->filled('examType'))
                <input type="hidden" name="examType" value="{{ request('examType') }}">
            @endif
            <div class="ka-sheet-apply">
                <button type="submit" class="ds-btn ds-btn-primary text-sm">Apply filter</button>
                @if(request()->hasAny(['year', 'term', 'class', 'examType', 'stream']))
                    <a href="{{ route($filterRoute) }}" class="ds-btn ds-btn-ghost text-sm">Clear</a>
                @endif
            </div>
        </div>
        @if($filtered && $students->isNotEmpty())
            <a href="{{ route($downloadRoute, request()->query()) }}" class="ds-btn ds-btn-outline ka-download-desk" data-testid="marks-sheet-download">Download mark sheet</a>
        @endif
    </form>
</div>
