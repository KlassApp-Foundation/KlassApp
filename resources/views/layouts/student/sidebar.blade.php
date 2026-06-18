{{-- SPDX-License-Identifier: MIT --}}
<div id="admin-sidebar" class="w-full h-full lg:w-48 md:w-48 dashboard-themed-sidebar student-sidebar" data-collapsed="false">
  <div class="min-h-full header-wrapper-b hidden lg:block md:block">
   @include('layouts.student.menu')
  </div>
</div>
<div id="res_sidebar" class="w-full lg:w-48 md:w-48 student-sidebar dashboard-themed-sidebar hidden lg:hidden md:hidden res_sidebar">
  <div class="min-h-full header-wrapper-b lg:hidden md:hidden">
   @include('layouts.student.menu')
  </div>
</div>