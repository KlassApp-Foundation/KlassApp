<div class="checklist-onboarding" data-testid="checklist-onboarding" @if(!$enabled) hidden @endif>
    @if($enabled)
<link rel="stylesheet" href="{{ asset('css/toshi-checklist-panel.css') }}?v={{ @filemtime(public_path('css/toshi-checklist-panel.css')) }}">

        @if($phase === 'idle')
            <div class="t-card t-welcome" data-testid="checklist-card-welcome" data-checklist-card="welcome">
                <h3>Toshi</h3>
                <p>I'm Toshi, your school's assistant.</p>
                <ul class="t-points">
                    <li>Set up the school from its checklist</li>
                    <li>Answer questions about the school</li>
                    <li>Help with everyday tasks</li>
                </ul>
                <p>Nothing is saved without your approval.</p>
                <div class="t-actions">
                    <button type="button" class="t-btn" wire:click="start" data-testid="onboard-from-checklist">Set up from checklist</button>
                </div>
            </div>
            @if(($card['kind'] ?? '') === 'hint')
                <p class="t-note" data-testid="checklist-hint">{{ $card['message'] }}</p>
            @endif
        @endif

        @if(($card['kind'] ?? '') === 'agreement' || ($phase === 'agreement' && ($card['kind'] ?? '') !== 'refused'))
            <div class="t-card" data-testid="checklist-card-agreement" data-checklist-card="agreement">
                <h3>Agreement</h3>
                <p>The checklist holds children's names, classes, and parent phone numbers.</p>
                <p>Nothing is saved until you approve each step.</p>
                <label class="t-check">
                    <input type="checkbox" wire:model.live="agreed" data-testid="checklist-agree" data-checklist-focus>
                    <span>I agree on behalf of the school</span>
                </label>
                <p class="t-legal" id="checklist-agree-hint">
                    <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>
                    <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms</a>
                </p>
                <div class="t-actions">
                    <button type="button" class="t-btn" wire:click="confirmAgreement" data-testid="confirm-agreement" @disabled(! $agreed) @if(! $agreed) aria-describedby="checklist-agree-hint" @endif>Continue</button>
                </div>
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'upload')
            <div class="t-card" data-testid="checklist-card-upload" data-checklist-card="upload" wire:loading.attr="aria-busy" wire:target="ingest,importPickedSheet">
                <h3>Add the checklist</h3>
                <p>Drop the school's checklist workbook, or choose it from Google Drive. Nothing is saved until you approve each step.</p>
                <label class="t-drop" data-testid="checklist-drop" tabindex="0" data-checklist-focus
                       ondragover="event.preventDefault(); this.classList.add('is-drag');"
                       ondragleave="this.classList.remove('is-drag');"
                       ondrop="event.preventDefault(); this.classList.remove('is-drag'); var input=this.querySelector('input[type=file]'); if(input && event.dataTransfer && event.dataTransfer.files.length){ input.files = event.dataTransfer.files; input.dispatchEvent(new Event('change', {bubbles:true})); }"
                       onkeydown="if(event.key==='Enter'||event.key===' '){ event.preventDefault(); var input=this.querySelector('input[type=file]'); if(input) input.click(); }">
                    <input class="t-file" type="file" wire:model="workbook" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" data-testid="checklist-upload">
                    @if($workbook)
                        <strong data-testid="checklist-file-name">{{ $workbook->getClientOriginalName() }}</strong>
                    @else
                        <strong>Drop the checklist here</strong>
                        <span>or click to choose an .xlsx file</span>
                    @endif
                </label>
                @if(filled(config('services.google.client_id')))
                    <button
                        type="button"
                        class="t-btn secondary t-drive"
                        data-testid="connect-google-sheet"
                        data-client-id="{{ config('services.google.client_id') }}"
                        data-app-id="{{ config('services.google.project_number') }}"
                        data-picker-key="{{ config('services.google.picker_key') }}"
                        data-scope="https://www.googleapis.com/auth/drive.file"
                        onclick="window.klassConnectSheet(this)"
                    ><img src="{{ asset('images/brand/google-drive.svg') }}" alt="" width="20" height="20"> Choose from Google Drive</button>
                @endif
                <div class="t-link">
                    <input class="t-text" type="url" wire:model.live="sheetUrl" placeholder="Paste a Google Drive or Sheets link" data-testid="checklist-sheet-url" autocomplete="off" inputmode="url"
                           onkeydown="if(event.key==='Enter'){ event.preventDefault(); var node=this.closest('[wire\\:id]'); if(node && window.Livewire){ window.Livewire.find(node.getAttribute('wire:id')).call('ingest'); } }">
                    @if(trim($sheetUrl) !== '')
                        <button type="button" class="t-clear" wire:click="clearSheetLink" data-testid="checklist-sheet-clear" aria-label="Clear the link">×</button>
                    @endif
                </div>
                <div class="t-stream" wire:stream="checklist-progress" aria-live="polite" data-testid="checklist-progress"></div>
                <div class="t-actions">
                    <button type="button" class="t-btn" wire:click="ingest" data-testid="checklist-read" @disabled($workbook === null && trim($sheetUrl) === '' && $sheetFileId === '') @if($workbook === null && trim($sheetUrl) === '' && $sheetFileId === '') aria-describedby="checklist-read-hint" @endif>Read this checklist</button>
                </div>
                <p class="t-note" id="checklist-read-hint">Choose an .xlsx file or paste a link before reading.</p>
                @if($sheetNote !== '')
                    <p class="t-note" data-testid="checklist-sheet-note">{{ $sheetNote }}</p>
                @endif
                @error('workbook') <p class="t-note">{{ $message }}</p> @enderror
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'refused')
            <div class="t-card" data-testid="checklist-card-refused" data-checklist-card="refused">
                <h3 tabindex="-1" data-checklist-focus>Checklist not accepted</h3>
                <p>{{ $card['message'] }}</p>
                <p data-testid="checklist-dpa-where">Tab: {{ $card['tab'] }}. Row: {{ $card['row'] ?? '—' }}.</p>
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'summary')
            <div class="t-card" data-testid="checklist-card-summary" data-checklist-card="summary">
                <h3 tabindex="-1" @if(($card['flags'] ?? []) === []) data-checklist-focus @endif>{{ $card['school_name'] !== '' ? $card['school_name'] : 'School checklist' }}</h3>
                @php
                    $sheetSteps = $card['steps'] ?? [];
                    $structureKeys = ['terms', 'standards', 'subjects'];
                    $peopleKeys = ['teachers', 'students'];
                    $sheetSum = function (array $keys, string $field) use ($sheetSteps): int {
                        $total = 0;
                        foreach ($sheetSteps as $step) {
                            if (in_array($step['key'], $keys, true)) {
                                $total += (int) ($step[$field] ?? 0);
                            }
                        }
                        return $total;
                    };
                    $statByLabel = [];
                    foreach ($card['stats'] ?? [] as $stat) {
                        $statByLabel[$stat['label']] = (int) $stat['value'];
                    }
                    $peopleTotal = ($statByLabel['Teachers'] ?? 0) + ($statByLabel['Learners'] ?? 0) + ($statByLabel['Parents'] ?? 0);
                @endphp
                <div class="t-sheet" data-testid="checklist-sheet">
                    <div class="t-sheet-row t-sheet-head"><span>Workbook tab</span><span>Read</span><span>Ready</span><span>Flagged</span></div>
                    @foreach($sheetSteps as $step)
                        @if(in_array($step['key'], $structureKeys, true))
                            <div class="t-sheet-row" wire:key="step-{{ $step['key'] }}">
                                <span>{{ $step['label'] }}</span>
                                <b data-testid="count-read-{{ $step['key'] }}">{{ $step['read'] }}</b>
                                <b data-testid="count-accepted-{{ $step['key'] }}">{{ $step['accepted'] }}</b>
                                <b data-testid="count-flagged-{{ $step['key'] }}" @if((int) $step['flagged'] > 0) class="t-flag-figure" @endif>{{ $step['flagged'] }}</b>
                            </div>
                        @endif
                    @endforeach
                    <div class="t-sheet-row t-sheet-sub"><span>Structure rows</span><b>{{ $sheetSum($structureKeys, 'read') }}</b><b>{{ $sheetSum($structureKeys, 'accepted') }}</b><b>{{ $sheetSum($structureKeys, 'flagged') }}</b></div>
                    @foreach($sheetSteps as $step)
                        @if(in_array($step['key'], $peopleKeys, true))
                            <div class="t-sheet-row" wire:key="step-{{ $step['key'] }}">
                                <span>{{ $step['label'] }}</span>
                                <b data-testid="count-read-{{ $step['key'] }}">{{ $step['read'] }}</b>
                                <b data-testid="count-accepted-{{ $step['key'] }}">{{ $step['accepted'] }}</b>
                                <b data-testid="count-flagged-{{ $step['key'] }}" @if((int) $step['flagged'] > 0) class="t-flag-figure" @endif>{{ $step['flagged'] }}</b>
                            </div>
                        @endif
                    @endforeach
                    <div class="t-sheet-row t-sheet-sub"><span>People rows</span><b>{{ $sheetSum($peopleKeys, 'read') }}</b><b>{{ $sheetSum($peopleKeys, 'accepted') }}</b><b>{{ $sheetSum($peopleKeys, 'flagged') }}</b></div>
                    <div class="t-sheet-row t-total"><span>Total rows</span><b>{{ $sheetSum(array_merge($structureKeys, $peopleKeys), 'read') }}</b><b>{{ $sheetSum(array_merge($structureKeys, $peopleKeys), 'accepted') }}</b><b>{{ $sheetSum(array_merge($structureKeys, $peopleKeys), 'flagged') }}</b></div>
                </div>
                @include('livewire.partials.checklist-receipt', ['groups' => [
                    ['tone' => 'structure', 'name' => 'School structure', 'rows' => [
                        ['label' => 'Terms', 'value' => $statByLabel['Terms'] ?? 0],
                        ['label' => 'Classes', 'value' => $statByLabel['Classes'] ?? 0],
                        ['label' => 'Streams', 'value' => $statByLabel['Streams'] ?? 0],
                        ['label' => 'Subjects', 'value' => $statByLabel['Subjects'] ?? 0],
                    ]],
                    ['tone' => 'people', 'name' => 'People', 'rows' => [
                        ['label' => 'Teachers', 'value' => $statByLabel['Teachers'] ?? 0],
                        ['label' => 'Learners', 'value' => $statByLabel['Learners'] ?? 0],
                        ['label' => 'Parents', 'value' => $statByLabel['Parents'] ?? 0],
                    ], 'total' => ['label' => 'People', 'value' => $peopleTotal]],
                    ['tone' => 'flag', 'name' => 'Left out', 'rows' => [
                        ['label' => 'Flagged rows', 'value' => count($card['flags'] ?? [])],
                    ]],
                ]])
                @if($card['flags'] !== [])
                    <div class="t-flags">
                        <p><strong>Flagged rows</strong></p>
                        <ul>
                            @foreach($showAll ? $card['flags'] : array_slice($card['flags'], 0, 3) as $flag)
                                <li wire:key="flag-{{ $flag['tab'] }}-{{ $flag['row'] }}-{{ $loop->index }}" data-testid="checklist-flag">{{ $flag['tab'] }}, row {{ $flag['row'] ?? '—' }}: {{ $flag['field'] }}. {{ $flag['message'] }} {{ $flag['fix'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @if(count($card['flags']) > 3)
                        <button type="button" class="t-btn secondary" wire:click="toggleShown" data-testid="checklist-show-all">{{ $showAll ? 'Show less' : 'See every row' }}</button>
                    @endif
                    <div class="t-actions">
                        <button type="button" class="t-btn" wire:click="skipFlags" data-testid="skip-these-rows" data-checklist-focus>Skip these rows</button>
                        <button type="button" class="t-btn secondary" wire:click="stopForFix" data-testid="stop-fix-workbook">Stop, I'll fix the workbook</button>
                    </div>
                @elseif(is_array($follow) && ($follow['kind'] ?? '') === 'preview')
                    <div class="t-actions">
                        <button type="button" class="t-btn" wire:click="openNext" data-testid="checklist-next" data-checklist-focus>Next: {{ $follow['name'] ?? 'Terms' }}</button>
                    </div>
                @endif
                <p class="t-note">Class teachers listed: {{ $card['class_teachers_listed'] }}. Not written.</p>
                @if($progressLines !== [])
                    <div class="t-stream" aria-live="polite">
                        @foreach($progressLines as $progressLine)
                            @include('livewire.partials.checklist-progress-lines', ['line' => $progressLine])
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'result' && isset($card['created']))
            <div class="t-card" data-testid="checklist-card-result" data-checklist-card="result">
                <p class="t-kicker">{{ $card['name'] ?? $card['title'] }}</p>
                @if($progressLines !== [])
                    <div class="t-stream" aria-live="polite" data-testid="checklist-progress">
                        @foreach($progressLines as $progressLine)
                            @include('livewire.partials.checklist-progress-lines', ['line' => $progressLine])
                        @endforeach
                    </div>
                @endif
                @if(($card['receipt'] ?? []) !== [])
                    @include('livewire.partials.checklist-receipt', ['groups' => $card['receipt']])
                @else
                    <p data-testid="result-line">{{ $card['line'] ?? ('Created '.$card['created'].'.') }}</p>
                @endif
                <p data-testid="result-line" class="t-note">{{ $card['line'] ?? '' }}</p>
                <p><a href="{{ $card['href'] }}" data-testid="result-link">{{ $card['href_label'] }}</a></p>
                @if(is_array($follow) && ($follow['kind'] ?? '') === 'preview')
                    <div class="t-actions">
                        <button type="button" class="t-btn" wire:click="openNext" data-testid="checklist-next" data-checklist-focus>Next: {{ $follow['name'] ?? '' }}</button>
                    </div>
                @endif
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'preview')
            @php
                $preview = $card;
                $question = $preview['question'] ?? null;
                $waiting = is_array($question) && ! $questionCleared;
                $blocked = is_array($question) && ! empty($question['blocks_write']);
                $examples = $preview['examples'] ?? [];
                $shown = $showAll ? $examples : array_slice($examples, 0, 3);
                $exampleTotal = (int) ($preview['example_total'] ?? count($examples));
            @endphp
            <div class="t-card" data-testid="checklist-card-preview" data-checklist-card="preview" wire:loading.attr="aria-busy" wire:target="confirmStep">
                <p class="t-kicker" data-testid="checklist-step-kicker">Step {{ $preview['step_number'] }} of {{ $preview['step_total'] }}</p>
                <h3>{{ $preview['name'] ?? $preview['title'] }}</h3>
                <p data-testid="checklist-proposal">{{ $preview['proposal'] }}</p>
                @if(($preview['receipt'] ?? []) !== [])
                    @include('livewire.partials.checklist-receipt', ['groups' => $preview['receipt']])
                @else
                    <p data-testid="preview-counts">Create {{ $preview['create'] }}. Already exists {{ $preview['exists'] }}. Conflict {{ $preview['conflict'] }}.</p>
                @endif
                @if($shown !== [])
                    <div class="t-table-wrap">
                        <table class="t-table">
                            <thead><tr><th>On the sheet</th><th>Result</th></tr></thead>
                            <tbody>
                                @foreach($shown as $example)
                                    <tr wire:key="example-{{ $loop->index }}">
                                        <td>{{ $example['label'] }}</td>
                                        <td>{{ $example['bucket'] === 'exists' ? 'Already exists' : ($example['bucket'] === 'conflict' ? 'Conflict' : 'Create') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if(count($examples) > 3 || ($showAll && $exampleTotal > count($examples)))
                    <button type="button" class="t-btn secondary" wire:click="toggleShown" data-testid="checklist-show-all">{{ $showAll ? 'Show less' : 'See every row' }}</button>
                @endif
                @if($showAll && $exampleTotal > count($examples))
                    <p class="t-note">And {{ $exampleTotal - count($examples) }} more on the sheet.</p>
                @endif
                <div class="t-stream" wire:stream="checklist-progress" aria-live="polite" data-testid="checklist-progress"></div>

                @if($waiting)
                    <p data-testid="checklist-question">{{ $question['prompt'] }}</p>
                    <div class="t-actions">
                        @foreach($question['answers'] as $answer)
                            <button type="button" class="t-btn" wire:click="answerQuestion('{{ $answer['id'] }}')" data-testid="question-{{ $answer['id'] }}" @if($loop->first) data-checklist-focus @endif>{{ $answer['label'] }}</button>
                        @endforeach
                        @if(! $showOther)
                            <button type="button" class="t-btn secondary" wire:click="answerQuestion('other')" data-testid="question-other">Something else</button>
                        @endif
                    </div>
                    @if($showOther)
                        <textarea class="t-text" wire:model="otherText" maxlength="200" data-testid="question-other-text" rows="3"></textarea>
                        <div class="t-actions">
                            <button type="button" class="t-btn secondary" wire:click="saveNote" data-testid="question-save-note">Save this note</button>
                        </div>
                    @endif
                    @if($otherNote !== '')
                        <p class="t-note" data-testid="question-note">{{ $otherNote }} Nothing was written.</p>
                    @endif
                @else
                    @if($otherNote !== '')
                        <p class="t-note" data-testid="question-note">{{ $otherNote }} Nothing was written.</p>
                    @endif
                    @if($showChange)
                        <p>Keep the names and dates as they are written on the sheet, or type a note. A note does not change the sheet and does not write anything.</p>
                        <div class="t-actions">
                            <button type="button" class="t-btn secondary" wire:click="keepSheet" data-testid="keep-sheet">Keep the sheet as written</button>
                            @if(! $showOther)
                                <button type="button" class="t-btn secondary" wire:click="answerQuestion('other')" data-testid="question-other">Something else</button>
                            @endif
                        </div>
                        @if($showOther)
                            <textarea class="t-text" wire:model="otherText" maxlength="200" data-testid="question-other-text" rows="3"></textarea>
                            <div class="t-actions">
                                <button type="button" class="t-btn secondary" wire:click="saveNote" data-testid="question-save-note">Save this note</button>
                            </div>
                        @endif
                    @endif
                    @if(! $blocked)
                        <div class="t-actions">
                            <button type="button" class="t-btn" wire:click="confirmStep" data-testid="confirm-step" data-checklist-focus>{{ $preview['approve_label'] }}</button>
                            <div class="t-actions row">
                                @if(! $showChange)
                                    <button type="button" class="t-btn secondary" wire:click="openChange" data-testid="change-step">Change</button>
                                @endif
                                <button type="button" class="t-btn secondary" wire:click="skipStep" data-testid="skip-step">Skip</button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'done' || isset($card['completion']))
            @php $done = isset($card['checks']) ? $card : ($card['completion'] ?? null); @endphp
            @if(is_array($done))
                @php
                    $doneStats = [];
                    foreach ($done['stats'] ?? [] as $stat) {
                        $doneStats[$stat['label']] = (int) $stat['value'];
                    }
                    $donePeople = ($doneStats['Teachers'] ?? 0) + ($doneStats['Learners'] ?? 0) + ($doneStats['Parents'] ?? 0);
                @endphp
                <div class="t-card" data-testid="checklist-card-done" data-checklist-card="done">
                    <h3 tabindex="-1" data-checklist-focus>Done</h3>
                    @if($progressLines !== [])
                        <div class="t-stream" aria-live="polite">
                            @foreach($progressLines as $progressLine)
                                @include('livewire.partials.checklist-progress-lines', ['line' => $progressLine])
                            @endforeach
                        </div>
                    @endif
                    @include('livewire.partials.checklist-receipt', ['groups' => [
                        ['tone' => 'structure', 'name' => 'School structure', 'rows' => [
                            ['label' => 'Terms', 'value' => $doneStats['Terms'] ?? 0],
                            ['label' => 'Classes', 'value' => $doneStats['Classes'] ?? 0],
                            ['label' => 'Streams', 'value' => $doneStats['Streams'] ?? 0],
                            ['label' => 'Subjects', 'value' => $doneStats['Subjects'] ?? 0],
                        ]],
                        ['tone' => 'people', 'name' => 'People', 'rows' => [
                            ['label' => 'Teachers', 'value' => $doneStats['Teachers'] ?? 0],
                            ['label' => 'Learners', 'value' => $doneStats['Learners'] ?? 0],
                            ['label' => 'Parents', 'value' => $doneStats['Parents'] ?? 0],
                        ], 'total' => ['label' => 'People', 'value' => $donePeople]],
                        ['tone' => 'flag', 'name' => 'Left out', 'rows' => [
                            ['label' => 'Rows skipped', 'value' => (int) ($done['skipped'] ?? 0)],
                        ]],
                    ]])
                    <p data-testid="invites-ready">Invites ready: {{ $done['invites_ready'] }}, not sent</p>
                    <p class="t-note">No email or WhatsApp message was sent.</p>
                    <p><a href="{{ url('/admin/students') }}">Learners</a></p>
                    <p><a href="{{ url('/admin/academics') }}">Classes</a></p>
                    <p>KLS numbers present: {{ $done['kls_present'] ? 'yes' : 'no' }}@if(($done['kls_missing'] ?? 0) > 0) ({{ $done['kls_missing'] }} missing)@endif.</p>
                    <p>Phones in international format: {{ $done['phones_e164'] ? 'yes' : 'no' }}.</p>
                    @if(($done['checks'] ?? []) !== [])
                        <div class="t-table-wrap">
                            <table class="t-table">
                                <thead><tr><th>Class</th><th>In the school</th><th>On the checklist</th></tr></thead>
                                <tbody>
                                    @foreach($showAll ? $done['checks'] : array_slice($done['checks'], 0, 3) as $check)
                                        <tr wire:key="check-{{ $loop->index }}"><td>{{ $check['label'] }}</td><td>{{ $check['actual'] }}</td><td>{{ $check['expected'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(count($done['checks']) > 3)
                            <button type="button" class="t-btn secondary" wire:click="toggleShown" data-testid="checklist-show-all">{{ $showAll ? 'Show less' : 'See every row' }}</button>
                        @endif
                    @endif
                    <p class="t-note">Class teachers listed: {{ $done['class_teachers_listed'] }}. Not written.</p>
                </div>
            @endif
        @endif

        @if(($card['kind'] ?? '') === 'failed')
            <div class="t-card" data-testid="checklist-card-failed" data-checklist-card="failed">
                <h3 tabindex="-1" data-checklist-focus>{{ $card['title'] ?? 'Not applied' }}</h3>
                <p>{{ $card['message'] }}</p>
                @if($progressLines !== [])
                    <div class="t-stream" aria-live="polite">
                        @foreach($progressLines as $progressLine)
                            @include('livewire.partials.checklist-progress-lines', ['line' => $progressLine])
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'stopped')
            <div class="t-card" data-testid="checklist-card-stopped" data-checklist-card="stopped">
                <p>{{ $card['message'] }}</p>
                <button type="button" class="t-btn" wire:click="start" data-testid="onboard-from-checklist-again" data-checklist-focus>Set up from checklist</button>
            </div>
        @endif

        <script>
            if (!window.klassChecklistUi) {
                window.klassChecklistUi = true;
                window.klassChecklistCard = '';
                window.klassChecklistComposer = function () {
                    var panel = document.getElementById('toshi-panel');
                    if (!panel) {
                        return null;
                    }
                    var fields = panel.querySelectorAll('textarea.toshi-composer-input');
                    for (var i = 0; i < fields.length; i++) {
                        if (!fields[i].disabled && fields[i].getClientRects().length) {
                            return fields[i];
                        }
                    }
                    return null;
                };
                window.klassChecklistLift = function (el) {
                    var panel = document.getElementById('toshi-panel');
                    var viewport = window.visualViewport;
                    if (panel && viewport && window.innerWidth <= 1279 && getComputedStyle(panel).display !== 'none') {
                        panel.style.setProperty('top', viewport.offsetTop + 'px', 'important');
                        panel.style.setProperty('bottom', 'auto', 'important');
                        panel.style.setProperty('height', viewport.height + 'px', 'important');
                        panel.style.setProperty('max-height', viewport.height + 'px', 'important');
                    }
                    if (!el || !el.getBoundingClientRect) {
                        return;
                    }
                    var rect = el.getBoundingClientRect();
                    var viewTop = viewport ? viewport.offsetTop : 0;
                    var viewBottom = viewTop + (viewport ? viewport.height : window.innerHeight);
                    if (rect.top >= viewTop + 8 && rect.bottom <= viewBottom - 8) {
                        return;
                    }
                    var scroller = el.closest('.toshi-messages-area');
                    if (!scroller) {
                        return;
                    }
                    var scrollerRect = scroller.getBoundingClientRect();
                    if (rect.bottom > scrollerRect.bottom - 8) {
                        scroller.scrollTop += rect.bottom - scrollerRect.bottom + 12;
                    } else if (rect.top < scrollerRect.top + 8) {
                        scroller.scrollTop -= scrollerRect.top - rect.top + 12;
                    }
                };
                window.klassChecklistFocus = function (force) {
                    var root = document.querySelector('[data-testid="checklist-onboarding"]');
                    var panel = document.getElementById('toshi-panel');
                    if (!root || !panel || getComputedStyle(panel).display === 'none') {
                        return;
                    }
                    var cards = root.querySelectorAll('[data-checklist-card]');
                    var signature = Array.prototype.map.call(cards, function (card) {
                        return card.getAttribute('data-checklist-card');
                    }).join('|') || 'welcome';
                    if (!force && signature === window.klassChecklistCard) {
                        return;
                    }
                    window.klassChecklistCard = signature;
                    var targets = root.querySelectorAll('[data-checklist-focus]');
                    var target = null;
                    for (var i = 0; i < targets.length; i++) {
                        if (!targets[i].disabled && targets[i].getClientRects().length) {
                            target = targets[i];
                            break;
                        }
                    }
                    if (!target) {
                        target = window.klassChecklistComposer();
                    }
                    if (target && !target.disabled) {
                        target.focus({ preventScroll: true });
                        window.klassChecklistLift(target);
                    }
                };
                if (window.visualViewport) {
                    window.visualViewport.addEventListener('resize', function () {
                        var active = document.activeElement;
                        if (active && document.getElementById('toshi-panel') && document.getElementById('toshi-panel').contains(active)) {
                            window.klassChecklistLift(active);
                        }
                    });
                }
                document.addEventListener('keydown', function (event) {
                    if (event.key !== 'Escape') {
                        return;
                    }
                    var panel = document.getElementById('toshi-panel');
                    if (!panel || getComputedStyle(panel).display === 'none' || !panel.contains(document.activeElement)) {
                        return;
                    }
                    event.preventDefault();
                    if (window.toshiSetCollapsed) {
                        window.toshiSetCollapsed(true);
                    }
                });
                window.addEventListener('toshi-collapsed-changed', function (event) {
                    if (event.detail && event.detail.collapsed) {
                        window.klassChecklistCard = '';
                        return;
                    }
                    setTimeout(function () { window.klassChecklistFocus(true); }, 80);
                    setTimeout(function () { window.klassChecklistFocus(true); }, 350);
                });
                document.addEventListener('livewire:init', function () {
                    Livewire.hook('commit', function (payload) {
                        payload.succeed(function () { window.klassChecklistFocus(false); });
                    });
                    Livewire.on('checklist-open-picker', function (payload) {
                        var detail = Array.isArray(payload) ? (payload[0] || {}) : (payload || {});
                        var fileId = detail.fileId || '';
                        var button = document.querySelector('[data-testid="connect-google-sheet"]');
                        if (button && window.klassConnectSheet) {
                            window.klassConnectSheet(button, fileId);
                        }
                    });
                });
                window.klassConnectSheet = function (button, fileId) {
                    var root = button.closest('[wire\\:id]');
                    var component = root && window.Livewire ? window.Livewire.find(root.getAttribute('wire:id')) : null;
                    if (!component) {
                        return;
                    }
                    var clientId = button.getAttribute('data-client-id') || '';
                    var appId = button.getAttribute('data-app-id') || '';
                    var pickerKey = button.getAttribute('data-picker-key') || '';
                    var scope = button.getAttribute('data-scope') || '';
                    if (!clientId || !appId || !pickerKey || scope !== 'https://www.googleapis.com/auth/drive.file') {
                        component.call('noteSheet', 'picker-key');
                        return;
                    }
                    var googleReason = function (response) {
                        var reason = response && response.error ? String(response.error) : '';
                        if (!/^[a-z0-9_]{1,64}$/.test(reason)) {
                            component.call('noteSheet', 'picker-failed');
                            return;
                        }
                        component.call('noteSheet', 'google:' + reason);
                    };
                    var start = function () {
                        if (!window.google || !google.accounts || !google.accounts.oauth2 || !window.gapi) {
                            component.call('noteSheet', 'picker-failed');
                            return;
                        }
                        var tokenClient = google.accounts.oauth2.initTokenClient({
                            client_id: clientId,
                            scope: scope,
                            include_granted_scopes: false,
                            callback: function (response) {
                                if (!response || response.error || !response.access_token) {
                                    googleReason(response);
                                    return;
                                }
                                Promise.resolve(component.call('rememberSheetAccess', response.access_token)).then(function () {
                                    gapi.load('picker', { callback: function () {
                                        var sheets = new google.picker.DocsView(google.picker.ViewId.SPREADSHEETS);
                                        var files = new google.picker.DocsView(google.picker.ViewId.DOCS)
                                            .setMimeTypes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                                            .setIncludeFolders(true);
                                        if (fileId) {
                                            sheets.setFileIds(fileId);
                                            files.setFileIds(fileId);
                                        }
                                        var picker = new google.picker.PickerBuilder()
                                            .addView(sheets)
                                            .addView(files)
                                            .setOAuthToken(response.access_token)
                                            .setDeveloperKey(pickerKey)
                                            .setAppId(appId)
                                            .setCallback(function (data) {
                                                if (data.action === google.picker.Action.PICKED && data.docs && data.docs[0]) {
                                                    component.call('importPickedSheet', data.docs[0].id);
                                                }
                                            })
                                            .build();
                                        picker.setVisible(true);
                                    } });
                                }).catch(function () {
                                    component.call('noteSheet', 'picker-failed');
                                });
                            },
                        });
                        tokenClient.requestAccessToken({ prompt: 'consent' });
                    };
                    var pending = 0;
                    var ready = function () {
                        pending += 1;
                        if (pending === 2) {
                            start();
                        }
                    };
                    var load = function (src) {
                        var existing = document.querySelector('script[src="' + src + '"]');
                        var gsiReady = src.indexOf('gsi/client') === -1 || (window.google && google.accounts && google.accounts.oauth2);
                        if (existing && gsiReady) {
                            ready();
                            return;
                        }
                        var script = document.createElement('script');
                        script.src = src;
                        script.onload = ready;
                        script.onerror = function () { component.call('noteSheet', 'picker-failed'); };
                        document.head.appendChild(script);
                    };
                    load('https://accounts.google.com/gsi/client');
                    load('https://apis.google.com/js/api.js');
                };
            }
        </script>
    @endif
</div>
