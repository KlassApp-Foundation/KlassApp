{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
@php
    $parent = $page['parent'];
    $editKey = filled($parent->name) ? $parent->name : $parent->id;
    $editUrl = url('/admin/parent/edit/'.$editKey);
    $btn = 'min-height:44px;display:inline-flex;align-items:center;box-sizing:border-box;border:1px solid #E2E8F0;border-radius:8px;padding:8px 12px;background:#fff;color:#0F172A;font-size:14px;font-weight:600;text-decoration:none;';
@endphp
<div class="px-4 md:px-6 py-4" data-testid="parent-profile">
    <x-person-profile
        :user="$parent"
        :name="$page['name']"
        :initials="$page['initials']"
        :role="$page['role']"
        :status="$page['status']"
        :status-label="$page['status_label']"
        :tabs="$tabs"
        :active="$tab"
        :base-url="$showUrl"
        :back-url="url('/admin/parents')"
        back-label="Parents"
    >
        <x-slot:actions>
            <a href="{{ $editUrl }}" style="{{ $btn }}">Edit</a>
            <div style="position:relative;">
                <button type="button" id="parent-more" aria-haspopup="menu" aria-expanded="false" aria-controls="parent-more-menu" style="{{ $btn }}">More</button>
                <div id="parent-more-menu" role="menu" hidden style="position:absolute;right:0;top:48px;min-width:240px;background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:8px;z-index:30;">
                    <button type="button" role="menuitem" id="open-deactivate" style="{{ $btn }}width:100%;border:0;text-align:left;color:#B91C1C;">Deactivate</button>
                    <button type="button" role="menuitem" id="open-reset" style="{{ $btn }}width:100%;border:0;text-align:left;">Reset password</button>
                    <div style="margin-top:8px;padding-top:8px;border-top:1px solid #E2E8F0;">
                        <p style="margin:0 0 8px;font-size:13px;font-weight:600;">Change login details</p>
                        <form method="POST" action="{{ url('/admin/credentials/add/'.$editKey) }}">
                            @csrf
                            <input type="hidden" name="user_group" value="7">
                            <label style="display:block;font-size:13px;color:#475569;">Email
                                <input name="email" type="email" value="{{ $parent->email }}" style="{{ $btn }}width:100%;margin-top:4px;">
                            </label>
                            <label style="display:block;font-size:13px;color:#475569;margin-top:8px;">Mobile
                                <input name="mobile_no" type="text" value="{{ $parent->mobile_no }}" style="{{ $btn }}width:100%;margin-top:4px;">
                            </label>
                            <button type="submit" style="{{ $btn }}margin-top:8px;">Save login details</button>
                        </form>
                    </div>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:tiles>
            <article data-testid="kpi-children" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
                <p style="margin:0;font-size:13px;color:#475569;">Children</p>
                @if($page['children_count'] === 0)
                    <p style="margin:8px 0 0;font-size:16px;font-weight:600;">No children yet</p>
                @else
                    <p style="margin:8px 0 0;font-size:22px;font-weight:700;">{{ $page['children_count'] }}</p>
                @endif
            </article>
            <article data-testid="kpi-fees" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
                <p style="margin:0;font-size:13px;color:#475569;">Fees balance</p>
                <p style="margin:8px 0 0;font-size:16px;font-weight:600;">{{ $page['fees']['text'] }}</p>
            </article>
            <article data-testid="kpi-whatsapp" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
                <p style="margin:0;font-size:13px;color:#475569;">WhatsApp</p>
                <p style="margin:8px 0 0;font-size:16px;font-weight:600;">{{ $page['whatsapp'] }}</p>
            </article>
        </x-slot:tiles>

        <dialog id="deactivate-parent" style="margin:auto;border:1px solid #E2E8F0;border-radius:12px;padding:16px;color:#0F172A;max-width:420px;">
            <form method="POST" action="{{ url('/admin/parent/show/'.$editKey.'/deactivate') }}">
                @csrf
                <h2 style="margin:0 0 8px;font-size:16px;">Deactivate this parent?</h2>
                <p style="margin:0 0 16px;color:#475569;font-size:14px;">They stay on record. Their status becomes inactive.</p>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" id="deactivate-cancel" autofocus style="{{ $btn }}">Cancel</button>
                    <button type="submit" style="{{ $btn }}border:0;background:#B91C1C;color:#fff;">Deactivate</button>
                </div>
            </form>
        </dialog>

        <dialog id="reset-parent" style="margin:auto;border:1px solid #E2E8F0;border-radius:12px;padding:16px;color:#0F172A;max-width:420px;">
            <form method="POST" action="{{ url('/admin/parent/show/'.$editKey.'/reset-password') }}">
                @csrf
                <h2 style="margin:0 0 8px;font-size:16px;">Reset this password?</h2>
                <p style="margin:0 0 16px;color:#475569;font-size:14px;">This sends a reset. It does not happen until you confirm.</p>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" id="reset-cancel" style="{{ $btn }}">Cancel</button>
                    <button type="submit" style="{{ $btn }}">Reset password</button>
                </div>
            </form>
        </dialog>

        @if($tab === 'children')
            <section data-testid="parent-children" style="display:grid;gap:12px;">
                @if($page['children'] === [])
                    <p style="margin:0;color:#475569;">No children yet</p>
                @else
                    @foreach($page['children'] as $child)
                        <article data-testid="child-card" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
                            <h2 style="margin:0 0 8px;font-size:16px;">{{ $child['name'] }}</h2>
                            <p style="margin:0;font-size:14px;">{{ $child['class_name'] }}</p>
                            <p style="margin:4px 0 0;font-size:14px;">{{ $child['kls'] }}</p>
                            <p style="margin:4px 0 12px;font-size:14px;">{{ $child['fees'] }}</p>
                            <a href="{{ $child['profile_url'] }}" style="{{ $btn }}">View student</a>
                            @if($child['report_url'])
                                <a href="{{ $child['report_url'] }}" style="{{ $btn }}margin-left:8px;">Report card</a>
                            @endif
                        </article>
                    @endforeach
                @endif
            </section>
        @else
            <section data-testid="parent-overview" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
                <h2 style="margin:0 0 12px;font-size:16px;">Contact</h2>
                <dl style="margin:0;display:grid;grid-template-columns:140px 1fr;gap:8px 12px;font-size:14px;">
                    <dt style="color:#475569;">Phone</dt><dd style="margin:0;">{{ $page['phone'] }}</dd>
                    <dt style="color:#475569;">Email</dt><dd style="margin:0;">{{ $page['email'] }}</dd>
                    <dt style="color:#475569;">Last login</dt><dd style="margin:0;">{{ $page['last_login'] }}</dd>
                    <dt style="color:#475569;">WhatsApp</dt><dd style="margin:0;">{{ $page['whatsapp'] }}</dd>
                </dl>
            </section>
        @endif
    </x-person-profile>
</div>
@endsection

@push('scripts')
<style>
#deactivate-parent::backdrop, #reset-parent::backdrop { background: rgba(15, 23, 42, 0.45); }
</style>
<script>
(function () {
    function bindParentMore() {
        var button = document.getElementById('parent-more');
        var menu = document.getElementById('parent-more-menu');
        var dialog = document.getElementById('deactivate-parent');
        var reset = document.getElementById('reset-parent');
        var cancel = document.getElementById('deactivate-cancel');
        var resetCancel = document.getElementById('reset-cancel');
        if (!button || !menu || !dialog || !reset || button.dataset.bound === '1') {
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
        document.getElementById('open-reset').addEventListener('click', function () {
            closeMenu();
            reset.showModal();
        });
        resetCancel.addEventListener('click', function () {
            reset.close();
            button.focus();
        });
    }
    if (document.readyState === 'complete') {
        bindParentMore();
    } else {
        window.addEventListener('load', bindParentMore);
    }
})();
</script>
@endpush
