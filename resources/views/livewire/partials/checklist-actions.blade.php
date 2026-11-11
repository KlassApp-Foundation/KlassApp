{{-- SPDX-License-Identifier: MIT --}}
@php
    $kind = (string) ($card['kind'] ?? '');
    $question = is_array($card['question'] ?? null) ? $card['question'] : null;
    $waiting = $kind === 'preview' && is_array($question) && ! $questionCleared;
    $blocked = is_array($question) && ! empty($question['blocks_write']);
    $readReady = $workbook !== null || trim($sheetUrl) !== '' || $sheetFileId !== '';
    $checkKey = (string) ($card['step_key'] ?? '');
    $needsCheck = ($card['connect_notice'] ?? '') === '' && (
        $kind === 'summary'
        || ($kind === 'preview' && in_array($checkKey, ['terms', 'standards', 'teachers', 'teacher_invites', 'whatsapp'], true))
    );
    $checkLabel = $checkKey === 'whatsapp' ? "I've checked this" : "I've checked these";
    $held = $needsCheck && ! $reviewed;
    $emailsBlocked = ! empty($card['emails_blocked']);
@endphp
<div class="t-actionbar" data-testid="checklist-actions">
    <p class="t-working" data-testid="checklist-working" wire:loading.class="is-on" wire:target="ingest,confirmStep,confirmReviewed,importPickedSheet,skipFlags,finishSetup">
        <span class="t-ring" aria-hidden="true"></span>
        <span>Toshi is working…</span>
    </p>
    @if(is_array($editor))
        <div class="t-actions">
            <button type="button" class="t-btn secondary" wire:click="closeEditor" data-testid="checklist-editor-close">Close</button>
        </div>
    @elseif($phase === 'idle' && ! ($setupFinished ?? false))
        <div class="t-actions">
            <button type="button" class="t-btn" wire:click="start" data-testid="onboard-from-checklist">Set up from checklist</button>
        </div>
    @elseif($kind === 'agreement' || ($phase === 'agreement' && $kind !== 'refused'))
        <div class="t-actions">
            <button type="button" class="t-btn" wire:click="confirmAgreement" data-testid="confirm-agreement" @disabled(! $agreed) @if(! $agreed) aria-describedby="checklist-agree-hint" @endif>Continue</button>
        </div>
    @elseif($kind === 'upload')
        <div class="t-actions">
            <button type="button" class="t-btn" wire:click="ingest" data-testid="checklist-read" @disabled(! $readReady) @if(! $readReady) aria-describedby="checklist-read-hint" @endif>Read this checklist</button>
            @include('livewire.partials.checklist-back')
        </div>
    @elseif($kind === 'summary' && ($card['flags'] ?? []) !== [])
        <div class="t-actions">
            @if($details !== [])
                <button type="button" class="t-btn secondary" wire:click="openDetail" data-testid="checklist-detail">Fill this in</button>
            @endif
            <div class="t-confirm-row">
                @if($needsCheck)
                    <label class="t-check" data-testid="checklist-reviewed">
                        <input type="checkbox" wire:model.live="reviewed" data-testid="checklist-reviewed-input">
                        <span>{{ $checkLabel }}</span>
                    </label>
                @endif
                <button type="button" class="t-btn" wire:click="skipFlags" data-testid="skip-these-rows" data-checklist-focus @disabled($held)>Continue</button>
            </div>
            <button type="button" class="t-btn secondary" wire:click="stopForFix" data-testid="stop-fix-workbook">Stop, I'll fix the workbook</button>
            @include('livewire.partials.checklist-back')
        </div>
    @elseif($kind === 'summary' && is_array($follow) && ($follow['kind'] ?? '') === 'preview')
        <div class="t-actions">
            @if($details !== [])
                <button type="button" class="t-btn secondary" wire:click="openDetail" data-testid="checklist-detail">Fill this in</button>
            @endif
            <div class="t-confirm-row">
                @if($needsCheck)
                    <label class="t-check" data-testid="checklist-reviewed">
                        <input type="checkbox" wire:model.live="reviewed" data-testid="checklist-reviewed-input">
                        <span>{{ $checkLabel }}</span>
                    </label>
                @endif
                <button type="button" class="t-btn" wire:click="openNext" data-testid="checklist-next" data-checklist-focus @disabled($held)>Next: {{ $follow['name'] ?? 'Terms' }}</button>
            </div>
            @include('livewire.partials.checklist-back')
        </div>
    @elseif($kind === 'summary')
        <div class="t-actions">
            @include('livewire.partials.checklist-back')
        </div>
    @elseif($kind === 'result' && isset($card['created']))
        <div class="t-actions">
            @if(is_array($follow) && ($follow['kind'] ?? '') === 'preview')
                <button type="button" class="t-btn" wire:click="openNext" data-testid="checklist-next" data-checklist-focus>Next: {{ $follow['name'] ?? '' }}</button>
            @endif
            <button type="button" class="t-btn secondary" wire:click="openEditor" data-testid="checklist-edit">Edit</button>
            @include('livewire.partials.checklist-back')
        </div>
    @elseif($kind === 'preview' && $waiting)
        <div class="t-actions">
            @foreach($question['answers'] as $answer)
                <button type="button" class="t-btn" wire:click="answerQuestion('{{ $answer['id'] }}')" data-testid="question-{{ $answer['id'] }}" @if($loop->first) data-checklist-focus @endif>{{ $answer['label'] }}</button>
            @endforeach
            @if(! $showOther)
                <button type="button" class="t-btn secondary" wire:click="answerQuestion('other')" data-testid="question-other">Something else</button>
            @endif
        </div>
    @elseif($kind === 'preview' && ! $blocked)
        <div class="t-actions">
            @if($showChange)
                <button type="button" class="t-btn secondary" wire:click="keepSheet" data-testid="keep-sheet">Keep the sheet as written</button>
                @if(! $showOther)
                    <button type="button" class="t-btn secondary" wire:click="answerQuestion('other')" data-testid="question-other">Something else</button>
                @endif
            @else
                <div class="t-confirm-row">
                    @if($needsCheck)
                        <label class="t-check" data-testid="checklist-reviewed">
                            <input type="checkbox" wire:model.live="reviewed" data-testid="checklist-reviewed-input">
                            <span>{{ $checkLabel }}</span>
                        </label>
                    @endif
                    <button type="button" class="t-btn" wire:click="confirmReviewed" data-testid="confirm-step" data-checklist-focus @disabled($held || $emailsBlocked)>{{ $card['approve_label'] ?? 'Create' }}</button>
                </div>
                @if(! in_array($checkKey, ['teacher_invites', 'whatsapp'], true))
                    <button type="button" class="t-btn secondary" wire:click="openEditor" data-testid="checklist-edit">Edit</button>
                @endif
                @if(! in_array($checkKey, ['teacher_invites', 'whatsapp'], true))
                    <div class="t-actions row">
                        <button type="button" class="t-btn secondary" wire:click="openChange" data-testid="change-step">Change</button>
                        <button type="button" class="t-btn secondary" wire:click="skipStep" data-testid="skip-step">Skip</button>
                    </div>
                @endif
            @endif
            @include('livewire.partials.checklist-back')
        </div>
    @elseif($kind === 'done' || isset($card['completion']))
        <div class="t-actions">
            <button type="button" class="t-btn" wire:click="finishSetup" data-testid="checklist-finish" data-checklist-focus>Finish setup</button>
            @include('livewire.partials.checklist-back')
        </div>
    @elseif($kind === 'finished')
        <div class="t-actions">
            <button type="button" class="t-btn" wire:click="continueToDashboard" data-testid="checklist-continue" data-checklist-focus>Continue to dashboard</button>
        </div>
    @elseif($kind === 'stopped')
        <div class="t-actions">
            <button type="button" class="t-btn" wire:click="start" data-testid="onboard-from-checklist-again" data-checklist-focus>Set up from checklist</button>
        </div>
    @endif
</div>
