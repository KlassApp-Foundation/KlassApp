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
            <a href="{{ url('/admin/school-profile') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-blue-50 text-blue-600"><x-icons.sidebar name="classes"/></span>
                {{-- Boundary made explicit: this card owns the school's identity as data. How it is
                     displayed and themed belongs to Site branding below, so the two cannot drift. --}}
                <span><strong>School profile</strong><small>The school's own details: name, motto, address, website, logo</small></span>
            </a>
            <a href="{{ url('/admin/settings/generalsettings') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-green-50 text-green-600"><x-icons.sidebar name="settings"/></span>
                <span><strong>Site branding</strong><small>How it is presented: site title, favicon, theme</small></span>
            </a>
        </div>
    </section>

    <section class="mt-6">
        <h2 class="settings-hub-group">Academics</h2>
        <div class="settings-hub-grid">
            <a href="{{ url('/admin/academics') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-blue-50 text-blue-600"><x-icons.sidebar name="classes"/></span>
                <span><strong>Academic years</strong><small>The school year and which one is current</small></span>
            </a>
            <a href="{{ url('/admin/academic-term/create') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-blue-50 text-blue-600"><x-icons.sidebar name="exams"/></span>
                <span><strong>Academic terms</strong><small>Term dates and which term is current</small></span>
            </a>
            <a href="{{ url('/admin/standard/create') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-purple-50 text-purple-600"><x-icons.sidebar name="classes"/></span>
                <span><strong>Structure &amp; classes</strong><small>Classes, streams and class teachers</small></span>
            </a>
            <a href="{{ url('/admin/subjects') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-purple-50 text-purple-600"><x-icons.sidebar name="reports"/></span>
                <span><strong>Subjects</strong><small>Subjects offered per class</small></span>
            </a>
            <a href="{{ url('/admin/settings/exam-types') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-purple-50 text-purple-600"><x-icons.sidebar name="exams"/></span>
                <span><strong>Exam types</strong><small>Which exam types count toward results</small></span>
            </a>
        </div>
    </section>

    <section class="mt-6">
        <h2 class="settings-hub-group">People</h2>
        <div class="settings-hub-grid">
            <a href="{{ url('/admin/teacher/add') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-amber-50 text-amber-600"><x-icons.sidebar name="classes"/></span>
                <span><strong>Teachers</strong><small>Teaching staff and their assignments</small></span>
            </a>
            <a href="{{ url('/admin/student/add') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-amber-50 text-amber-600"><x-icons.sidebar name="reports"/></span>
                <span><strong>Students</strong><small>Learners, classes and guardians</small></span>
            </a>
        </div>
    </section>

    <section class="mt-6">
        <h2 class="settings-hub-group">Finance</h2>
        <div class="settings-hub-grid">
            <a href="{{ url('/admin/fees-categories/create') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-green-50 text-green-600"><x-icons.sidebar name="reports"/></span>
                <span><strong>Fee structures</strong><small>Fee categories and amounts per class</small></span>
            </a>
            <a href="{{ url('/admin/subscriptions') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-green-50 text-green-600"><x-icons.sidebar name="tasks"/></span>
                <span><strong>Plan &amp; subscription</strong><small>Your plan and billing</small></span>
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
            <a href="{{ url('/admin/whatsapp/phone') }}" class="ds-card ds-card-hover settings-hub-card">
                <span class="settings-hub-icon bg-amber-50 text-amber-600"><x-icons.sidebar name="messages"/></span>
                <span><strong>WhatsApp number</strong><small>The number parents message, and its verification</small></span>
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
