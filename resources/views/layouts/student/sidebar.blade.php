{{-- SPDX-License-Identifier: MIT --}}
<div id="admin-sidebar" class="hidden md:flex md:flex-col h-full lg:w-48 md:w-48 dashboard-themed-sidebar student-sidebar" data-collapsed="false">
  <div class="flex-1 header-wrapper-b">
    @include('layouts.partials.sidebar-menu', ['role' => 'student'])
  </div>
  @include('layouts.partials.sidebar-footer', ['notifyMode' => 'student'])
</div>
<div id="res_sidebar" role="dialog" aria-modal="true" aria-label="Main menu" class="block md:hidden student-sidebar dashboard-themed-sidebar hidden res_sidebar">
  <div class="header-wrapper-b">
    @include('layouts.partials.sidebar-menu', ['role' => 'student'])
  </div>
  @include('layouts.partials.sidebar-footer', ['notifyMode' => 'student'])
</div>
