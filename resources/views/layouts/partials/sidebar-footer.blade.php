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
    <p class="sidebar-toshi-soon" data-testid="sidebar-toshi-soon">Toshi · coming soon</p>
    @if(!empty($showYear))
        @php
            $drawerSchoolId = auth()->user()->school_id;
            $drawerYears = \App\Models\AcademicYear::query()
                ->where('school_id', $drawerSchoolId)
                ->orderBy('name')
                ->get(['id', 'name', 'status']);
            $drawerCurrent = \App\Helpers\SiteHelper::getAcademicYear($drawerSchoolId);
            $drawerCurrentId = $drawerCurrent?->id;
        @endphp
        <div class="drawer-year" data-testid="drawer-academic-year">
            <label for="academic_year_drawer" class="dashboard-ay-label">Academic year</label>
            <select id="academic_year_drawer" class="dashboard-ay-select" data-testid="drawer-academic-year-select" aria-label="Academic year">
                @foreach($drawerYears as $year)
                    <option value="{{ $year->id }}" @selected((int) $year->id === (int) $drawerCurrentId)>{{ $year->name }}@if((int) $year->status === 1 && ! str_contains((string) $year->name, 'current')) (current)@endif</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="sidebar-account-row">
        @if($notifyMode)
            <div class="dashboard-sidebar-footer-bell">
                <notification url="{{ url('/') }}" mode="{{ $notifyMode }}"></notification>
            </div>
        @endif
        @include('layouts.partials.dashboard-v2-chip')
        @include('layouts.partials.profile-dropdown')
    </div>
</div>
@endif
