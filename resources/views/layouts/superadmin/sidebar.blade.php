{{-- SPDX-License-Identifier: MIT --}}
<div id="superadmin-sidebar" class="w-full h-full lg:w-48 md:w-48 dashboard-themed-sidebar admin-sidebar" data-collapsed="false">
  <div class="min-h-full header-wrapper-b hidden lg:block md:block">
   @include('layouts.superadmin.menu')
  </div>
</div>
<div id="res_sidebar" class="w-full lg:w-48 md:w-48 admin-sidebar dashboard-themed-sidebar hidden lg:hidden md:hidden res_sidebar">
  <div class="min-h-full header-wrapper-b lg:hidden md:hidden" style="background-color: #FFFFFF;">
   @include('layouts.superadmin.menu')
  </div>
</div>