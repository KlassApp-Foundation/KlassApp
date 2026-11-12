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

    @php
        $classLabel = trim((string) ($exam->section->name ?? ''));
        if ($classLabel === '') {
            $classLabel = trim((string) ($exam->standard->name ?? ''));
        }
        $subjectLabel = $exam->subject ? (string) ($exam->subject->getRawOriginal('name') ?: $exam->subject->name) : '';
        $entryTitle = trim($classLabel.' '.$subjectLabel);
        if ($entryTitle === '') {
            $entryTitle = $isNursery ? 'Enter assessments' : 'Enter marks';
        }
        $setLabel = trim((string) ($exam->examType->name ?? $exam->academicTerm->name ?? ''));
        $lifeWord = strtolower((string) ($submissionState ?? ''));
        if ($lifeWord === '' || $lifeWord === 'not started') {
            $lifeWord = 'not submitted';
        }
    @endphp

    <h1 class="ka-h1">{{ $entryTitle }}</h1>
    @unless($isNursery)
        <div>
            <div style="font:700 1.05rem 'Sora',sans-serif;">{{ $setLabel !== '' ? $setLabel.' · out of 100' : 'Out of 100' }}</div>
            <div class="ka-entry-tally" data-testid="marks-entry-tally" data-entry-tally data-life="{{ $lifeWord }}">{{ $savedMarkCount }} saved · {{ $unsavedCount }} not saved yet · {{ $lifeWord }}</div>
        </div>
    @else
        <p class="ka-sub">{{ $exam->standard->name ?? 'Nursery' }} · {{ $exam->academicTerm->name }}</p>
    @endunless

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
                <a class="ka-chip {{ empty($selectedStreamId) ? 'is-on' : '' }}" href="{{ route('teacher.exam.marks.enter', $exam) }}" role="radio" aria-checked="{{ empty($selectedStreamId) ? 'true' : 'false' }}">All · {{ $rosterTotal }}</a>
                @foreach($entryStreams as $stream)
                    <a class="ka-chip {{ (int) ($selectedStreamId ?? 0) === (int) $stream['id'] ? 'is-on' : '' }}" href="{{ route('teacher.exam.marks.enter', ['exam' => $exam, 'stream' => $stream['id']]) }}" role="radio" aria-checked="{{ (int) ($selectedStreamId ?? 0) === (int) $stream['id'] ? 'true' : 'false' }}">{{ $stream['label'] }} · {{ $streamCounts[$stream['label']] ?? 0 }}</a>
                @endforeach
            </div>
        </div>
    @endif

    <div id="marks-offline-banner" class="ka-offline" role="status" data-testid="marks-offline-banner" hidden>
        <div><strong>You're offline.</strong> Your marks are kept on this phone and will save by themselves when you're back online. <strong data-offline-count>{{ $unsavedCount }} not saved yet.</strong></div>
    </div>

    @if(($writesOpen ?? false) && ! $isNursery)
        <form id="marks-submit-form" method="POST" action="{{ route('teacher.exam.marks.submit', $exam) }}" hidden>
            @csrf
        </form>
    @endif

    @php
        $submitReady = ($canSubmit ?? false) && ($missingMarkCount ?? 0) === 0 && ($fixCount ?? 0) === 0 && ($unsavedCount ?? 0) === 0 && ($blankCount ?? 0) === 0;
    @endphp
    <div class="ka-sticky ka-marks-bar">
        @unless($isNursery)
            <p class="ka-progress" data-testid="marks-progress"><strong>{{ $enteredCount }} of {{ $total }}</strong> entered · {{ $fixCount }} to fix · {{ $unsavedCount }} not saved yet</p>
            @if(($missingMarkCount ?? 0) > 0)
                <p class="ka-still-needed" data-testid="marks-still-needed">A mark or ABS is missing for {{ $missingMarkCount }} {{ \Illuminate\Support\Str::plural('learner', $missingMarkCount) }}.</p>
            @endif
            <div style="display:flex;gap:10px;">
                <button type="button" class="ka-prev" data-testid="marks-previous" aria-label="Previous learner">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg>
                </button>
                <button type="button" class="ka-next" data-testid="marks-next">Next</button>
            </div>
        @endunless
        <div class="ka-marks-actions">
            <button type="submit" form="marks-save-form" class="ds-btn ds-btn-outline" data-testid="marks-save-draft">
                {{ $isNursery ? 'Save Assessments' : 'Save draft' }}
            </button>
            @unless($isNursery)
                <button type="submit" form="marks-submit-form" class="ds-btn ds-btn-primary ka-submit-approval" data-testid="marks-submit-button" @disabled(! $submitReady)>Submit for approval</button>
            @endunless
        </div>
        @unless($isNursery)
            <p class="ka-entry-help">Submit is ready when every learner has a mark or ABS, and every mark is saved.</p>
        @endunless
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
            <div class="ka-marks-sheet ka-entry-list" data-testid="marks-card-list">
                @foreach($allStudents as $student)
                @php
                    $storedMark = (string) ($savedMarks[$student->id] ?? '');
                    $enteredMark = (string) old('marks.'.$student->id, $storedMark);
                    $aboveMaximum = is_numeric($enteredMark) && (float) $enteredMark > 100;
                    $isBlank = trim($enteredMark) === '';
                    $isAbs = in_array(strtoupper(trim($enteredMark)), ['AB', 'ABS'], true);
                    $savedMatch = trim($storedMark) !== '' && strcasecmp(trim($enteredMark), trim($storedMark)) === 0;
                    $learnerName = trim(($student->userprofile->firstname ?? '').' '.($student->userprofile->lastname ?? ''));
                    if ($learnerName === '') {
                        $learnerName = (string) $student->name;
                    }
                    $streamLabel = (string) ($student->stream_label ?? '');
                    $meta = trim((string) ($student->registration_number ?? ''));
                    if ($streamLabel !== '') {
                        $meta = $meta === '' ? $streamLabel : $meta.' · '.$streamLabel;
                    }
                    if ($isBlank) {
                        $entryState = 'blank';
                        $statusLabel = 'Not entered';
                    } elseif ($aboveMaximum) {
                        $entryState = 'error';
                        $statusLabel = 'Above 100';
                    } elseif ($isAbs && $savedMatch) {
                        $entryState = 'absent';
                        $statusLabel = 'Saved · absent';
                    } elseif ($savedMatch) {
                        $entryState = 'saved';
                        $statusLabel = 'Saved';
                    } else {
                        $entryState = 'pending';
                        $statusLabel = 'Not saved yet';
                    }
                    $displayMark = $isAbs ? 'ABS' : $enteredMark;
                @endphp
                <div class="ka-entry-row" data-state="{{ $entryState }}" data-learner="{{ $student->id }}" data-testid="mark-student-{{ $student->id }}">
                    <div class="ka-entry-who">
                        <div class="ka-learner-name">{{ $learnerName }}</div>
                        @if($meta !== '')
                            <div class="ka-entry-meta" data-testid="mark-kls">{{ $meta }}</div>
                        @endif
                        <div class="ka-entry-status" data-entry-status>
                            @if($entryState === 'saved' || $entryState === 'absent')
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>
                            @endif
                            <span data-status-label>{{ $statusLabel }}</span>
                        </div>
                    </div>
                    <button type="button" class="ka-abs" data-testid="abs-toggle" aria-pressed="{{ $isAbs ? 'true' : 'false' }}" aria-label="Mark {{ $learnerName }} absent">ABS</button>
                    <input type="text"
                           name="marks[{{ $student->id }}]"
                           class="ka-mark"
                           inputmode="numeric"
                           enterkeyhint="next"
                           value="{{ $displayMark }}"
                           data-saved="{{ $storedMark }}"
                           data-learner-name="{{ $learnerName }}"
                           aria-label="Mark for {{ $learnerName }}"
                           aria-invalid="{{ $aboveMaximum ? 'true' : 'false' }}"
                           data-testid="mark-input-{{ $student->id }}">
                    <div data-mark-error data-testid="mark-above-maximum-{{ $student->id }}" @unless($aboveMaximum) hidden @endunless>Above 100</div>
                </div>
                @endforeach
            </div>
            <a href="{{ route('teacher.exam.marks.import.page', $exam) }}" class="ds-btn ds-btn-outline" style="min-height:48px" data-testid="marks-paste-upload">Paste or upload marks</a>
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
    var tally = document.querySelector('[data-entry-tally]');
    var submit = document.querySelector('[data-testid="marks-submit-button"]');
    var nextButton = document.querySelector('[data-testid="marks-next"]');
    var previousButton = document.querySelector('[data-testid="marks-previous"]');
    var offlineCount = banner ? banner.querySelector('[data-offline-count]') : null;
    var writesOpen = {{ ($writesOpen ?? false) ? 'true' : 'false' }};

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

    function rowOf(input) {
        return input.closest('[data-learner]');
    }

    function setStatus(row, state, label) {
        if (!row) return;
        row.setAttribute('data-state', state);
        var text = row.querySelector('[data-status-label]');
        if (text) text.textContent = label;
    }

    function savedSame(input) {
        var value = String(input.value).trim();
        var saved = String(input.getAttribute('data-saved') || '').trim();
        return saved !== '' && value.toUpperCase() === saved.toUpperCase();
    }

    function paint() {
        var entered = 0;
        var fix = 0;
        var unsaved = 0;
        var savedCount = 0;
        inputs.forEach(function (input) {
            if (isAbs(input.value) && String(input.value).trim().toUpperCase() !== 'ABS') {
                input.value = 'ABS';
            }
            var value = String(input.value).trim();
            var kind = kindOf(value);
            var above = kind === 'fix' && value !== '' && !isNaN(Number(value)) && Number(value) > 100;
            var same = savedSame(input);
            var row = rowOf(input);
            var focused = document.activeElement === input;
            var state = 'blank';
            var label = 'Not entered';
            if (above) {
                state = 'error';
                label = 'Above 100';
            } else if (focused && value !== '' && !same && !isAbs(value)) {
                state = 'typing';
                label = 'Typing';
            } else if (value === '') {
                state = 'blank';
                label = 'Not entered';
            } else if (isAbs(value) && same) {
                state = 'absent';
                label = 'Saved · absent';
            } else if (same) {
                state = 'saved';
                label = 'Saved';
            } else {
                state = 'pending';
                label = 'Not saved yet';
            }
            setStatus(row, state, label);
            var err = row && row.querySelector('[data-mark-error]');
            if (err) err.hidden = !above;
            input.setAttribute('aria-invalid', above ? 'true' : 'false');
            var absBtn = row && row.querySelector('[data-testid="abs-toggle"]');
            if (absBtn) absBtn.setAttribute('aria-pressed', isAbs(value) ? 'true' : 'false');
            if (kind === 'entered') entered++;
            else if (kind === 'blank') { /* counted by the missing line */ }
            else fix++;
            if (value !== '' && !same) unsaved++;
            if (same && kind === 'entered') savedCount++;
        });
        if (progress) {
            progress.innerHTML = '<strong>' + entered + ' of ' + inputs.length + '</strong> entered · ' + fix + ' to fix · ' + unsaved + ' not saved yet';
        }
        if (tally) {
            var life = tally.getAttribute('data-life') || tally.textContent.split('·').pop().trim() || 'not submitted';
            tally.setAttribute('data-life', life);
            tally.textContent = savedCount + ' saved · ' + unsaved + ' not saved yet · ' + life;
        }
        if (offlineCount) offlineCount.textContent = unsaved + ' not saved yet.';
        var ready = writesOpen && inputs.length > 0 && inputs.every(function (input) {
            var value = String(input.value).trim();
            if (!savedSame(input)) return false;
            if (isAbs(value)) return true;
            var number = Number(value);
            return value !== '' && !isNaN(number) && number <= 100;
        });
        if (submit) submit.disabled = !ready;
        nameNext();
    }

    function indexOfFocus() {
        return inputs.indexOf(document.activeElement);
    }

    function focusAt(index) {
        if (index < 0 || index >= inputs.length) return;
        inputs[index].focus();
        if (typeof inputs[index].select === 'function') inputs[index].select();
        nameNext();
    }

    function nameNext() {
        if (!nextButton) return;
        var index = indexOfFocus();
        var nextIndex = index < 0 ? 0 : index + 1;
        var next = inputs[nextIndex];
        if (!next) {
            nextButton.disabled = true;
            nextButton.textContent = 'Next';
        } else {
            nextButton.disabled = false;
            nextButton.textContent = 'Next: ' + (next.getAttribute('data-learner-name') || 'next learner');
        }
        if (previousButton) previousButton.disabled = index <= 0;
    }

    function showOffline() {
        if (banner) banner.hidden = false;
        paint();
    }

    function pending() {
        return inputs.some(function (input) {
            var value = String(input.value).trim();
            var saved = String(input.getAttribute('data-saved') || '').trim();
            return value.toUpperCase() !== saved.toUpperCase();
        });
    }

    function retry() {
        var data = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (response) {
            if (!response.ok && response.status !== 302) throw new Error('save failed');
            inputs.forEach(function (input) {
                var value = String(input.value).trim();
                input.setAttribute('data-saved', isAbs(value) ? 'ABS' : value);
            });
            if (banner) banner.hidden = true;
            paint();
        }).catch(function () {
            showOffline();
        });
    }

    inputs.forEach(function (input, index) {
        input.addEventListener('input', paint);
        input.addEventListener('focus', paint);
        input.addEventListener('blur', paint);
        input.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            focusAt(index + 1);
        });
    });

    form.addEventListener('click', function (event) {
        var button = event.target.closest('[data-testid="abs-toggle"]');
        if (!button) return;
        var row = button.closest('[data-learner]');
        var input = row && row.querySelector('input[name^="marks["]');
        if (!input) return;
        input.value = isAbs(input.value) ? '' : 'ABS';
        input.focus();
        paint();
    });

    function keepKeyboard(button, move) {
        if (!button) return;
        button.addEventListener('mousedown', function (event) { event.preventDefault(); });
        button.addEventListener('click', function () {
            var index = indexOfFocus();
            if (index < 0) index = move > 0 ? -1 : 1;
            focusAt(index + move);
        });
    }
    keepKeyboard(nextButton, 1);
    keepKeyboard(previousButton, -1);

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
