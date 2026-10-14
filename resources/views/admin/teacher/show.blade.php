{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
@php
    $teacher = $page['teacher'];
    $editKey = filled($teacher->name) ? $teacher->name : $teacher->id;
    $editUrl = url('/admin/teacher/edit/'.$editKey);
    $showUrl = url('/admin/teacher/show/'.$editKey);
    $btn = 'min-height:44px;display:inline-flex;align-items:center;box-sizing:border-box;border:1px solid #E2E8F0;border-radius:8px;padding:8px 12px;background:#fff;color:#0F172A;font-size:14px;font-weight:600;text-decoration:none;';
@endphp
<div class="px-4 md:px-6 py-4" data-testid="teacher-profile">
    <x-person-profile
        :user="$teacher"
        :name="$page['name']"
        :initials="$page['initials']"
        :role="$page['role']"
        :joined="$page['joined']"
        :status="$page['status']"
        :status-label="$page['status_label']"
        :tabs="$tabs"
        :active="$tab"
        :base-url="$showUrl"
        :back-url="url('/admin/teachers')"
        back-label="Teachers"
    >
        <x-slot:actions>
            <a href="{{ $editUrl }}" style="{{ $btn }}">Edit</a>
            <div style="position:relative;">
                <button type="button" id="teacher-more" aria-haspopup="menu" aria-expanded="false" aria-controls="teacher-more-menu" style="{{ $btn }}">More</button>
                <div id="teacher-more-menu" role="menu" hidden style="position:absolute;right:0;top:48px;min-width:200px;background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:4px;z-index:20;">
                    <button type="button" role="menuitem" id="open-deactivate" style="{{ $btn }}width:100%;border:0;text-align:left;color:#B91C1C;">Deactivate</button>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:tiles>
            <article data-testid="kpi-classes" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
                <p style="margin:0;font-size:13px;color:#475569;">Classes taught</p>
                @if($page['classes']['empty'])
                    <p style="margin:8px 0 0;font-size:16px;font-weight:600;">No classes yet</p>
                @else
                    <p style="margin:8px 0 0;font-size:22px;font-weight:700;">{{ $page['classes']['count'] }}</p>
                    <p style="margin:4px 0 0;font-size:13px;color:#475569;">{{ $page['classes']['names'] }}</p>
                @endif
            </article>
            <article data-testid="kpi-subjects" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
                <p style="margin:0;font-size:13px;color:#475569;">Subjects taught</p>
                @if($page['subjects']['empty'])
                    <p style="margin:8px 0 0;font-size:16px;font-weight:600;">No subjects yet</p>
                @else
                    <p style="margin:8px 0 0;font-size:22px;font-weight:700;">{{ $page['subjects']['count'] }}</p>
                    <p style="margin:4px 0 0;font-size:13px;color:#475569;">{{ $page['subjects']['names'] }}</p>
                @endif
            </article>
            <article data-testid="kpi-pending" data-pending="{{ $page['pending']['count'] }}" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
                <p style="margin:0;font-size:13px;color:#475569;">Still pending marks</p>
                @if($page['pending']['empty'])
                    <p style="margin:8px 0 0;font-size:16px;font-weight:600;">{{ $page['pending']['detail'] }}</p>
                @else
                    <p style="margin:8px 0 0;font-size:22px;font-weight:700;">{{ $page['pending']['count'] }}</p>
                    <p style="margin:4px 0 0;font-size:13px;color:#475569;">{{ $page['pending']['detail'] }}</p>
                @endif
            </article>
        </x-slot:tiles>

        <dialog id="deactivate-teacher" style="margin:auto;border:1px solid #E2E8F0;border-radius:12px;padding:16px;color:#0F172A;max-width:420px;">
            <form method="POST" action="{{ url('/admin/teacher/show/'.$editKey.'/deactivate') }}">
                @csrf
                <h2 style="margin:0 0 8px;font-size:16px;">Deactivate this teacher?</h2>
                <p style="margin:0 0 16px;color:#475569;font-size:14px;">They stay on record. Their status becomes inactive.</p>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" id="deactivate-cancel" autofocus style="{{ $btn }}">Cancel</button>
                    <button type="submit" style="{{ $btn }}border:0;background:#B91C1C;color:#fff;">Deactivate</button>
                </div>
            </form>
        </dialog>

        @if($tab === 'teaches')
            <section data-testid="teacher-teaches" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
                <h2 style="margin:0 0 12px;font-size:16px;">Teaches</h2>
                @if($page['teaches'] === [])
                    <p style="margin:0;color:#475569;">No classes yet</p>
                @else
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:14px;">
                            <thead>
                                <tr>
                                    <th style="text-align:left;padding:8px 0;border-bottom:1px solid #E2E8F0;">Class</th>
                                    <th style="text-align:left;padding:8px 0;border-bottom:1px solid #E2E8F0;">Subject</th>
                                    <th style="text-align:left;padding:8px 0;border-bottom:1px solid #E2E8F0;">Students</th>
                                    <th style="text-align:left;padding:8px 0;border-bottom:1px solid #E2E8F0;">Latest exam</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($page['teaches'] as $row)
                                    <tr data-teach="{{ $row['slug'] }}" data-students="{{ $row['students'] }}" data-marks="{{ $row['marks_key'] }}">
                                        <td style="padding:8px 8px 8px 0;border-bottom:1px solid #F1F5F9;">{{ $row['class_name'] }}</td>
                                        <td style="padding:8px 8px 8px 0;border-bottom:1px solid #F1F5F9;">{{ $row['subject'] }}</td>
                                        <td style="padding:8px 8px 8px 0;border-bottom:1px solid #F1F5F9;">{{ $row['students'] }}</td>
                                        <td style="padding:8px 0;border-bottom:1px solid #F1F5F9;">{{ $row['marks_label'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;" data-testid="teacher-overview">
                <section style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
                    <h2 style="margin:0 0 12px;font-size:16px;">Details</h2>
                    <dl style="margin:0;display:grid;grid-template-columns:140px 1fr;gap:8px 12px;font-size:14px;">
                        <dt style="color:#475569;">Employee ID</dt><dd style="margin:0;">{{ $page['employee_id'] }}</dd>
                        <dt style="color:#475569;">Designation</dt><dd style="margin:0;">{{ $page['designation'] }}</dd>
                        <dt style="color:#475569;">Gender</dt><dd style="margin:0;">{{ $page['gender'] }}</dd>
                        <dt style="color:#475569;">Date of birth</dt><dd style="margin:0;">{{ $page['date_of_birth'] }}</dd>
                        <dt style="color:#475569;">Joining date</dt><dd style="margin:0;">{{ $page['joining_date'] }}</dd>
                        <dt style="color:#475569;">Job type</dt><dd style="margin:0;">{{ $page['job_type'] }}</dd>
                    </dl>
                </section>
                <section style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
                    <h2 style="margin:0 0 12px;font-size:16px;">Contact</h2>
                    <dl style="margin:0;display:grid;grid-template-columns:140px 1fr;gap:8px 12px;font-size:14px;">
                        <dt style="color:#475569;">Phone</dt><dd style="margin:0;">{{ $page['phone'] }}</dd>
                        <dt style="color:#475569;">Email</dt><dd style="margin:0;">{{ $page['email'] }}</dd>
                        <dt style="color:#475569;">Last login</dt><dd style="margin:0;">{{ $page['last_login'] }}</dd>
                    </dl>
                </section>
            </div>
        @endif
    </x-person-profile>
</div>
@endsection

@push('scripts')
<style>
#deactivate-teacher::backdrop { background: rgba(15, 23, 42, 0.45); }
</style>
<script>
(function () {
    function bindTeacherMore() {
        var button = document.getElementById('teacher-more');
        var menu = document.getElementById('teacher-more-menu');
        var dialog = document.getElementById('deactivate-teacher');
        var cancel = document.getElementById('deactivate-cancel');
        if (!button || !menu || !dialog || !cancel || button.dataset.bound === '1') {
            return;
        }
        button.dataset.bound = '1';
        function closeMenu() {
            menu.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        }
        button.addEventListener('click', function () {
            var open = menu.hidden;
            menu.hidden = !open;
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function (event) {
            if (!button.contains(event.target) && !menu.contains(event.target)) {
                closeMenu();
            }
        });
        document.getElementById('open-deactivate').addEventListener('click', function () {
            closeMenu();
            dialog.showModal();
            cancel.focus();
        });
        cancel.addEventListener('click', function () {
            dialog.close();
            button.focus();
        });
    }
    if (document.readyState === 'complete') {
        bindTeacherMore();
    } else {
        window.addEventListener('load', bindTeacherMore);
    }
})();
</script>
@endpush
