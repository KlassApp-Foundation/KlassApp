@if($history !== [] && ! in_array(($card['kind'] ?? $phase), ['agreement', 'welcome', 'hint'], true))
    <button type="button" class="t-btn secondary" wire:click="back" data-testid="checklist-back">Back</button>
@endif
