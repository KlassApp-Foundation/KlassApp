{{-- SPDX-License-Identifier: MIT --}}
{{-- Consistent settings navigation shown on every SchoolAdmin settings page. --}}
<nav class="settings-nav" aria-label="Settings sections">
    <a href="{{ url('/admin/settings') }}" class="{{ request()->is('admin/settings') ? 'is-current' : '' }}">All settings</a>
    <a href="{{ url('/admin/settings/generalsettings') }}" class="{{ request()->is('admin/settings/generalsettings') ? 'is-current' : '' }}">Site branding</a>
    <a href="{{ url('/admin/settings/exam-types') }}" class="{{ request()->is('admin/settings/exam-types') ? 'is-current' : '' }}">Exam types</a>
    <a href="{{ url('/admin/settings/integrations') }}" class="{{ request()->is('admin/settings/integrations') ? 'is-current' : '' }}">Integrations</a>
    <a href="{{ url('/admin/settings/seodetailsettings') }}" class="{{ request()->is('admin/settings/seodetailsettings') ? 'is-current' : '' }}">SEO details</a>
    <a href="{{ url('/admin/settings/maintenancesettings') }}" class="{{ request()->is('admin/settings/maintenancesettings') ? 'is-current' : '' }}">Maintenance</a>
</nav>
