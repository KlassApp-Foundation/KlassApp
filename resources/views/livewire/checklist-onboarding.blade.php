<div class="checklist-onboarding" data-testid="checklist-onboarding" @if(!$enabled) hidden @endif>
    @if($enabled)
        <style>
            .checklist-onboarding { width: 100%; max-width: 100%; box-sizing: border-box; padding: 8px 10px 4px; color: #141413; }
            .checklist-card { background: #ffffff; color: #141413; border: 1px solid #d6d3d1; border-radius: 12px; padding: 12px; margin: 0 0 10px; box-sizing: border-box; overflow-wrap: anywhere; }
            .checklist-card h3 { margin: 0 0 8px; font-size: 16px; line-height: 1.3; color: #141413; }
            .checklist-card p, .checklist-card li { font-size: 14px; line-height: 1.4; color: #141413; }
            .checklist-card table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .checklist-card th, .checklist-card td { text-align: left; padding: 4px 2px; border-bottom: 1px solid #e7e5e4; vertical-align: top; }
            .checklist-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
            .checklist-btn { min-height: 44px; width: 100%; border-radius: 10px; border: 1px solid #141413; background: #141413; color: #ffffff; font-size: 15px; font-weight: 600; padding: 10px 12px; cursor: pointer; }
            .checklist-btn.secondary { background: #ffffff; color: #141413; }
            .checklist-file { min-height: 44px; width: 100%; font-size: 14px; }
            .checklist-note { font-size: 13px; color: #44403c; margin: 6px 0 0; }
        </style>

        @if($phase === 'idle')
            <button type="button" class="checklist-btn" wire:click="start" data-testid="onboard-from-checklist">Onboard from checklist</button>
            @if(($card['kind'] ?? '') === 'hint')
                <p class="checklist-note" data-testid="checklist-hint">{{ $card['message'] }}</p>
            @endif
        @endif

        @if(($card['kind'] ?? '') === 'upload')
            <div class="checklist-card" data-testid="checklist-card-upload">
                <h3>Upload the checklist</h3>
                <p>Choose the school's checklist workbook (.xlsx). I will read it here before anything is saved.</p>
                <input class="checklist-file" type="file" wire:model="workbook" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" data-testid="checklist-upload">
                <div class="checklist-actions">
                    <button type="button" class="checklist-btn" wire:click="ingest" data-testid="checklist-read">Read this checklist</button>
                </div>
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
                <table>
                    <thead>
                        <tr><th>Step</th><th>Read</th><th>Accepted</th><th>Flagged</th></tr>
                    </thead>
                    <tbody>
                        @foreach($card['steps'] as $step)
                            <tr>
                                <td>{{ $step['label'] }}</td>
                                <td data-testid="count-read-{{ $step['key'] }}">{{ $step['read'] }}</td>
                                <td data-testid="count-accepted-{{ $step['key'] }}">{{ $step['accepted'] }}</td>
                                <td data-testid="count-flagged-{{ $step['key'] }}">{{ $step['flagged'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="checklist-note">Class teachers listed: {{ $card['class_teachers_listed'] }}. This demo does not write class teachers.</p>
                @if($card['flags'] !== [])
                    <p><strong>Flagged rows</strong></p>
                    <ul>
                        @foreach($card['flags'] as $flag)
                            <li data-testid="checklist-flag">{{ $flag['tab'] }}, row {{ $flag['row'] ?? '—' }}: {{ $flag['field'] }}. {{ $flag['message'] }}</li>
                        @endforeach
                    </ul>
                    <div class="checklist-actions">
                        <button type="button" class="checklist-btn" wire:click="skipFlags" data-testid="skip-these-rows">Skip these rows</button>
                        <button type="button" class="checklist-btn secondary" wire:click="stopForFix" data-testid="stop-fix-workbook">Stop, I'll fix the workbook</button>
                    </div>
                @endif
            </div>
        @endif

        @if(in_array($card['kind'] ?? '', ['preview', 'result'], true) || $follow)
            @php $preview = ($card['kind'] ?? '') === 'preview' ? $card : $follow; @endphp
            @if(is_array($preview) && ($preview['kind'] ?? '') === 'preview')
                <div class="checklist-card" data-testid="checklist-card-preview">
                    <h3>{{ $preview['title'] }}</h3>
                    <p data-testid="preview-counts">Create {{ $preview['create'] }}. Already exists {{ $preview['exists'] }}. Conflict {{ $preview['conflict'] }}.</p>
                    @if($preview['examples'] !== [])
                        <ul>
                            @foreach($preview['examples'] as $example)
                                <li>{{ $example['label'] }} — {{ $example['bucket'] === 'exists' ? 'already exists' : $example['bucket'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if($preview['conflicts'] !== [])
                        <ul>
                            @foreach($preview['conflicts'] as $conflict)
                                <li>{{ $conflict['label'] }}: {{ $conflict['message'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="checklist-actions">
                        <button type="button" class="checklist-btn" wire:click="confirmStep" data-testid="confirm-step">Confirm step</button>
                        <button type="button" class="checklist-btn secondary" wire:click="skipStep" data-testid="skip-step">Skip step</button>
                    </div>
                </div>
            @endif
        @endif

        @if(in_array($card['kind'] ?? '', ['result', 'done'], true) && isset($card['created']))
            <div class="checklist-card" data-testid="checklist-card-result">
                <h3>{{ $card['title'] }} saved</h3>
                <p data-testid="result-counts">Created {{ $card['created'] }}. Already existed {{ $card['existed'] }}.</p>
                <p><a href="{{ $card['href'] }}" data-testid="result-link">{{ $card['href_label'] }}</a></p>
            </div>
        @endif

        @if(($card['kind'] ?? '') === 'done' || isset($card['completion']))
            @php $done = isset($card['checks']) ? $card : ($card['completion'] ?? null); @endphp
            @if(is_array($done))
                <div class="checklist-card" data-testid="checklist-card-done">
                    <h3>Checklist finished</h3>
                    <p data-testid="invites-ready">Invites ready: {{ $done['invites_ready'] }}</p>
                    <p class="checklist-note">No email or WhatsApp message was sent.</p>
                    <p>KLS numbers present: {{ $done['kls_present'] ? 'yes' : 'no' }}@if(($done['kls_missing'] ?? 0) > 0) ({{ $done['kls_missing'] }} missing)@endif.</p>
                    <p>Phones in international format: {{ $done['phones_e164'] ? 'yes' : 'no' }}.</p>
                    @if($done['checks'] !== [])
                        <ul>
                            @foreach($done['checks'] as $check)
                                <li>{{ $check['label'] }}: {{ $check['actual'] }} in the school, {{ $check['expected'] }} on the checklist.</li>
                            @endforeach
                        </ul>
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
    @endif
</div>
