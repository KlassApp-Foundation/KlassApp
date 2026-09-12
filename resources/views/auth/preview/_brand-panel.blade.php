{{-- Desktop branding column; stacks compactly on mobile (OD v2 breakpoints). --}}
<aside class="ap-brand-panel">
  <span class="ap-preview-badge">Preview</span>
  <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="ap-brand-logo" alt="KlassApp">
  <p class="ap-brand-tagline">{{ $tagline ?? 'School operations on one protocol' }}</p>
  <p class="ap-brand-support">{{ $support ?? 'Classes, fees, and parent updates from one connected system.' }}</p>
</aside>
