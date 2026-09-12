{{-- Brand header: desktop top-aligned stack; mobile logo|copy row (OD brand-header-balance-v1). --}}
<aside class="ap-brand-panel">
  <span class="ap-preview-badge">Preview</span>
  <div class="ap-brand-row">
    <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="ap-brand-logo" alt="KlassApp">
    <div class="ap-brand-copy">
      <p class="ap-brand-tagline">{{ $tagline ?? 'School operations on one protocol' }}</p>
      <p class="ap-brand-support">{{ $support ?? 'Classes, fees, and parent updates from one connected system.' }}</p>
    </div>
  </div>
</aside>
