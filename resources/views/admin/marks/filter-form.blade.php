<div class="ds-card ds-card-padding-sm">
    <form method="GET" action="{{ route($filterRoute) }}" class="flex flex-wrap items-end gap-3" data-testid="exams-filter-form">
        <div class="w-full sm:w-40">
            <label for="term" class="ds-label">Term <span class="text-red-500">*</span></label>
            <select name="term" id="term" class="ds-form-select" required>
                <option value="">All terms</option>
                @foreach ($terms as $termOption)
                    <option value="{{ $termOption->id }}" @selected((string) request('term') === (string) $termOption->id)>
                        {{ $termOption->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-40">
            <label for="class" class="ds-label">Class <span class="text-red-500">*</span></label>
            <select name="class" id="class" class="ds-form-select" required>
                <option value="">All classes</option>
                @foreach ($classes as $classOption)
                    <option value="{{ $classOption->id }}" @selected((string) request('class') === (string) $classOption->id)>
                        {{ $classOption->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-48">
            <label for="examType" class="ds-label">Exam type <span class="text-red-500">*</span></label>
            <select name="examType" id="examType" class="ds-form-select" required>
                <option value="">All exam types</option>
                @foreach ($examTypes as $examType)
                    <option value="{{ $examType->id }}" @selected((string) request('examType') === (string) $examType->id)>
                        {{ $examType->name }}
                    </option>
                @endforeach
            </select>
        </div>

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
            <button type="submit" class="ds-btn ds-btn-primary text-sm">Apply filter</button>
            @if(request()->hasAny(['term', 'class', 'examType', 'stream']))
                <a href="{{ route($filterRoute) }}" class="ds-btn ds-btn-ghost text-sm">Clear</a>
            @endif
        </div>
    </form>
</div>
