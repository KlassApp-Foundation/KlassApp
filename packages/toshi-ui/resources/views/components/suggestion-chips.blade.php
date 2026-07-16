@props([
    'suggestions' => [],
    'inputId' => 'toshi-input-panel',
    'wireKey' => 'sc',
])
<div class="toshi-suggestions-wrapper shrink-0"
     x-data="{ used: new Set() }">
    <div class="toshi-suggestions-scroll">
        @foreach($suggestions as $index => $chip)
        <button type="button"
                class="toshi-chip-suggestion"
                wire:key="{{ $wireKey }}-{{ $index }}"
                :class="{ 'toshi-chip-used': used.has('{{ $index }}') }"
                x-on:click="
                    used.add('{{ $index }}');
                    const input = document.getElementById('{{ $inputId }}');
                    if(input) {
                        input.value = '{{ $chip['label'] }}';
                        input.dispatchEvent(new Event('input'));
                        input.focus();
                    }
                ">
            <span class="toshi-chip-icon">{{ $chip['icon'] }}</span>
            <span>{{ $chip['label'] }}</span>
        </button>
        @endforeach
    </div>
</div>
