{{-- Mode dropdown shared between panel and maximized modal views --}}
<div x-data="{ open: false }" @click.away="open = false" style="position:relative;">
    <button @click="open = !open" style="display:flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;border:1px solid #e8e6dc;background:#FFFFFF;font-size:11px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;color:#5e5d59;transition:all 0.15s;" onmouseover="this.style.borderColor='#22C55E'" onmouseout="this.style.borderColor='#e8e6dc'">
        @if($mode === 'assistant')<span style="color:#22C55E;">●</span> Toshi Agent
        @elseif($mode === 'create')<span style="color:#D97706;">●</span> Creating School
        @else<span style="color:#1E6FD9;">●</span> Completing Setup ✅
        @endif
        <svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3l3 4 3-4"/></svg>
    </button>
    <div x-show="open" style="position:absolute;top:100%;left:0;margin-top:4px;background:#FFFFFF;border:1px solid #e8e6dc;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);padding:6px;z-index:100;min-width:200px;font-family:'DM Sans',sans-serif;" x-transition @click="open = false">
        @if($scope === 'platform')
        <button wire:click="switchMode('assistant')" type="button" style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 10px;border:none;border-radius:6px;background:{{ $mode === 'assistant' ? '#F0FDF4' : 'transparent' }};cursor:pointer;font-size:13px;text-align:left;color:#141413;font-weight:{{ $mode === 'assistant' ? '600' : '400' }};" onmouseover="this.style.background='#F5F5F5'" onmouseout="this.style.background='{{ $mode === 'assistant' ? '#F0FDF4' : 'transparent' }}'">
            <span style="color:#22C55E;font-size:16px;">💬</span> <span>Assistant</span>
            @if($mode === 'assistant')<span style="margin-left:auto;color:#22C55E;">✓</span>@endif
        </button>
        <button wire:click="switchMode('create')" type="button" style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 10px;border:none;border-radius:6px;background:{{ $mode === 'create' ? '#FFFBEB' : 'transparent' }};cursor:pointer;font-size:13px;text-align:left;color:#141413;font-weight:{{ $mode === 'create' ? '600' : '400' }};" onmouseover="this.style.background='#F5F5F5'" onmouseout="this.style.background='{{ $mode === 'create' ? '#FFFBEB' : 'transparent' }}'">
            <span style="color:#D97706;font-size:16px;">🏗️</span> <span>Create School</span>
            @if($mode === 'create')<span style="margin-left:auto;color:#D97706;">✓</span>@endif
        </button>
        @else
        <button wire:click="switchMode('assistant')" type="button" style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 10px;border:none;border-radius:6px;background:{{ $mode === 'assistant' ? '#F0FDF4' : 'transparent' }};cursor:pointer;font-size:13px;text-align:left;color:#141413;font-weight:{{ $mode === 'assistant' ? '600' : '400' }};" onmouseover="this.style.background='#F5F5F5'" onmouseout="this.style.background='{{ $mode === 'assistant' ? '#F0FDF4' : 'transparent' }}'">
            <span style="color:#22C55E;font-size:16px;">💬</span> <span>Toshi Agent</span>
            @if($mode === 'assistant')<span style="margin-left:auto;color:#22C55E;">✓</span>@endif
        </button>
        @endif
        <div style="border-top:1px solid #f0f0f0;margin:4px 0;"></div>
        <div style="padding:6px 10px;font-size:11px;color:#87867f;line-height:1.4;">
            <div><code style="background:#f5f4ed;padding:1px 4px;border-radius:3px;font-size:10px;">/agent</code> — Q&A mode</div>
            @if($scope === 'platform')<div style="margin-top:3px;"><code style="background:#f5f4ed;padding:1px 4px;border-radius:3px;font-size:10px;">/create</code> — New school</div>@endif
            <div style="margin-top:3px;"><code style="background:#f5f4ed;padding:1px 4px;border-radius:3px;font-size:10px;">/help</code> — All commands</div>
        </div>
    </div>
</div>
