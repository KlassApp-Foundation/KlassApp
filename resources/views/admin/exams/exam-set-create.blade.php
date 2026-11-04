@extends($layout)

@section('content')
<div class="dashboard-shell px-4 md:px-6 py-4" data-testid="exam-set-create">
    @include('partials.message')

    <div class="ds-page-head">
        <div>
            <h1 class="ds-page-head-title">Create this term's exam set</h1>
            <p class="ds-page-head-sub">
                One action creates an exam for every subject in the classes you pick. Exams that already exist are skipped,
                so it is safe to run again after adding a subject.
            </p>
        </div>
    </div>

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
                <p class="text-xs mt-1" style="color: var(--d-muted);">Each class creates exams on all of its streams. A class with no streams gets one exam.</p>
                @if($isTeacher)
                    <p class="text-xs mt-1" style="color: var(--d-muted);">Only the classes you are class teacher of are listed.</p>
                @endif
                @error('standard_ids') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="ds-btn ds-btn-primary" data-testid="exam-set-submit">Create exam set</button>
        </form>
    @endif
</div>
@endsection
