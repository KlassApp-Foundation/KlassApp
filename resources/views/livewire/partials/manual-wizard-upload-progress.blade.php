@php
    $uploadStatus = $activeUpload['status'] ?? null;
    $uploadTotal = (int) ($activeUpload['total'] ?? 0);
    $uploadProcessed = (int) ($activeUpload['processed'] ?? 0);
    $uploadAdded = (int) ($activeUpload['added'] ?? 0);
    $uploadSkipped = (int) ($activeUpload['skipped'] ?? 0);
@endphp

@if(in_array($uploadStatus, ['queued', 'running'], true))
    <div class="manual-wizard-bulk-help" wire:poll.1500ms="refreshUpload" data-testid="wizard-upload-progress">
        @if($uploadTotal < 1)
            Reading the file…
        @else
            Importing in the background: {{ $uploadProcessed }} of {{ $uploadTotal }} rows processed
            ({{ $uploadAdded }} added, {{ $uploadSkipped }} skipped). You can keep working.
        @endif
    </div>
@endif
