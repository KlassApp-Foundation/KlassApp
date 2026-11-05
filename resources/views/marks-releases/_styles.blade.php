{{-- SPDX-License-Identifier: MIT --}}
<style>
    .mr { width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 0 0 32px; color: var(--d-text, #1E293B); }
    .mr-page { display: grid; grid-template-columns: minmax(0, 1fr) minmax(240px, 340px); grid-template-areas: "filters aside" "reason aside" "approvals aside" "sheet aside"; gap: 16px; align-items: start; width: 100%; min-width: 0; }
    .mr-filters { grid-area: filters; min-width: 0; }
    .mr-reason { grid-area: reason; min-width: 0; }
    .mr-approvals { grid-area: approvals; min-width: 0; }
    .mr-aside { grid-area: aside; min-width: 0; background: #fff; border: 1px solid var(--d-border, #E2E8F0); border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
    .mr-head { display: flex; flex-direction: column; gap: 8px; margin: 0 0 12px; }
    .mr-bar { display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end; min-width: 0; background: #fff; border: 1px solid var(--d-border, #E2E8F0); border-radius: 12px; padding: 10px 12px; }
    .mr-seg, .mr-btn { flex: 0 0 auto; }
    .mr-when-at { flex: 1 1 12.5rem; min-width: 0; max-width: 100%; }
    @media (min-width: 768px) {
        .mr-scope.ka-filter-panel { display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end; width: 100%; }
    }
    .mr-control { display: flex; flex: 1 1 8rem; flex-direction: column; gap: 4px; margin: 0; min-width: 0; max-width: none; }
    .mr-control:has(select[name="exam_id"]) { flex: 2 1 12rem; min-width: 0; max-width: none; }
    .mr-reason-box { display: flex; flex-direction: column; gap: 8px; margin: 0; padding: 16px; background: #FFFBEB; border: 2px solid #B45309; border-radius: 14px; }
    .mr-reason-box span { color: #78350F; }
    .mr-reason-box textarea { width: 100%; min-height: 72px; border: 1px solid #E2E8F0; border-radius: 8px; padding: 8px; background: #fff; color: #0F172A; font: inherit; }
    .mr-control span, .mr-wa h2 { font-size: 12px; font-weight: 700; color: #475569; }
    .mr-control select, .mr-when-at input { width: 100%; min-width: 0; min-height: 44px; max-width: 100%; border: 1px solid #E2E8F0; border-radius: 8px; background: #fff; padding: 0 8px; color: #0F172A; font-size: 14px; box-sizing: border-box; }
    .mr-when-at input { font-size: 12px; }
    .mr-seg { display: inline-flex; align-items: stretch; min-height: 40px; border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; background: #F8FAFC; }
    .mr-seg label { position: relative; display: flex; align-items: center; padding: 0 10px; font-size: 13px; font-weight: 700; color: #334155; cursor: pointer; white-space: nowrap; }
    .mr-seg input { position: absolute; opacity: 0; inset: 0; margin: 0; }
    .mr-seg label:has(input:checked) { background: #15803D; color: #fff; }
    .mr-when-at[hidden] { display: none !important; }
    .mr-wa { background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 12px; padding: 8px 10px; }
    .mr-wa summary { cursor: pointer; font-size: 13px; font-weight: 700; color: #14532D; }
    .mr-wa-body { font-size: 12px; line-height: 1.35; color: #0F172A; margin-top: 6px; max-height: 96px; overflow: auto; }
    @media (max-width: 767px) {
        .mr-page { grid-template-columns: minmax(0, 1fr); grid-template-areas: "filters" "reason" "approvals" "sheet" "aside"; }
        .mr-aside .mr-btn { width: 100%; min-height: 52px; }
    }
    .mr-wa-pre { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; white-space: pre; margin: 8px 0 0; overflow-x: auto; }
    .mr-when-label { margin: 0; font-size: 13px; font-weight: 600; }
    .mr-sheet-tools { display: flex; gap: 12px; align-items: center; margin: 0 0 8px; font-size: 14px; font-weight: 600; }
    .mr-sheet { grid-area: sheet; overflow: auto; max-height: 70vh; max-width: 100%; min-width: 0; border: 1px solid #E2E8F0; border-radius: 12px; background: #fff; }
    .mr-sheet--wide .mr-grid { min-width: 1100px; }
    .mr-grid { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 15px; color: #0F172A; }
    .mr-grid th, .mr-grid td { padding: 8px 10px; border-bottom: 1px solid #E2E8F0; text-align: left; background: #fff; }
    .mr-grid thead th { position: sticky; top: 0; z-index: 2; background: #F8FAFC; font-size: 13px; }
    .mr-grid thead button { background: transparent; border: 0; padding: 0; font: inherit; font-weight: 700; color: #0F172A; cursor: pointer; text-align: inherit; }
    .mr-num { text-align: right; white-space: nowrap; }
    .mr-group th { background: #EEF6FF; font-size: 15px; font-weight: 700; position: sticky; top: 36px; z-index: 1; }
    .mr-mark { font-weight: 700; font-variant-numeric: tabular-nums; }
    .mr-grade { display: block; font-size: 12px; font-weight: 600; color: #475569; }
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
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
    .mr-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: 0 10px; border-radius: var(--d-radius-md, 8px); border: 1px solid var(--d-accent, #15803D); background: var(--d-accent, #15803D); color: #fff; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; white-space: nowrap; }
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
