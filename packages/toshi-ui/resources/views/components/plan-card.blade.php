@props([
    'steps' => [],
    'wireKey' => 'plan',
    'confirmMethod' => 'confirmPlan',
    'cancelMethod' => 'cancelPlan',
    'executingVerb' => 'Executing...',
])
<div class="toshi-plan-card" wire:key="{{ $wireKey }}">
    <div class="toshi-plan-card-header">
        <span class="toshi-plan-card-icon">📋</span>
        <span class="toshi-plan-card-title">Execution Plan</span>
        <span class="toshi-plan-card-count">{{ count($steps) }} step{{ count($steps) !== 1 ? 's' : '' }}</span>
    </div>
    <div class="toshi-plan-card-steps">
        @foreach($steps as $i => $step)
            @php
                $status = $step['status'] ?? 'pending';
                $statusIcon = match ($status) {
                    'completed'   => '✅',
                    'failed'      => '❌',
                    'in_progress' => '⏳',
                    default       => '○',
                };
                $statusClass = match ($status) {
                    'completed'   => 'step-completed',
                    'failed'      => 'step-failed',
                    'in_progress' => 'step-active',
                    default       => 'step-pending',
                };
            @endphp
            <div class="toshi-plan-step {{ $statusClass }}" wire:key="{{ $wireKey }}-step-{{ $i }}">
                <span class="toshi-plan-step-icon">{{ $statusIcon }}</span>
                <span class="toshi-plan-step-num">{{ $i + 1 }}.</span>
                <span class="toshi-plan-step-label">{{ $step['label'] ?? 'Execute action' }}</span>
            </div>
        @endforeach
    </div>
    @php
        $allDone = collect($steps)->every(fn($s) => in_array($s['status'] ?? '', ['completed', 'failed']));
        $hasPending = collect($steps)->contains(fn($s) => ($s['status'] ?? 'pending') === 'pending');
        $isProcessing = collect($steps)->contains(fn($s) => ($s['status'] ?? '') === 'in_progress');
    @endphp
    <div class="toshi-plan-card-footer">
        @if($allDone)
            @php
                $succeeded = collect($steps)->where('status', 'completed')->count();
                $failed = collect($steps)->where('status', 'failed')->count();
            @endphp
            <div class="toshi-plan-card-done">
                ✅ Plan complete: {{ $succeeded }} succeeded@if($failed), {{ $failed }} failed @endif
            </div>
        @elseif($isProcessing)
            <span class="toshi-plan-card-processing">⏳ {{ $executingVerb }}</span>
            <button wire:click="{{ $cancelMethod }}" type="button" class="toshi-plan-btn toshi-plan-btn-cancel" style="flex: 0 0 auto;">
                Cancel
            </button>
        @elseif($hasPending)
            <button wire:click="{{ $confirmMethod }}" type="button" class="toshi-plan-btn toshi-plan-btn-execute">
                Execute All ({{ collect($steps)->where('status', 'pending')->count() }})
            </button>
            <button wire:click="{{ $cancelMethod }}" type="button" class="toshi-plan-btn toshi-plan-btn-cancel">
                Cancel
            </button>
        @else
            <div class="toshi-plan-card-done">Processing...</div>
        @endif
    </div>
</div>
