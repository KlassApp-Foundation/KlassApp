{{-- SPDX-License-Identifier: MIT --}}
{{--
    Sidebar footer — profile dropdown + notification bell, relocated from the
    dashboard header (Toshi panel thread, "Profile and notifications to the
    sidebar footer" — was queued NOT STARTED since 2026-09-22; shipped in the
    2026-09-27 UI-polish pass).

    Mechanics that make relocation safe (verified before shipping):
    - Profile dropdown toggle is DELEGATED (custom.js binds on document,
      '.profile-click'), and .user-dtl is position:absolute under the
      .profile-click anchor — it works wherever the anchor lives. Here we
      anchor it in the sidebar footer; the panel opens above the anchor.
    - The notification list is position:fixed top:0 right:0 (a full-height
      drawer) — independent of where the bell sits.
    - Collapsed rail (body.sidebar-collapsed): the footer shrinks to icon
      size; labels hide exactly like the menu items above.

    Parameters: $notifyMode (string|null) — same contract as the header nav.
--}}
@php
    $notifyMode = $notifyMode ?? 'admin';
@endphp
@guest
    {{-- No chrome for guest contexts: the footer partial is rendered inside role
         layouts that tests can render via view() without an acting user, and the
         profile dropdown requires an authenticated user (PortalProfileLinks). --}}
@else
<div class="dashboard-sidebar-footer" data-testid="dashboard-sidebar-footer">
    <p class="sidebar-toshi-soon" data-testid="sidebar-toshi-soon">Toshi, your school’s AI assistant, is coming soon.</p>
    @if(!empty($showYear))
        <div class="drawer-year" data-testid="drawer-academic-year">
            <nav-bar field-id="academic_year_drawer"></nav-bar>
        </div>
    @endif
    @if($notifyMode)
        <div class="dashboard-sidebar-footer-bell">
            <notification url="{{ url('/') }}" mode="{{ $notifyMode }}"></notification>
        </div>
    @endif
    @include('layouts.partials.dashboard-v2-chip')
    @include('layouts.partials.profile-dropdown')
</div>
@endif
