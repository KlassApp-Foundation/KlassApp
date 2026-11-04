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
                <div class="exam-set-types" data-testid="exam-set-types">
                    @foreach($types as $type)
                        <label class="exam-set-type">
                            <input type="checkbox" name="type_ids[]" value="{{ $type->id }}"
                                   @checked(in_array($type->id, old('type_ids', $defaultTypeIds)))>
                            <span>{{ $type->name }}@if($type->isShared()) <span class="exam-set-hint">(shared)</span>@endif</span>
                        </label>
                    @endforeach
                </div>
                @error('type_ids') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </fieldset>

            @unless($isTeacher)
                <div class="mb-3">
                    <label class="exam-set-type">
                        <input type="checkbox" name="whole_school" value="1" @checked(old('whole_school', true)) data-testid="exam-set-whole-school">
                        <span>Whole school (every class)</span>
                    </label>
                    <p class="exam-set-hint mt-1">Class picks below are used only when this is unticked. Streams are never included unless you open a class and pick them.</p>
                </div>
            @endunless

            <fieldset class="mb-5" data-testid="exam-set-classes">
                <legend class="form-label">Classes</legend>
                <p class="exam-set-hint">Each class creates one exam per subject, on the class itself. Open a class to pick its streams too.</p>

                @forelse($classGroups as $group)
                    <div class="exam-set-level" data-testid="exam-set-level-{{ $group['key'] }}">
                        <p class="exam-set-level__label">{{ $group['label'] }}</p>
                        <div class="exam-set-chips">
                            @foreach($group['classes'] as $class)
                                @php
                                    $oldStreams = array_map('intval', (array) old('stream_ids', []));
                                    $openForOldPicks = collect($class['streams'])->pluck('id')->intersect($oldStreams)->isNotEmpty();
                                @endphp
                                <details class="exam-set-class" data-testid="exam-set-class-{{ $class['id'] }}" @if($openForOldPicks) open @endif>
                                    <summary class="exam-set-class__summary">
                                        <span class="exam-set-class__pick">
                                            <input type="checkbox" name="standard_ids[]" value="{{ $class['id'] }}"
                                                   @checked(in_array($class['id'], (array) old('standard_ids', [])))
                                                   data-testid="exam-set-class-pick-{{ $class['id'] }}">
                                            <span class="exam-set-class__name">{{ $class['name'] }}</span>
                                        </span>
                                        @if($class['streams'] !== [])
                                            <span class="exam-set-class__count">{{ count($class['streams']) }} streams</span>
                                        @endif
                                    </summary>
                                    <div class="exam-set-class__body">
                                        @if($class['streams'] === [])
                                            <p class="exam-set-hint">This class has no streams.</p>
                                        @else
                                            <div class="exam-set-streams" data-testid="exam-set-streams-{{ $class['id'] }}">
                                                @foreach($class['streams'] as $stream)
                                                    <label class="exam-set-stream">
                                                        <input type="checkbox" name="stream_ids[]" value="{{ $stream['id'] }}"
                                                               @checked(in_array($stream['id'], $oldStreams))
                                                               data-testid="exam-set-stream-pick-{{ $stream['id'] }}">
                                                        <span>{{ $stream['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <p class="exam-set-hint">A picked stream gets its own exam and its own teacher.</p>
                                        @endif
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="exam-set-hint" data-testid="exam-set-no-classes">No classes yet. Add a class first, then come back.</p>
                @endforelse

                @if($isTeacher)
                    <p class="exam-set-hint">Only the classes you are class teacher of are listed.</p>
                @endif
                @error('standard_ids') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </fieldset>

            <button type="submit" class="ds-btn ds-btn-primary" data-testid="exam-set-submit">Create exam set</button>
        </form>
    @endif
</div>
@endsection
