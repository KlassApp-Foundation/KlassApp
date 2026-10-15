{{-- SPDX-License-Identifier: MIT --}}
<style>
    .mr { max-width: 760px; padding: 20px 16px 40px; color: var(--d-text, #1E293B); }
    .mr h1 { font-size: 22px; line-height: 1.25; margin: 0 0 4px; }
    .mr-sub { color: var(--d-text-secondary, #64748B); margin: 0 0 16px; }
    .mr-card { background: var(--d-white, #fff); border: 1px solid var(--d-border, #E2E8F0); border-radius: var(--d-radius-lg, 12px); padding: 18px; margin: 0 0 16px; }
    .mr-field { margin: 0 0 14px; }
    .mr-field label, .mr-field legend { display: block; font-weight: 600; margin: 0 0 6px; }
    .mr-field select, .mr-field input[type="datetime-local"] { width: 100%; max-width: 420px; min-height: 44px; padding: 0 10px; border: 1px solid var(--d-border, #E2E8F0); border-radius: var(--d-radius-md, 8px); background: #fff; }
    .mr-field fieldset { border: 0; margin: 0; padding: 0; }
    .mr-radio { display: flex; align-items: center; gap: 8px; min-height: 44px; }
    .mr-help { color: var(--d-text-secondary, #64748B); font-size: 13px; margin: 4px 0 0; }
    .mr-error { color: #B91C1C; font-size: 13px; margin: 4px 0 0; }
    .mr-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .mr-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 16px; border-radius: var(--d-radius-md, 8px); border: 1px solid var(--d-accent, #15803D); background: var(--d-accent, #15803D); color: #fff; font-weight: 600; text-decoration: none; cursor: pointer; white-space: nowrap; }
    .mr-btn--soft { background: #fff; color: var(--d-accent, #15803D); }
    .mr-btn:focus-visible { outline: none; box-shadow: var(--d-focus-ring, 0 0 0 3px rgba(30,111,217,.25)); }
    .mr-msg { white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 12px; overflow-x: auto; margin: 0; }
    .mr-dl { display: grid; grid-template-columns: max-content 1fr; gap: 6px 16px; margin: 0 0 12px; }
    .mr-dl dt { color: var(--d-text-secondary, #64748B); }
    .mr-dl dd { margin: 0; font-weight: 600; }
    .mr-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .mr-table th, .mr-table td { text-align: left; padding: 8px 6px; border-top: 1px solid var(--d-border, #E2E8F0); vertical-align: top; }
    .mr-note { background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 10px 12px; margin: 0 0 16px; }
    .mr-scroll { overflow-x: auto; }
</style>
