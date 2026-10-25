@if(count($pairs ?? []) > 0)
    @php
        $streamList = collect($pairs)->map(fn ($pair) => trim((string) ($pair['class'] ?? '')).', stream '.trim((string) ($pair['stream'] ?? '')))->implode('; ');
    @endphp
    <div class="manual-wizard-bulk-help" data-testid="{{ $testId }}">
        <p>These streams are not set up yet:</p>
        <ul class="manual-wizard-bulk-list">
            @foreach($pairs as $pair)
                <li class="manual-wizard-bulk-item" data-testid="wizard-missing-stream">{{ $pair['class'] }}, stream {{ $pair['stream'] }}</li>
            @endforeach
        </ul>
        <x-button type="button"
                  variant="outline"
                  size="sm"
                  wire:click="createMissingStreamsAndRetry('{{ $upload }}')"
                  wire:confirm="Create these streams and retry the upload? {{ $streamList }}"
                  data-testid="wizard-create-missing-streams-{{ $upload }}">Create missing streams and retry</x-button>
    </div>
@endif
