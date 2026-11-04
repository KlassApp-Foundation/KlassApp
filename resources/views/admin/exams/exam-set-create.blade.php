@extends($layout)

@section('content')
@include('partials.ka-screen')
<div class="dashboard-shell px-4 md:px-6 py-4" data-testid="exam-set-create">
    @include('partials.message')
    <main class="ka-main">
    <nav class="ka-crumb" aria-label="Breadcrumb">
        <a href="{{ $isTeacher ? route('teacher.exam-sets.index') : route('admin.exam-sets.index') }}">Exam sets</a><span aria-hidden="true">/</span><span>New set</span>
    </nav>
    <div>
        <h1 class="ka-h1">New exam set</h1>
        <p class="ka-sub">Create this term's exam set. One set creates every exam for the term, class by class. Teachers then enter marks under Marks.</p>
    </div>
    @if($errors->any())
        <div class="ka-alert ka-alert-error" role="alert" data-testid="exam-set-error">
            <div>
                <strong>The exams weren't created</strong>
                <p style="margin:4px 0 0">{{ $errors->first() }} Nothing partial was kept from this attempt, so trying again won't make duplicates of exams that already exist.</p>
            </div>
        </div>
    @endif

    @if($terms->isEmpty())
        <div class="ds-card ds-card-padding-default mt-6" data-testid="exam-set-no-terms">
            <p class="text-sm" style="color: var(--d-muted);">No academic terms yet. Add a term first, then come back.</p>
        </div>
    @else
        <form method="POST" action="{{ $action }}" class="ds-card ds-card-padding-default mt-6" data-testid="exam-set-form">
            @csrf

            <div class="mb-5">
                <label class="form-label" for="exam-set-term">Term</label>
                <select name="academic_term_id" id="exam-set-term" class="ds-form-input w-full" required data-testid="exam-set-term">
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" @selected((int) old('academic_term_id', $selectedTermId) === (int) $term->id)>
                            {{ $term->name }}
                        </option>
                    @endforeach
                </select>
                @error('academic_term_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <fieldset class="mb-5">
                <legend class="form-label">Exam types</legend>
                <div class="flex flex-wrap gap-4" data-testid="exam-set-types">
                    @foreach($types as $type)
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="type_ids[]" value="{{ $type->id }}"
                                   @checked(in_array($type->id, old('type_ids', $defaultTypeIds)))>
                            <span>{{ $type->name }}@if($type->isShared()) <span class="text-xs" style="color: var(--d-muted);">(shared)</span>@endif</span>
                        </label>
                    @endforeach
                </div>
                @error('type_ids') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </fieldset>

            @unless($isTeacher)
                <div class="mb-3">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="whole_school" value="1" @checked(old('whole_school', true)) data-testid="exam-set-whole-school">
                        <span>Whole school (every class and stream)</span>
                    </label>
                </div>
            @endunless

            <div class="mb-5">
                <label class="form-label" for="exam-set-classes">Classes</label>
                <select name="standard_ids[]" id="exam-set-classes" class="ds-form-input w-full" multiple size="10" data-testid="exam-set-classes">
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(in_array($class->id, (array) old('standard_ids', [])))>{{ $class->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs mt-1" style="color: var(--d-muted);">Each class creates one exam per subject, on the class itself. Streams are not included unless you pick them below.</p>
                @if($isTeacher)
                    <p class="text-xs mt-1" style="color: var(--d-muted);">Only the classes you are class teacher of are listed.</p>
                @endif
                @error('standard_ids') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            @if($streams->isNotEmpty())
                <details class="mb-5">
                    <summary class="text-sm" style="cursor:pointer;">Pick specific streams instead (optional)</summary>
                    <div class="mt-3 grid grid-cols-2 gap-2" data-testid="exam-set-streams">
                        @foreach($streams as $stream)
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="stream_ids[]" value="{{ $stream->id }}" @checked(in_array($stream->id, (array) old('stream_ids', [])))>
                                <span>{{ $stream->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs mt-2" style="color: var(--d-muted);">A picked stream gets its own exam and its own teacher.</p>
                </details>
            @endif

            <button type="submit" class="ds-btn ds-btn-primary" data-testid="exam-set-submit" style="min-height:44px">Create exam set</button>
            <div id="exam-set-creating" hidden data-testid="exam-set-creating" aria-busy="true">
                <div class="ka-sk on" style="height:26px;width:55%"></div>
                <p class="ka-sub">Creating this term's exams… this takes a few seconds. You can leave this page.</p>
            </div>
        </form>
        <script>
        document.querySelector('[data-testid="exam-set-form"]')?.addEventListener('submit', function () {
            var node = document.getElementById('exam-set-creating');
            if (node) node.hidden = false;
        });
        </script>
    @endif
    </main>
</div>
@endsection
