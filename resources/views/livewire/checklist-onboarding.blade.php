<div class="checklist-onboarding" data-testid="checklist-onboarding" @if(!$enabled) hidden @endif>
    @if($enabled)
        <style>
            .checklist-onboarding { width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 8px 10px 4px; color: var(--d-text, #1E293B); overflow-x: hidden; }
            .checklist-card { background: var(--d-white, #fff); color: var(--d-text, #1E293B); border: 1px solid var(--d-border, #E2E8F0); border-radius: 12px; padding: 12px; margin: 0 0 10px; box-sizing: border-box; max-width: 100%; overflow-x: hidden; overflow-wrap: anywhere; transition: opacity 120ms ease; }
            .checklist-card h3 { margin: 0 0 8px; font-size: 16px; line-height: 1.3; color: inherit; font-family: Sora, "DM Sans", system-ui, sans-serif; }
            .checklist-card p, .checklist-card li { font-size: 14px; line-height: 1.4; color: inherit; overflow-wrap: anywhere; }
            .checklist-progress { margin: 0 0 8px; font-size: 12px; font-weight: 600; color: var(--d-text-secondary, #64748B); }
            .checklist-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin: 8px 0; }
            .checklist-stat { min-width: 0; background: var(--d-shell, #FAFAF5); border: 1px solid var(--d-border, #E2E8F0); border-radius: 10px; padding: 8px 10px; }
            .checklist-stat.wide { grid-column: 1 / -1; }
            .checklist-stat b { display: block; font-size: 22px; line-height: 1.1; font-family: Sora, "DM Sans", system-ui, sans-serif; color: var(--d-text, #1E293B); }
            .checklist-stat span { display: block; margin-top: 2px; font-size: 12px; color: var(--d-text-secondary, #64748B); }
            .checklist-stat small { display: block; margin-top: 4px; font-size: 12px; line-height: 1.35; color: var(--d-text, #1E293B); overflow-wrap: anywhere; }
            .checklist-steps { list-style: none; margin: 8px 0 0; padding: 0; }
            .checklist-steps li { padding: 4px 0; border-bottom: 1px solid var(--d-border, #E2E8F0); }
            .checklist-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
            .checklist-btn { min-height: 44px; width: 100%; border-radius: 10px; border: 1px solid var(--d-accent, #15803D); background: var(--d-accent, #15803D); color: var(--d-text-on-accent, #fff); font-size: 15px; font-weight: 600; padding: 10px 12px; cursor: pointer; box-sizing: border-box; }
            .checklist-btn.secondary { background: var(--d-white, #fff); color: var(--d-text, #1E293B); border-color: var(--d-border-strong, #CBD5E1); }
            .checklist-file, .checklist-text { min-height: 44px; width: 100%; max-width: 100%; box-sizing: border-box; font-size: 14px; }
            .checklist-note { font-size: 13px; color: var(--d-text-secondary, #64748B); margin: 6px 0 0; }
            .checklist-card a { color: var(--d-blue, #1E6FD9); }
            @media (prefers-color-scheme: dark) {
                .checklist-onboarding { color: var(--d-dark-fg, #CBD5E1); }
                .checklist-card { background: var(--d-dark-surface, #1E293B); color: var(--d-dark-fg, #CBD5E1); border-color: var(--d-dark-border, #334155); }
                .checklist-stat { background: var(--d-dark, #0F172A); border-color: var(--d-dark-border, #334155); }
                .checklist-stat b, .checklist-stat small { color: var(--d-dark-fg, #CBD5E1); }
                .checklist-btn.secondary { background: var(--d-dark, #0F172A); color: var(--d-dark-fg, #CBD5E1); border-color: var(--d-dark-border, #334155); }
            }
            @media (prefers-reduced-motion: reduce) {
                .checklist-card { transition: none; }
            }
        </style>

        @if($phase === 'idle')
            <button type="button" class="checklist-btn" wire:click="start" data-testid="onboard-from-checklist">Onboard from checklist</button>
            @if(($card['kind'] ?? '') === 'hint')
                <p class="checklist-note" data-testid="checklist-hint">{{ $card['message'] }}</p>
            @endif
        @endif

        @if(($card['kind'] ?? '') === 'agreement' || ($phase === 'agreement' && ($card['kind'] ?? '') !== 'refused'))
            <div class="checklist-card" data-testid="checklist-card-agreement">
                <h3>Data agreement</h3>
                <p>The checklist holds children's names, classes, and parent phone numbers. KlassApp uses it only to set up this school.</p>
                <p>Privacy covers what this school stores here. <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy</a></p>
                <p>Terms cover using KlassApp for this school. <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms</a></p>
                <p class="checklist-note">These links open the privacy and terms pages this app serves today.</p>
                @if($sheetNote !== '')
                    <p class="checklist-note" data-testid="checklist-sheet-note">{{ $sheetNote }}</p>
                @endif
                <div class="checklist-actions">
                    <button type="button" class="checklist-btn" wire:click="confirmAgreement" data-testid="confirm-agreement">I agree, continue</button>
                </div>
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'upload')
            <div class="checklist-card" data-testid="checklist-card-upload">
                <h3>Upload the checklist</h3>
                <p>Choose the school's checklist workbook (.xlsx). Nothing is saved until you approve each step.</p>
                <input class="checklist-file" type="file" wire:model="workbook" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" data-testid="checklist-upload">
                @if(filled(config('services.google.client_id')))
                    <button
                        type="button"
                        class="checklist-btn secondary"
                        data-testid="connect-google-sheet"
                        data-client-id="{{ config('services.google.client_id') }}"
                        data-app-id="{{ config('services.google.project_number') }}"
                        data-picker-key="{{ config('services.google.picker_key') }}"
                        data-scope="https://www.googleapis.com/auth/drive.file"
                        onclick="window.klassConnectSheet(this)"
                    >Connect Google Sheet</button>
                @endif
                <p class="checklist-note">Or paste a Google Sheets link. This demo has no Google account that can read a shared sheet, so download the sheet as .xlsx and upload that file. Keep the sheet shared only with people who should see it.</p>
                <input class="checklist-text" type="url" wire:model="sheetUrl" placeholder="https://docs.google.com/spreadsheets/..." data-testid="checklist-sheet-url" autocomplete="off">
                <div class="checklist-actions">
                    <button type="button" class="checklist-btn secondary" wire:click="submitSheetLink" data-testid="checklist-sheet-submit">Use this link</button>
                    <button type="button" class="checklist-btn" wire:click="ingest" data-testid="checklist-read">Read this checklist</button>
                </div>
                @if($sheetNote !== '')
                    <p class="checklist-note" data-testid="checklist-sheet-note">{{ $sheetNote }}</p>
                @endif
                @error('workbook') <p class="checklist-note">{{ $message }}</p> @enderror
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'refused')
            <div class="checklist-card" data-testid="checklist-card-refused">
                <h3>Checklist not accepted</h3>
                <p>{{ $card['message'] }}</p>
                <p data-testid="checklist-dpa-where">Tab: {{ $card['tab'] }}. Row: {{ $card['row'] ?? '—' }}.</p>
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'summary')
            <div class="checklist-card" data-testid="checklist-card-summary">
                <h3>{{ $card['school_name'] !== '' ? $card['school_name'] : 'School checklist' }}</h3>
                @include('livewire.partials.checklist-stats', ['stats' => $card['stats'] ?? []])
                @if($card['flags'] !== [])
                    <p><strong>Flagged rows</strong></p>
                    <ul>
                        @foreach($showAll ? $card['flags'] : array_slice($card['flags'], 0, 3) as $flag)
                            <li wire:key="flag-{{ $flag['tab'] }}-{{ $flag['row'] }}-{{ $loop->index }}" data-testid="checklist-flag">{{ $flag['tab'] }}, row {{ $flag['row'] ?? '—' }}: {{ $flag['field'] }}. {{ $flag['message'] }} {{ $flag['fix'] ?? '' }}</li>
                        @endforeach
                    </ul>
                    @if(count($card['flags']) > 3)
                        <button type="button" class="checklist-btn secondary" wire:click="toggleShown" data-testid="checklist-show-all">{{ $showAll ? 'Show less' : 'Show all' }}</button>
                    @endif
                    <div class="checklist-actions">
                        <button type="button" class="checklist-btn" wire:click="skipFlags" data-testid="skip-these-rows">Skip these rows</button>
                        <button type="button" class="checklist-btn secondary" wire:click="stopForFix" data-testid="stop-fix-workbook">Stop, I'll fix the workbook</button>
                    </div>
                @endif
                <ul class="checklist-steps">
                    @foreach($card['steps'] as $step)
                        <li wire:key="step-{{ $step['key'] }}">
                            {{ $step['label'] }}
                            <span data-testid="count-read-{{ $step['key'] }}">Read {{ $step['read'] }}</span>,
                            <span data-testid="count-accepted-{{ $step['key'] }}">accepted {{ $step['accepted'] }}</span>,
                            <span data-testid="count-flagged-{{ $step['key'] }}">flagged {{ $step['flagged'] }}</span>.
                        </li>
                    @endforeach
                </ul>
                <p class="checklist-note">Class teachers listed: {{ $card['class_teachers_listed'] }}. This demo does not write class teachers.</p>
            </div>
        @endif

        @if(in_array($card['kind'] ?? '', ['result', 'done'], true) && isset($card['created']))
            <div class="checklist-card" data-testid="checklist-card-result">
                <p class="checklist-progress">{{ $card['title'] }}</p>
                <p data-testid="result-line">{{ $card['line'] ?? ('Created '.$card['created'].'.') }}</p>
                <p><a href="{{ $card['href'] }}" data-testid="result-link">{{ $card['href_label'] }}</a></p>
            </div>
        @endif

        @if(in_array($card['kind'] ?? '', ['preview', 'result'], true) || $follow)
            @php
                $preview = ($card['kind'] ?? '') === 'preview' ? $card : $follow;
                $question = is_array($preview) ? ($preview['question'] ?? null) : null;
                $waiting = is_array($question) && ! $questionCleared;
                $blocked = is_array($question) && ! empty($question['blocks_write']);
            @endphp
            @if(is_array($preview) && ($preview['kind'] ?? '') === 'preview')
                <div class="checklist-card" data-testid="checklist-card-preview">
                    <p class="checklist-progress" data-testid="checklist-progress">Step {{ $preview['step_number'] }} of {{ $preview['step_total'] }} · {{ $preview['title'] }}</p>
                    <h3>{{ $preview['title'] }}</h3>
                    <p data-testid="preview-counts">Create {{ $preview['create'] }}. Already exists {{ $preview['exists'] }}. Conflict {{ $preview['conflict'] }}.</p>
                    @php
                        $examples = $preview['examples'] ?? [];
                        $shown = $showAll ? $examples : array_slice($examples, 0, 3);
                        $exampleTotal = (int) ($preview['example_total'] ?? count($examples));
                    @endphp
                    @if($shown !== [])
                        <ul>
                            @foreach($shown as $example)
                                <li wire:key="example-{{ $loop->index }}">{{ $example['label'] }} — {{ $example['bucket'] === 'exists' ? 'already exists' : $example['bucket'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if(count($examples) > 3)
                        <button type="button" class="checklist-btn secondary" wire:click="toggleShown" data-testid="checklist-show-all">{{ $showAll ? 'Show less' : 'Show all' }}</button>
                    @endif
                    @if($showAll && $exampleTotal > count($examples))
                        <p class="checklist-note">And {{ $exampleTotal - count($examples) }} more on the sheet.</p>
                    @endif

                    @if($waiting)
                        <p data-testid="checklist-question">{{ $question['prompt'] }}</p>
                        <div class="checklist-actions">
                            @foreach($question['answers'] as $answer)
                                <button type="button" class="checklist-btn" wire:click="answerQuestion('{{ $answer['id'] }}')" data-testid="question-{{ $answer['id'] }}">{{ $answer['label'] }}</button>
                            @endforeach
                            @if(! $showOther)
                                <button type="button" class="checklist-btn secondary" wire:click="answerQuestion('other')" data-testid="question-other">Something else</button>
                            @endif
                        </div>
                        @if($showOther)
                            <textarea class="checklist-text" wire:model="otherText" maxlength="200" data-testid="question-other-text" rows="3"></textarea>
                            <div class="checklist-actions">
                                <button type="button" class="checklist-btn secondary" wire:click="saveNote" data-testid="question-save-note">Save this note</button>
                            </div>
                        @endif
                        @if($otherNote !== '')
                            <p class="checklist-note" data-testid="question-note">{{ $otherNote }} Nothing was written.</p>
                        @endif
                    @else
                        <p data-testid="checklist-proposal">{{ $preview['proposal'] }}</p>
                        @if($otherNote !== '')
                            <p class="checklist-note" data-testid="question-note">{{ $otherNote }} Nothing was written.</p>
                        @endif
                        @if($showChange)
                            <p>Keep the names and dates as they are written on the sheet, or type a note. A note does not change the sheet and does not write anything.</p>
                            <div class="checklist-actions">
                                <button type="button" class="checklist-btn secondary" wire:click="keepSheet" data-testid="keep-sheet">Keep the sheet as written</button>
                                @if(! $showOther)
                                    <button type="button" class="checklist-btn secondary" wire:click="answerQuestion('other')" data-testid="question-other">Something else</button>
                                @endif
                            </div>
                            @if($showOther)
                                <textarea class="checklist-text" wire:model="otherText" maxlength="200" data-testid="question-other-text" rows="3"></textarea>
                                <div class="checklist-actions">
                                    <button type="button" class="checklist-btn secondary" wire:click="saveNote" data-testid="question-save-note">Save this note</button>
                                </div>
                            @endif
                        @endif
                        @if(! $blocked)
                            <div class="checklist-actions">
                                <button type="button" class="checklist-btn" wire:click="confirmStep" data-testid="confirm-step">{{ $preview['approve_label'] }}</button>
                                @if(! $showChange)
                                    <button type="button" class="checklist-btn secondary" wire:click="openChange" data-testid="change-step">Change</button>
                                @endif
                                <button type="button" class="checklist-btn secondary" wire:click="skipStep" data-testid="skip-step">Skip step</button>
                            </div>
                        @endif
                    @endif
                </div>
            @endif
        @endif

        @if(($card['kind'] ?? '') === 'done' || isset($card['completion']))
            @php $done = isset($card['checks']) ? $card : ($card['completion'] ?? null); @endphp
            @if(is_array($done))
                <div class="checklist-card" data-testid="checklist-card-done">
                    <h3>Checklist finished</h3>
                    @include('livewire.partials.checklist-stats', ['stats' => $done['stats'] ?? []])
                    <p data-testid="invites-ready">Invites ready: {{ $done['invites_ready'] }}</p>
                    <p class="checklist-note">No email or WhatsApp message was sent.</p>
                    <p>KLS numbers present: {{ $done['kls_present'] ? 'yes' : 'no' }}@if(($done['kls_missing'] ?? 0) > 0) ({{ $done['kls_missing'] }} missing)@endif.</p>
                    <p>Phones in international format: {{ $done['phones_e164'] ? 'yes' : 'no' }}.</p>
                    @if(($done['checks'] ?? []) !== [])
                        <ul>
                            @foreach($showAll ? $done['checks'] : array_slice($done['checks'], 0, 3) as $check)
                                <li wire:key="check-{{ $loop->index }}">{{ $check['label'] }}: {{ $check['actual'] }} in the school, {{ $check['expected'] }} on the checklist.</li>
                            @endforeach
                        </ul>
                        @if(count($done['checks']) > 3)
                            <button type="button" class="checklist-btn secondary" wire:click="toggleShown" data-testid="checklist-show-all">{{ $showAll ? 'Show less' : 'Show all' }}</button>
                        @endif
                    @endif
                    <p class="checklist-note">Class teachers listed: {{ $done['class_teachers_listed'] }}. Not written.</p>
                </div>
            @endif
        @endif

        @if(($card['kind'] ?? '') === 'failed')
            <div class="checklist-card" data-testid="checklist-card-failed">
                <h3>{{ $card['title'] ?? 'Not applied' }}</h3>
                <p>{{ $card['message'] }}</p>
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'stopped')
            <div class="checklist-card" data-testid="checklist-card-stopped">
                <p>{{ $card['message'] }}</p>
                <button type="button" class="checklist-btn" wire:click="start" data-testid="onboard-from-checklist-again">Onboard from checklist</button>
            </div>
        @endif
        <script>
            if (!window.klassConnectSheet) {
                window.klassConnectSheet = function (button) {
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
                    var start = function () {
                        var tokenClient = google.accounts.oauth2.initTokenClient({
                            client_id: clientId,
                            scope: scope,
                            include_granted_scopes: false,
                            callback: function (response) {
                                if (!response || response.error || !response.access_token) {
                                    component.call('noteSheet', 'picker-failed');
                                    return;
                                }
                                component.call('rememberSheetAccess', response.access_token);
                                gapi.load('picker', { callback: function () {
                                    var picker = new google.picker.PickerBuilder()
                                        .addView(google.picker.ViewId.SPREADSHEETS)
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
                        if (existing) {
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
