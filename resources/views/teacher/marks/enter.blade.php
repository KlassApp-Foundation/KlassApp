@extends('layouts.teacher.layout')

@section('content')
@include('partials.ka-screen')
<div class="container-fluid w-full py-3 lg:mx-2">
<main class="ka-main ka-marks-page">

      {{-- Flash Success Message --}}
    @include('partials.message')
    @if($errors->any())
        <div class="ka-alert ka-alert-error" role="alert" data-testid="marks-submit-error">{{ $errors->first() }}</div>
    @endif
    <p class="ka-sub">Type ABS when a learner was absent. Marks are out of 100.</p>

    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $isNursery ? 'Enter Assessments' : 'Enter Marks' }}
            </h1>

            <p class="mt-1  text-gray-600 dark:text-gray-400">
                @if($isNursery)
                    {{ $exam->standard->name ?? 'Nursery' }} &bull; {{ $exam->academicTerm->name }}
                @else
                    {{ $exam->subject->name }} &bull; {{ $exam->academicTerm->name }}
                @endif
            </p>
            @if(($savedMarkCount ?? 0) > 0)
                <p class="ka-draft-saved" data-testid="marks-draft-saved">
                    Draft saved
                    @if(!empty($draftSavedAt))
                        {{ $draftSavedAt->timezone(config('app.timezone'))->format('j M Y, H:i') }}
                    @endif
                </p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @unless($isNursery)
                <a href="{{ route('teacher.exam.marks.template', $exam) }}"
                   class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                   style="min-height:44px;">
                    Download template
                </a>
                <a href="{{ route('teacher.exam.marks.import.page', $exam) }}" class="ds-btn ds-btn-outline ds-btn-md" data-testid="marks-import-button">Import from spreadsheet</a>
            @endunless
        </div>
    </div>

    @if(!empty($submissionState))
        <p class="mb-4 text-sm font-medium text-gray-800" data-testid="marks-entry-state">{{ $submissionState }}</p>
    @endif

    @if(!empty($returnNotice))
        <div role="status" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-950" data-testid="marks-return-reason">
            <strong>Returned.</strong> {{ $returnNotice }}
        </div>
    @endif

    @if(($savedMarkCount ?? 0) > 0)
        <div role="status" class="mb-4 rounded-lg border border-blue-300 bg-blue-50 p-4 text-blue-900" data-testid="saved-marks-note">
            <strong>{{ $savedMarkCount }}</strong> {{ \Illuminate\Support\Str::plural('mark', $savedMarkCount) }} already saved.
            <a href="{{ route('teacher.exam.marks.view', $exam) }}" class="font-semibold underline">View them</a>
        </div>
    @endif

    @php
        $life = 'saved';
        $stateText = strtolower((string) ($submissionState ?? ''));
        if (str_contains($stateText, 'approv')) { $life = 'approved'; }
        elseif (str_contains($stateText, 'submit') || str_contains($stateText, 'return')) { $life = 'submitted'; }
    @endphp
    @include('partials.ka-lifecycle', ['current' => $life])

    @if(($showStreamColumn ?? false) && count($entryStreams ?? []) > 0)
        <div id="entry-stream-filters" class="ka-entry-streams" data-ka-filters data-testid="marks-entry-stream-filter">
            <button type="button" class="ka-filter-launch ds-btn ds-btn-outline" data-ka-open-filters="entry-stream-filters">Filters</button>
            <div class="ka-filter-backdrop" data-ka-close-filters></div>
            <div class="ka-filter-panel" role="radiogroup" aria-label="Stream">
                <h2 class="ka-h2 ka-filter-launch">Filters</h2>
                <span style="font-size:.85rem;font-weight:600" class="ka-filter-launch">Stream</span>
                <a class="ka-chip {{ empty($selectedStreamId) ? 'is-on' : '' }}" href="{{ route('teacher.exam.marks.enter', $exam) }}" role="radio" aria-checked="{{ empty($selectedStreamId) ? 'true' : 'false' }}">All streams</a>
                @foreach($entryStreams as $stream)
                    <a class="ka-chip {{ (int) ($selectedStreamId ?? 0) === (int) $stream['id'] ? 'is-on' : '' }}" href="{{ route('teacher.exam.marks.enter', ['exam' => $exam, 'stream' => $stream['id']]) }}" role="radio" aria-checked="{{ (int) ($selectedStreamId ?? 0) === (int) $stream['id'] ? 'true' : 'false' }}">{{ $stream['label'] }}</a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- design review: offline save -->
    <div id="marks-offline-banner" class="ka-alert ka-alert-warn" data-testid="marks-offline-banner" hidden>
        Working offline: marks will save when you're back online
    </div>

    @if($canSubmit ?? false)
        <form id="marks-submit-form" method="POST" action="{{ route('teacher.exam.marks.submit', $exam) }}" hidden>
            @csrf
        </form>
    @endif

    <div class="ka-sticky ka-marks-bar">
        @unless($isNursery)
            <p class="ka-progress" data-testid="marks-progress"><strong>{{ $enteredCount }} of {{ $total }}</strong> entered · {{ $fixCount }} to fix · {{ $blankCount }} blank</p>
            @if(($missingMarkCount ?? 0) > 0)
                <p class="ka-still-needed" data-testid="marks-still-needed">{{ $missingMarkCount }} {{ \Illuminate\Support\Str::plural('learner', $missingMarkCount) }} still need a mark or ABS.</p>
            @endif
        @endunless
        <div class="ka-marks-actions">
            <button type="submit" form="marks-save-form" class="ds-btn ds-btn-outline" data-testid="marks-save-draft">
                {{ $isNursery ? 'Save Assessments' : 'Save draft' }}
            </button>
            @if($canSubmit ?? false)
                <button type="submit" form="marks-submit-form" class="ds-btn ds-btn-primary ka-submit-approval" data-testid="marks-submit-button" @disabled(($missingMarkCount ?? 0) > 0)>Submit for approval</button>
            @endif
        </div>
    </div>

    <form id="marks-save-form" class="ka-marks-form" action="{{ route('teacher.exam.marks.save', $exam)  }}" method="POST">
        @csrf

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">

            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    @if($isNursery)
                        Nursery Domain Assessments
                    @else
                        Exam Marks for {{ $exam->subject->name }}
                    @endif
                </h2>
                <p>{{ $total }} students</p>
            </div>

            @if($allStudents->isEmpty())
                <div class="ka-empty" data-testid="marks-empty">
                    <h2 class="ka-h2">No learners in this class yet.</h2>
                </div>
            @elseif($isNursery)
            <div class="p-6 text-sm ka-nursery-sheet">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 ka-nursery-roster">
                    <thead>
                        <tr class="text-left uppercase font-semibold text-gray-700 dark:text-gray-300">
                            <th class="py-3 px-2">Student</th>
                            @if($showStreamColumn ?? false)
                                <th class="py-3 px-2">Stream</th>
                            @endif
                            @foreach($domains as $domain)
                                <th class="py-3 px-2">{{ $domain }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($allStudents as $student)
                        <tr>
                            <td class="py-3 px-2 text-gray-900 dark:text-white whitespace-nowrap">
                                <span>{{ $student->userprofile->firstname }}</span>
                                <span>{{ $student->userprofile->lastname }}</span>
                            </td>
                            @if($showStreamColumn ?? false)
                                <td class="py-3 px-2" data-testid="mark-stream-{{ $student->id }}">{{ $student->stream_label }}</td>
                            @endif
                            @foreach($domains as $domain)
                                @php
                                    $existing = $existingAssessments->get($student->id)?->get($domain);
                                    $currentRating = $existing?->rating;
                                @endphp
                                <td class="py-3 px-2">
                                    <select name="assessments[{{ $student->id }}][{{ $domain }}]"
                                            class="tw-form-control w-44 text-sm">
                                        <option value="">-- Select --</option>
                                        @foreach($ratings as $rating)
                                            <option value="{{ $rating }}"
                                                {{ $currentRating === $rating ? 'selected' : '' }}>
                                                {{ $rating }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-6 text-sm ka-marks-sheet" data-testid="marks-card-list">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 ka-marks-roster">

                    <thead>
                        <tr class="text-left uppercase font-semibold text-gray-700 dark:text-gray-300">
                            <th class="py-3">Student</th>
                            @if($showStreamColumn ?? false)
                                <th class="py-3">Stream</th>
                            @endif
                            <th class="py-3">Marks</th>
                            <th class="py-3">Out Of</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($allStudents as $student)
                        @php
                            $storedMark = $savedMarks[$student->id] ?? '';
                            $enteredMark = old('marks.'.$student->id, $storedMark);
                            $aboveMaximum = is_numeric($enteredMark) && (float) $enteredMark > 100;
                            $isBlank = trim((string) $enteredMark) === '';
                            $isAbs = in_array(strtoupper(trim((string) $enteredMark)), ['AB', 'ABS'], true);
                            $savedMatch = $storedMark !== '' && strcasecmp(trim((string) $enteredMark), trim((string) $storedMark)) === 0;
                            $learnerName = trim(($student->userprofile->firstname ?? '').' '.($student->userprofile->lastname ?? ''));
                            if ($learnerName === '') {
                                $learnerName = (string) $student->name;
                            }
                        @endphp
                        <tr class="ka-learner {{ $aboveMaximum ? 'is-invalid' : '' }}" data-learner="{{ $student->id }}">
                            <td class="py-3 ka-learner-id" data-testid="mark-student-{{ $student->id }}">
                                <div class="ka-learner-name">{{ $learnerName }}</div>
                                @if(filled($student->registration_number))
                                    <div class="ka-learner-kls" data-testid="mark-kls">{{ $student->registration_number }}</div>
                                @endif
                            </td>
                            @if($showStreamColumn ?? false)
                                <td class="py-3 ka-stream-cell" data-testid="mark-stream-{{ $student->id }}">{{ $student->stream_label }}</td>
                            @endif

                            <td class="ka-mark-cell">
                                <div class="ka-mark-controls">
                                    <!-- design review: saved check -->
                                    <span class="ka-saved-check" data-saved-check @unless($savedMatch) hidden @endunless aria-label="Saved">✓</span>
                                    <input type="text"
                                           name="marks[{{ $student->id }}]"
                                           class="tw-form-control ka-mark {{ $isBlank ? 'is-blank' : '' }} {{ $aboveMaximum ? 'is-invalid' : '' }}"
                                           inputmode="decimal"
                                           enterkeyhint="next"
                                           value="{{ $enteredMark }}"
                                           data-saved="{{ $storedMark }}"
                                           aria-label="Mark for {{ $learnerName }}"
                                           aria-invalid="{{ $aboveMaximum ? 'true' : 'false' }}"
                                           data-testid="mark-input-{{ $student->id }}">
                                    <button type="button" class="ka-abs" data-testid="abs-toggle" aria-pressed="{{ $isAbs ? 'true' : 'false' }}" aria-label="ABS for {{ $learnerName }}">ABS</button>
                                </div>
                                <div data-mark-error data-testid="mark-above-maximum-{{ $student->id }}" @unless($aboveMaximum) hidden @endunless>Above 100. Check the mark.</div>
                                <span class="ka-not-saved" data-not-saved hidden>Not saved yet</span>
                            </td>

                            <td class="ka-hide-sm">
                                <input type="number"
                                       name="out_of[{{ $student->id }}]"
                                       value="100"
                                       class="tw-form-control w-24">
                            </td>
                        </tr>
                        @endforeach

                    </tbody>

                </table>
            </div>
            @endif

            @if($correctionReasonRequired ?? false)
            <div class="ka-sticky" style="position:static">
                <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3">
                    <label for="correction_reason" class="block text-sm font-medium mb-1" style="color:#92400E;">
                        Reason for correcting submitted marks <span class="text-red-500">*</span>
                    </label>
                    <textarea id="correction_reason" name="correction_reason" rows="3" minlength="10" maxlength="500" required
                              class="tw-form-control w-full"
                              placeholder="e.g. Marks were entered against the wrong column.">{{ old('correction_reason') }}</textarea>
                    <p class="mt-1 text-xs" style="color:#92400E;">Recorded in the marks audit log and sent to your school admin.</p>
                </div>
            </div>
            @endif

        </div>

    </form>

</main>
</div>
<script>
(function () {
    var form = document.getElementById('marks-save-form');
    if (!form) return;
    var banner = document.getElementById('marks-offline-banner');
    var inputs = Array.prototype.slice.call(form.querySelectorAll('input[name^="marks["]'));
    var progress = document.querySelector('[data-testid="marks-progress"]');

    function isAbs(value) {
        var text = String(value || '').trim().toUpperCase();
        return text === 'ABS' || text === 'AB';
    }

    function kindOf(value) {
        var text = String(value || '').trim();
        if (text === '') return 'blank';
        if (isAbs(text)) return 'entered';
        var number = Number(text);
        if (text !== '' && !isNaN(number)) return number > 100 ? 'fix' : 'entered';
        return 'fix';
    }

    function paint() {
        var entered = 0;
        var fix = 0;
        var blank = 0;
        inputs.forEach(function (input) {
            var kind = kindOf(input.value);
            var row = input.closest('tr');
            var above = kind === 'fix' && !isNaN(Number(input.value)) && Number(input.value) > 100;
            input.classList.toggle('is-blank', String(input.value).trim() === '');
            input.classList.toggle('is-invalid', above);
            if (row) row.classList.toggle('is-invalid', above);
            var err = row && row.querySelector('[data-mark-error]');
            if (err) err.hidden = !above;
            input.setAttribute('aria-invalid', above ? 'true' : 'false');
            var absBtn = row && row.querySelector('[data-testid="abs-toggle"]');
            if (absBtn) absBtn.setAttribute('aria-pressed', isAbs(input.value) ? 'true' : 'false');
            var saved = input.getAttribute('data-saved') || '';
            var same = String(input.value).trim().toUpperCase() === String(saved).trim().toUpperCase();
            var check = row && row.querySelector('[data-saved-check]');
            if (check) check.hidden = !(saved.trim() !== '' && same);
            var dirty = row && row.querySelector('[data-not-saved]');
            if (dirty) dirty.hidden = same;
            if (kind === 'entered') entered++;
            else if (kind === 'blank') blank++;
            else fix++;
        });
        if (progress) {
            progress.innerHTML = '<strong>' + entered + ' of ' + inputs.length + '</strong> entered · ' + fix + ' to fix · ' + blank + ' blank';
        }
    }

    function showOffline() {
        if (banner) banner.hidden = false;
    }

    function pending() {
        return inputs.some(function (input) {
            return String(input.value).trim().toUpperCase() !== String(input.getAttribute('data-saved') || '').trim().toUpperCase();
        });
    }

    function retry() {
        var data = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok) throw new Error('save failed');
            inputs.forEach(function (input) {
                input.setAttribute('data-saved', String(input.value).trim());
            });
            if (banner) banner.hidden = true;
            paint();
        }).catch(function () {
            showOffline();
        });
    }

    inputs.forEach(function (input, index) {
        input.addEventListener('input', paint);
        input.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            var next = inputs[index + 1];
            if (next) next.focus();
        });
    });

    form.addEventListener('click', function (event) {
        var button = event.target.closest('[data-testid="abs-toggle"]');
        if (!button) return;
        var row = button.closest('tr');
        var input = row && row.querySelector('input[name^="marks["]');
        if (!input) return;
        if (isAbs(input.value)) {
            input.value = '';
        } else {
            input.value = 'ABS';
        }
        paint();
    });

    form.addEventListener('submit', function (event) {
        if (navigator.onLine === false) {
            event.preventDefault();
            showOffline();
        }
    });

    window.addEventListener('offline', showOffline);
    window.addEventListener('online', function () {
        if (pending()) retry();
        else if (banner) banner.hidden = true;
    });

    if (navigator.onLine === false) showOffline();
    paint();
})();
</script>
@endsection
