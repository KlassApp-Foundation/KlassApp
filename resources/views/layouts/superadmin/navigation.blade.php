{{-- SPDX-License-Identifier: MIT --}}
{{--
    SiteAdmin (platform) header.
    Chrome + mechanics come from the shared partial; `plain` gives the white,
    platform-level treatment (D2) that distinguishes this shell from the cream
    school dashboards. The platform MENU stays in layouts/superadmin/menu.blade.php.
      brandLogo=klassapp -> a SiteAdmin has no single school identity (and ug1 may
                            carry a school_id for impersonation purposes).
      showToggle=true    -> shared #sidebar-collapse-toggle + delegated listener.
      notifyMode=superadmin -> platform bell (superadmin/notification/* routes).
--}}
@include('layouts.partials.navigation', [
    'variant'          => 'plain',
    'brandLogo'        => 'klassapp',
    'brandRoute'       => 'superadmin.dashboard',
    'showToggle'       => true,
    'showAcademicYear' => false,
    'notifyMode'       => 'superadmin',
])
