{{-- SPDX-License-Identifier: MIT --}}
<div class="w-full h-full lg:w-48 md:w-48 text-white dashboard-themed-sidebar admin-sidebar" style="background-color: #0F172A;">
  <div class="min-h-full header-wrapper-b hidden lg:block md:block ">
   @include('layouts.superadmin.menu')
  </div>
</div>
<div id="res_sidebar" class="w-full lg:w-48 md:w-48 admin-sidebar dashboard-themed-sidebar hidden lg:hidden md:hidden res_sidebar ">
  <div class="min-h-full header-wrapper-b lg:hidden md:hidden" style="background-color: #0F172A;">
   @include('layouts.superadmin.menu')
  </div>
</div>
