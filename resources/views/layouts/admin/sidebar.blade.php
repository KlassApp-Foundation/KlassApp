{{-- SPDX-License-Identifier: MIT --}}
{{-- Desktop sidebar — visible md+ --}}
<div id="admin-sidebar" class="hidden md:block lg:w-48 md:w-48 text-white dashboard-themed-sidebar admin-sidebar" data-collapsed="false" style="background-color: #FFFCF5;">
  <div class="min-h-full header-wrapper-b">
   @include('layouts.admin.menu')
  </div>
</div>
{{-- Mobile sidebar — toggleable via hamburger --}}
<div id="res_sidebar" class="block md:hidden admin-sidebar dashboard-themed-sidebar hidden" style="background-color: #141413; position: absolute; z-index: 50; width: 100%;">
  <div class="min-h-full header-wrapper-b">
   @include('layouts.admin.menu')
  </div>
</div>