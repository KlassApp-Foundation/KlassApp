{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
@php
    $student = $page['student'];
    $editUrl = url('/admin/student/edit/'.$student->name);
@endphp
@php
    $feeMoney = function (?string $amount) use ($page): string {
        if ($amount === null || $amount === '') {
            return '—';
        }

        return ($page['currency'] ? $page['currency'].' ' : '').$amount;
    };
@endphp
<div class="ka-bare-page" data-testid="student-profile">
    <x-person-profile
        :user="$student"
        :name="$page['name']"
        :initials="$page['initials']"
        :class-name="$page['class_name']"
        :kls="$page['kls'] ?: 'No KLS number'"
        :facts="$page['facts']"
        :status="$page['status']"
        :status-label="$page['status_label']"
        :tabs="$tabs"
        :active="$tab"
        :base-url="url('/admin/students/'.$student->id)"
        :back-url="url('/admin/students')"
        back-label="Students"
    >
        <x-slot:actions>
            <a data-testid="profile-message" href="{{ $page['parents'][0]['profile_url'] ?? url('/admin/parents') }}" class="ds-btn ds-btn-ghost text-sm" style="min-height:44px;display:inline-flex;align-items:center;padding:0 12px;border:1px solid #E2E8F0;border-radius:8px;background:transparent;color:#0F172A;text-decoration:none;">Message</a>
            <a href="{{ $editUrl }}" class="ds-btn ds-btn-ghost text-sm" style="border:1px solid #E2E8F0;border-radius:8px;padding:8px 12px;color:#0F172A;background:#fff;">Edit</a>
            <div style="position:relative;">
                <button type="button" id="student-more" aria-haspopup="menu" aria-expanded="false" aria-controls="student-more-menu" style="border:1px solid #E2E8F0;border-radius:8px;padding:8px 12px;background:#fff;color:#0F172A;">More</button>
                <div id="student-more-menu" role="menu" hidden style="position:absolute;right:0;top:40px;min-width:180px;background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:4px;z-index:20;">
                    <button type="button" role="menuitem" id="open-deactivate" style="display:block;width:100%;text-align:left;background:transparent;border:0;padding:8px 10px;color:#B91C1C;">Deactivate</button>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:tiles>
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
            <p data-testid="exam-label" style="margin:0;font-size:13px;color:#475569;">{{ $page['exam']['label'] ?? 'Latest exam' }}</p>
            @if($page['exam']['empty'])
                <p style="margin:8px 0 0;font-size:16px;font-weight:600;">No exam this term</p>
            @else
                <p data-testid="exam-score" style="margin:8px 0 0;font-size:22px;font-weight:700;">{{ $page['exam']['average'] }}% · {{ $page['exam']['grade'] ?: '—' }}</p>
                <p data-testid="exam-name" style="margin:4px 0 0;font-size:13px;color:#475569;">{{ $page['exam']['name'] ?? '' }}</p>
            @endif
        </article>
        <article data-testid="kpi-fees" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
            <p style="margin:0;font-size:13px;color:#475569;">Balance</p>
            @if($page['fees']['empty'])
                <p style="margin:8px 0 0;font-size:16px;font-weight:600;">No fees on record</p>
            @else
                <p style="margin:8px 0 0;font-size:22px;font-weight:700;" data-testid="fee-balance">{{ $feeMoney($page['fees']['balance']) }}</p>
                @if($page['currency'] === null)
                    <p style="margin:4px 0 0;font-size:13px;"><a data-testid="fee-currency-hint" href="{{ url('/admin/settings') }}" style="color:#475569;">Set your currency</a></p>
                @endif
                <p style="margin:4px 0 0;font-size:13px;color:#475569;">
                    @if($page['fees']['paid'] && $page['fees']['expected'])
                        <span data-testid="fee-paid-of">Paid {{ $feeMoney($page['fees']['paid']) }} of {{ $feeMoney($page['fees']['expected']) }}</span>
                    @endif
                    <span data-testid="fee-status" style="display:inline-block;border-radius:999px;padding:2px 8px;font-size:12px;font-weight:600;background:#F1F5F9;">{{ $page['fees']['status'] }}</span>
                </p>
            @endif
        </article>
        </x-slot:tiles>

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

        @if($tab === 'overview')
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;" data-testid="student-overview">
                <section style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
                    <h2 style="margin:0 0 12px;font-size:16px;">Student</h2>
                    <dl style="margin:0;display:grid;grid-template-columns:140px 1fr;gap:8px 12px;font-size:14px;">
                        <dt style="color:#475569;">Full name</dt><dd style="margin:0;">{{ $page['name'] }}</dd>
                        <dt style="color:#475569;">KLS number</dt><dd style="margin:0;" data-testid="student-kls">{{ $page['kls'] ?: 'No KLS number' }}</dd>
                        <dt style="color:#475569;">Class</dt><dd style="margin:0;">{{ $page['class_name'] ?: 'No class' }}</dd>
                        <dt style="color:#475569;">Gender</dt><dd style="margin:0;">{{ $page['gender'] }}</dd>
                        <dt style="color:#475569;">Date of birth</dt><dd style="margin:0;">{{ $page['date_of_birth'] }}</dd>
                        <dt style="color:#475569;">Joined</dt><dd style="margin:0;">{{ $page['joined'] }}</dd>
                    </dl>
                </section>
                <section style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
                    <h2 style="margin:0 0 12px;font-size:16px;">Parents and guardians</h2>
                    @include('admin.students._guardians', ['parents' => $page['parents']])
                </section>
            </div>
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
            @include('admin.students._guardians', ['parents' => $page['parents']])
        @endif
    </x-person-profile>
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
