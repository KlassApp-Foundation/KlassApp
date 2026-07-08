{{-- Skip button shared between panel and maximized modal views --}}
@if(!$awaitingConfirm && !$actionStep && isset($steps[$step]) && in_array($steps[$step], ['whatsapp_verify', 'teachers', 'students', 'fees', 'exams']))
<div style="padding: 4px 16px 2px; background: #FFFFFF; {{ $modal ?? false ? 'padding:4px 24px 0;text-align:right;' : 'border-top:1px solid #f5f4ed;' }}">
    <button wire:click="skipStep" type="button"
            style="font-size:12px;color:#D97706;background:none;border:none;cursor:pointer;padding:4px 8px;font-weight:600;font-family:'DM Sans',sans-serif;"
            onmouseover="this.style.color='#B45309'" onmouseout="this.style.color='#D97706'">
        ⏭ Skip this step
    </button>
</div>
@endif
