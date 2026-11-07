{{-- Shared marks lifecycle strip (Lifecycle.dc.html). $current is saved, submitted, approved, or released. --}}
@php
    $order = ['saved' => 'Saved', 'submitted' => 'Submitted', 'approved' => 'Approved', 'released' => 'Released'];
    $copy = [
        'saved' => 'A draft. Only the teacher sees it.',
        'submitted' => 'Sent for approval. Locked for editing.',
        'approved' => 'Checked. Parents still see nothing.',
        'released' => 'Parents and students can see it.',
    ];
    $currentKey = $current ?? 'saved';
    $seen = false;
@endphp
<ol class="ka-life" aria-label="Where these marks are" data-testid="marks-lifecycle">
    @foreach($order as $key => $name)
        @php
            $now = $key === $currentKey;
            $done = ! $seen && ! $now;
            if ($now) { $seen = true; }
        @endphp
        <li class="{{ $now ? 'is-now' : ($done ? 'is-done' : '') }}" @if($now) aria-current="step" @endif>
            <span style="flex-shrink:0;width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font:700 .75rem Sora,sans-serif;{{ $done ? 'background:#15803D;color:#fff' : ($now ? 'background:#1E293B;color:#fff' : 'background:#fff;color:#475569;border:1px solid #CBD5E1') }}">{{ $done ? '✓' : $loop->iteration }}</span>
            <span><strong style="font-size:.85rem">{{ $name }}</strong><br><span style="font-size:.75rem;color:#475569">{{ $copy[$key] }}</span></span>
        </li>
    @endforeach
</ol>
