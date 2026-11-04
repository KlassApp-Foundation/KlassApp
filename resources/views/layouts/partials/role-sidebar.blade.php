{{-- SPDX-License-Identifier: MIT --}}
{{--
    The sidebar shell every non-admin role shares. It is the school admin's shell
    (layouts/admin/sidebar.blade.php): menu on top, the notification bell and the account menu
    in the sidebar footer on desktop, and the same slide-in drawer on phones (the top bar then
    carries the avatar, see `chromeInSidebar` in layouts/partials/navigation.blade.php).

    Params:
      role       key in config('navigation.roles') (sidebar-menu)
      roleClass  the role's legacy CSS hook (teacher-sidebar, student-sidebar, ...)
      notifyMode notification component mode (same contract as the header nav)
--}}
@php
    $notifyMode = $notifyMode ?? $role;
    $roleClass = $roleClass ?? ($role.'-sidebar');
@endphp
{{-- Desktop sidebar, visible md+. Flex column so the footer stays anchored at the bottom. --}}
<div id="admin-sidebar" class="hidden md:flex md:flex-col h-full lg:w-48 md:w-48 text-slate-700 dashboard-themed-sidebar admin-sidebar {{ $roleClass }}" data-collapsed="false" style="background-color: #FFFCF5;">
  <div class="flex-1 header-wrapper-b">
    @include('layouts.partials.sidebar-menu', ['role' => $role])
  </div>
  @include('layouts.partials.sidebar-footer', ['notifyMode' => $notifyMode])
</div>
{{-- Mobile drawer, toggled by the hamburger. Same footer at the bottom of the menu. --}}
<div id="res_sidebar" class="block md:hidden admin-sidebar dashboard-themed-sidebar {{ $roleClass }} hidden" role="dialog" aria-modal="true" aria-label="Main menu" style="background-color: #FFFCF5;">
  <div class="ka-drawer-head">
    <img class="ka-drawer-logo" src="{{ asset('images/klassapp-horizontal-dark.svg') }}" alt="" height="24">
    <button type="button" id="drawer-close" class="ka-drawer-close" aria-label="Close menu"><span aria-hidden="true">✕</span></button>
  </div>
  <div class="header-wrapper-b drawer-nav">
    @include('layouts.partials.sidebar-menu', ['role' => $role])
  </div>
  @include('layouts.partials.sidebar-footer', ['notifyMode' => $notifyMode])
</div>
