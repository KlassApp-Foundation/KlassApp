{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
@php
    $student = $page['student'];
    $editUrl = url('/admin/student/edit/'.$student->name);
@endphp
<div class="px-4 md:px-6 py-4" data-testid="student-profile" style="background:#F8FAFC;color:#0F172A;">
    <p class="text-sm" style="margin:0 0 12px;">
        <a href="{{ url('/admin/students') }}" style="color:#1D4ED8;">Students</a>
    </p>

    <header style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start;justify-content:space-between;background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
        <div style="display:flex;gap:12px;align-items:flex-start;min-width:0;">
            <div aria-hidden="true" data-testid="student-initials" style="width:48px;height:48px;border-radius:999px;background:#DCFCE7;color:#166534;display:flex;align-items:center;justify-content:center;font-weight:700;flex:0 0 auto;">{{ $page['initials'] }}</div>
            <div style="min-width:0;">
                <h1 style="margin:0;font-size:22px;line-height:1.3;font-weight:700;color:#0F172A;">{{ $page['name'] }}</h1>
                <p style="margin:4px 0 0;font-size:14px;color:#475569;">
                    {{ $page['class_name'] ?: 'No class' }}
                    <span aria-hidden="true"> · </span>
                    <span data-testid="student-kls">{{ $page['kls'] ?: '—' }}</span>
                </p>
                <p style="margin:8px 0 0;">
                    <span data-testid="student-status" style="display:inline-block;border-radius:999px;padding:2px 8px;font-size:12px;font-weight:600;background:{{ $page['status'] === 'active' ? '#DCFCE7' : '#F1F5F9' }};color:{{ $page['status'] === 'active' ? '#166534' : '#475569' }};">{{ $page['status_label'] }}</span>
                </p>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="{{ $editUrl }}" class="ds-btn ds-btn-ghost text-sm" style="border:1px solid #E2E8F0;border-radius:8px;padding:8px 12px;color:#0F172A;background:#fff;">Edit</a>
            <div style="position:relative;">
                <button type="button" id="student-more" aria-haspopup="menu" aria-expanded="false" aria-controls="student-more-menu" style="border:1px solid #E2E8F0;border-radius:8px;padding:8px 12px;background:#fff;color:#0F172A;">More</button>
                <div id="student-more-menu" role="menu" hidden style="position:absolute;right:0;top:40px;min-width:180px;background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:4px;z-index:20;">
                    <button type="button" role="menuitem" id="open-deactivate" style="display:block;width:100%;text-align:left;background:transparent;border:0;padding:8px 10px;color:#B91C1C;">Deactivate</button>
                </div>
            </div>
        </div>
    </header>

    <dialog id="deactivate-student" style="border:1px solid #E2E8F0;border-radius:12px;padding:16px;color:#0F172A;">
        <form method="POST" action="{{ url('/admin/students/'.$student->id.'/deactivate') }}">
            @csrf
            <h2 style="margin:0 0 8px;font-size:16px;">Deactivate this student?</h2>
            <p style="margin:0 0 16px;color:#475569;font-size:14px;">They stay on record. Their status becomes inactive.</p>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" id="deactivate-cancel" autofocus style="border:1px solid #E2E8F0;border-radius:8px;padding:8px 12px;background:#fff;">Cancel</button>
                <button type="submit" style="border:0;border-radius:8px;padding:8px 12px;background:#B91C1C;color:#fff;">Deactivate</button>
            </div>
        </form>
    </dialog>

    <section aria-label="This term" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:16px;">
        <article data-testid="kpi-attendance" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
            <p style="margin:0;font-size:13px;color:#475569;">Attendance this term</p>
            @if($page['attendance']['empty'])
                <p style="margin:8px 0 0;font-size:16px;font-weight:600;">No attendance this term</p>
            @else
                <p style="margin:8px 0 0;font-size:22px;font-weight:700;">{{ $page['attendance']['percent'] }}%</p>
                <p style="margin:4px 0 0;font-size:13px;color:#475569;">{{ $page['attendance']['absences'] }} {{ $page['attendance']['absences'] === 1 ? 'absence' : 'absences' }}</p>
            @endif
        </article>
        <article data-testid="kpi-exam" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
            <p style="margin:0;font-size:13px;color:#475569;">Latest exam</p>
            @if($page['exam']['empty'])
                <p style="margin:8px 0 0;font-size:16px;font-weight:600;">No exam this term</p>
            @else
                <p style="margin:8px 0 0;font-size:22px;font-weight:700;">{{ $page['exam']['average'] }}@if($page['exam']['grade']) <span style="font-size:16px;">{{ $page['exam']['grade'] }}</span>@endif</p>
            @endif
        </article>
        <article data-testid="kpi-fees" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
            <p style="margin:0;font-size:13px;color:#475569;">Fees balance</p>
            @if($page['fees']['empty'])
                <p style="margin:8px 0 0;font-size:16px;font-weight:600;">No fees on record</p>
            @else
                <p style="margin:8px 0 0;font-size:22px;font-weight:700;">{{ $page['fees']['balance'] ?? '—' }}</p>
                <p style="margin:4px 0 0;font-size:13px;color:#475569;">{{ $page['fees']['status'] }}@if($page['fees']['paid']) · paid {{ $page['fees']['paid'] }}@endif</p>
            @endif
        </article>
    </section>

    <nav data-testid="student-profile-tabs" aria-label="Student profile" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;">
        @foreach($tabs as $key => $label)
            <a href="{{ url('/admin/students/'.$student->id.'?tab='.$key) }}" data-tab="{{ $key }}" aria-current="{{ $tab === $key ? 'page' : 'false' }}" style="padding:8px 12px;border-radius:999px;text-decoration:none;font-size:14px;font-weight:600;background:{{ $tab === $key ? '#0F172A' : '#fff' }};color:{{ $tab === $key ? '#fff' : '#0F172A' }};border:1px solid #E2E8F0;">{{ $label }}</a>
        @endforeach
    </nav>

    <section style="margin-top:16px;background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
        @if($tab === 'overview')
            <h2 style="margin:0 0 8px;font-size:16px;">Overview</h2>
            <p style="margin:0;color:#475569;">{{ $page['class_name'] ?: 'No class' }} · {{ $page['kls'] ?: 'No KLS number' }} · {{ $page['status_label'] }}</p>
        @elseif($tab === 'academics')
            <h2 style="margin:0 0 8px;font-size:16px;">Marks this term</h2>
            @if($page['marks'] === [])
                <p style="margin:0;color:#475569;">No marks this term.</p>
            @else
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:8px 0;border-bottom:1px solid #E2E8F0;">Subject</th>
                            <th style="text-align:left;padding:8px 0;border-bottom:1px solid #E2E8F0;">Mark</th>
                            <th style="text-align:left;padding:8px 0;border-bottom:1px solid #E2E8F0;">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($page['marks'] as $mark)
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #F1F5F9;">{{ $mark['subject'] }}</td>
                                <td style="padding:8px 0;border-bottom:1px solid #F1F5F9;">{{ $mark['marks'] }}</td>
                                <td style="padding:8px 0;border-bottom:1px solid #F1F5F9;">{{ $mark['grade'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @elseif($tab === 'attendance')
            <h2 style="margin:0 0 8px;font-size:16px;">Attendance</h2>
            @if($page['attendance']['empty'])
                <p style="margin:0;color:#475569;">No attendance this term.</p>
            @else
                <p style="margin:0;">{{ $page['attendance']['percent'] }}% present, {{ $page['attendance']['absences'] }} {{ $page['attendance']['absences'] === 1 ? 'absence' : 'absences' }}.</p>
            @endif
        @elseif($tab === 'fees')
            <h2 style="margin:0 0 8px;font-size:16px;">Fees</h2>
            @if($page['fees']['empty'])
                <p style="margin:0;color:#475569;">No fees on record.</p>
            @else
                <p style="margin:0 0 12px;">Balance {{ $page['fees']['balance'] ?? '—' }} · {{ $page['fees']['status'] }}@if($page['fees']['expected']) · expected {{ $page['fees']['expected'] }}@endif</p>
                @if($page['payments'] === [])
                    <p style="margin:0;color:#475569;">No payments recorded.</p>
                @else
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($page['payments'] as $payment)
                            <li>{{ $payment['paid_on'] }} · {{ $payment['amount'] }}</li>
                        @endforeach
                    </ul>
                @endif
            @endif
        @else
            <h2 style="margin:0 0 8px;font-size:16px;">Parents and guardians</h2>
            @if($page['parents'] === [])
                <p style="margin:0;color:#475569;">No parent linked.</p>
            @else
                <ul style="margin:0;padding-left:18px;">
                    @foreach($page['parents'] as $parent)
                        <li>{{ $parent['name'] }} · {{ $parent['phone'] }} · WhatsApp {{ $parent['whatsapp'] }}</li>
                    @endforeach
                </ul>
            @endif
        @endif
    </section>
</div>
<script>
(function () {
    var button = document.getElementById('student-more');
    var menu = document.getElementById('student-more-menu');
    var dialog = document.getElementById('deactivate-student');
    var cancel = document.getElementById('deactivate-cancel');
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
})();
</script>
@endsection
