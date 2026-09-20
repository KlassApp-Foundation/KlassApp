{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Settings',
        'subtitle' => 'Everything for your school in one place — grouped by what it affects.',
    ])

    <section class="mt-6">
        <h2 class="settings-hub-group">School</h2>
        <div class="settings-hub-grid">
            <a href="{{ url('/admin/schooldetails') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-blue-50 text-blue-600"><x-icons.sidebar name="classes"/></span>
                <span><strong>School profile</strong><small>Name, address, motto, website</small></span>
            </a>
            <a href="{{ url('/admin/settings/generalsettings') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-green-50 text-green-600"><x-icons.sidebar name="settings"/></span>
                <span><strong>Site branding</strong><small>Site title, school name, logo, favicon</small></span>
            </a>
        </div>
    </section>

    <section class="mt-6">
        <h2 class="settings-hub-group">Academics</h2>
        <div class="settings-hub-grid">
            <a href="{{ url('/admin/settings/exam-types') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-purple-50 text-purple-600"><x-icons.sidebar name="exams"/></span>
                <span><strong>Exam types</strong><small>Which exam types count toward results</small></span>
            </a>
        </div>
    </section>

    <section class="mt-6">
        <h2 class="settings-hub-group">Connections</h2>
        <div class="settings-hub-grid">
            <a href="{{ url('/admin/settings/integrations') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-amber-50 text-amber-600"><x-icons.sidebar name="messages"/></span>
                <span><strong>Integrations</strong><small>WhatsApp, Drive, Slack connections</small></span>
            </a>
        </div>
    </section>

    <section class="mt-6">
        <h2 class="settings-hub-group">Public presence</h2>
        <div class="settings-hub-grid">
            <a href="{{ url('/admin/settings/seodetailsettings') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-rose-50 text-rose-600"><x-icons.sidebar name="reports"/></span>
                <span><strong>SEO details</strong><small>Site description, keywords, social links</small></span>
            </a>
        </div>
    </section>

    <section class="mt-6">
        <h2 class="settings-hub-group">System</h2>
        <div class="settings-hub-grid">
            <a href="{{ url('/admin/settings/maintenancesettings') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-red-50 text-red-600"><x-icons.sidebar name="tasks"/></span>
                <span><strong>Maintenance &amp; access</strong><small>Maintenance mode, registration, login</small></span>
            </a>
        </div>
    </section>
</div>
@endsection
