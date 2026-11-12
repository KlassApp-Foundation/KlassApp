@include('partials.ka-screen')
<div class="ds-card ds-card-padding-sm" id="marks-sheet-filters" data-ka-filters>
    <button type="button" class="ka-filter-launch ds-btn ds-btn-outline" data-ka-open-filters="marks-sheet-filters" data-testid="marks-filters-open">Filters</button>
    <div class="ka-filter-backdrop" data-ka-close-filters></div>
    <form method="GET" action="{{ route($filterRoute) }}" class="ka-filter-panel flex flex-wrap items-end gap-3" data-testid="exams-filter-form">
        <h2 class="ka-h2 ka-filter-launch">Filters</h2>
        <div class="w-full sm:w-40">
            <label for="year" class="ds-label">Year</label>
            <select name="year" id="year" class="ds-form-select">
                <option value="">All years</option>
                @foreach ($years as $yearOption)
                    <option value="{{ $yearOption->id }}" @selected((string) ($selectedYearId ?? '') === (string) $yearOption->id)>
                        {{ $yearOption->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-40">
            <label for="term" class="ds-label">Term</label>
            <select name="term" id="term" class="ds-form-select">
                <option value="">All terms</option>
                @foreach ($terms as $termOption)
                    <option value="{{ $termOption->id }}" @selected((string) request('term') === (string) $termOption->id)>
                        {{ $termOption->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-40">
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

        @if(request()->filled('examType'))
            <input type="hidden" name="examType" value="{{ request('examType') }}">
        @endif

        <div class="w-full sm:w-40">
            <label for="stream" class="ds-label">Stream</label>
            <select name="stream" id="stream" class="ds-form-select" data-testid="marks-stream-filter">
                <option value="">Whole class</option>
                @foreach ($streams as $streamOption)
                    <option value="{{ $streamOption->id }}" @selected((string) request('stream') === (string) $streamOption->id)>
                        {{ $streamOption->stream !== null && $streamOption->stream !== '' ? $streamOption->stream : 'Stream' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2 pb-px">
            <button type="submit" class="ds-btn ds-btn-primary text-sm" style="min-height:44px">Apply filter</button>
            @if(request()->hasAny(['year', 'term', 'class', 'examType', 'stream']))
                <a href="{{ route($filterRoute) }}" class="ds-btn ds-btn-ghost text-sm">Clear</a>
            @endif
        </div>
    </form>
</div>
