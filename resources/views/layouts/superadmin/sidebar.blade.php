{{-- SPDX-License-Identifier: MIT --}}
<div id="admin-sidebar" class="hidden md:flex md:flex-col h-full lg:w-48 md:w-48 dashboard-themed-sidebar admin-sidebar" data-collapsed="false" style="background-color: #FFFCF5;">
  <div class="min-h-full header-wrapper-b hidden lg:block md:block" style="color: var(--d-text);">
   @include('layouts.partials.sidebar-menu', ['role' => 'superadmin'])
  </div>
</div>
<div id="res_sidebar" class="w-full lg:w-48 md:w-48 admin-sidebar dashboard-themed-sidebar hidden lg:hidden md:hidden res_sidebar">
  <div class="min-h-full header-wrapper-b lg:hidden md:hidden" style="background-color: #FFFFFF;">
   @include('layouts.partials.sidebar-menu', ['role' => 'superadmin'])
  </div>
</div>