{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.accountant.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin">
    <div class="flex flex-wrap lg:flex-row justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold" style="font-family: Sora, sans-serif;">
                Batch Payroll Run
            </h1>
            <p class="text-sm" style="color: var(--d-muted); font-family: 'DM Sans', sans-serif;">
                Select a template and pay period, then preview computed payrolls before confirming.
            </p>
        </div>
    </div>

    <div x-data="batchPayroll()" x-cloak>
        {{-- Step 1: Selection form --}}
        <div class="bg-white rounded-xl p-6 mb-6" style="border: 1px solid var(--d-border); box-shadow: var(--shadow-sm);">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: var(--d-text);">Payroll Template</label>
                    <select x-model="templateId" class="w-full rounded-lg p-2.5 text-sm" style="border: 1px solid var(--d-border); background: var(--d-shell);">
                        <option value="">-- Select Template --</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: var(--d-text);">Start Date</label>
                    <input type="date" x-model="startDate" class="w-full rounded-lg p-2.5 text-sm" style="border: 1px solid var(--d-border); background: var(--d-shell);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: var(--d-text);">End Date</label>
                    <input type="date" x-model="endDate" class="w-full rounded-lg p-2.5 text-sm" style="border: 1px solid var(--d-border); background: var(--d-shell);">
                </div>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button x-on:click="preview()"
                        x-bind:disabled="!canPreview"
                        class="px-6 py-2.5 rounded-lg text-white font-medium text-sm transition-all"
                        x-bind:style="!canPreview ? 'background: var(--d-muted); cursor: not-allowed;' : 'background: var(--d-blue);'"
                        style="background: var(--d-blue);">
                    <span x-text="loading ? 'Computing...' : 'Preview Payroll'"></span>
                </button>
                <span x-show="error" x-text="error" class="text-sm" style="color: #DC2626;"></span>
            </div>
        </div>

        {{-- Step 2: Preview table (shown after preview loads) --}}
        <template x-if="rows.length > 0">
            <div>
                <div class="bg-white rounded-xl overflow-hidden mb-4" style="border: 1px solid var(--d-border); box-shadow: var(--shadow-sm);">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr style="background: var(--d-shell);">
                                    <th class="text-left p-3 font-semibold">Staff</th>
                                    <th class="text-right p-3 font-semibold">Gross (UGX)</th>
                                    <th class="text-right p-3 font-semibold" style="color: var(--d-amber);">PAYE</th>
                                    <th class="text-right p-3 font-semibold" style="color: var(--d-blue);">NSSF</th>
                                    <th class="text-right p-3 font-semibold" style="color: var(--d-amber);">LST</th>
                                    <th class="text-right p-3 font-semibold">Leave Ded.</th>
                                    <th class="text-right p-3 font-semibold">Earnings</th>
                                    <th class="text-right p-3 font-semibold" style="color: var(--d-green);">Net Pay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, idx) in rows" x-bind:key="row.staff_id">
                                    <tr style="border-top: 1px solid var(--d-border);">
                                        <td class="p-3 font-medium" x-text="row.staff_name"></td>
                                        <td class="p-3 text-right" x-text="numberFormat(row.gross)"></td>
                                        <td class="p-3 text-right" x-text="numberFormat(row.paye)"></td>
                                        <td class="p-3 text-right" x-text="numberFormat(row.nssf)"></td>
                                        <td class="p-3 text-right" x-text="numberFormat(row.lst)"></td>
                                        <td class="p-3 text-right">
                                            <input type="number"
                                                   x-model="row.leave_deduction"
                                                   x-on:input="recalcRow(row)"
                                                   class="w-24 text-right rounded p-1 text-sm"
                                                   style="border: 1px solid var(--d-border);">
                                        </td>
                                        <td class="p-3 text-right" x-text="numberFormat(row.item_earnings)"></td>
                                        <td class="p-3 text-right font-bold" style="color: var(--d-green);"
                                            x-text="numberFormat(row.net_pay)"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr style="background: var(--d-shell); border-top: 2px solid var(--d-border);">
                                    <td class="p-3 font-bold">Totals</td>
                                    <td class="p-3 text-right font-bold" x-text="numberFormat(totals.gross)"></td>
                                    <td class="p-3 text-right font-bold" x-text="numberFormat(totals.paye)"></td>
                                    <td class="p-3 text-right font-bold" x-text="numberFormat(totals.nssf)"></td>
                                    <td class="p-3 text-right font-bold" x-text="numberFormat(totals.lst)"></td>
                                    <td class="p-3 text-right font-bold" x-text="numberFormat(totalLeaveDed)"></td>
                                    <td class="p-3 text-right font-bold" x-text="numberFormat(totals.earnings)"></td>
                                    <td class="p-3 text-right font-bold" style="color: var(--d-green);"
                                        x-text="numberFormat(totals.net)"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Confirm panel (inline expanding) --}}
                <div class="bg-white rounded-xl p-6" style="border: 1px solid var(--d-border); box-shadow: var(--shadow-sm);"
                     x-data="{ showConfirm: false }">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-medium" style="color: var(--d-text);">
                                <span x-text="rows.length"></span> staff members ready for processing
                            </span>
                        </div>
                        <button x-on:click="showConfirm = !showConfirm"
                                class="px-6 py-2.5 rounded-lg text-white font-medium text-sm transition-all"
                                style="background: var(--d-green);">
                            <span x-text="showConfirm ? 'Cancel' : 'Run Payroll'"></span>
                        </button>
                    </div>

                    {{-- Inline expanding confirmation panel --}}
                    <div x-show="showConfirm" x-transition class="mt-4 p-4 rounded-lg" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
                        <p class="text-sm font-medium mb-3" style="color: #166534;">
                            This will create payroll records for <span x-text="rows.length"></span> staff.
                            Are you sure you want to proceed?
                        </p>
                        <div class="flex gap-3">
                            <button x-on:click="confirmRun()"
                                    x-bind:disabled="confirming"
                                    class="px-5 py-2 rounded-lg text-white text-sm font-medium"
                                    style="background: var(--d-green);">
                                <span x-text="confirming ? 'Processing...' : 'Yes, Run Payroll'"></span>
                            </button>
                            <button x-on:click="showConfirm = false"
                                    class="px-5 py-2 rounded-lg text-sm font-medium"
                                    style="background: var(--d-shell); color: var(--d-text); border: 1px solid var(--d-border);">
                                Cancel
                            </button>
                        </div>
                        <p x-show="confirmMessage" x-text="confirmMessage" class="mt-3 text-sm font-medium" style="color: var(--d-green);"></p>
                    </div>
                </div>
            </div>
        </template>

        {{-- Empty state --}}
        <template x-if="previewed && rows.length === 0">
            <div class="bg-white rounded-xl p-8 text-center" style="border: 1px solid var(--d-border);">
                <p class="text-lg font-medium" style="color: var(--d-muted);">No active salaries found for this template.</p>
                <p class="text-sm mt-1" style="color: var(--d-muted);">Add staff to the template first.</p>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
function batchPayroll() {
    return {
        templateId: '',
        startDate: '',
        endDate: '',
        loading: false,
        previewed: false,
        error: '',
        rows: [],
        totals: { gross: 0, paye: 0, nssf: 0, lst: 0, net: 0, earnings: 0 },
        confirming: false,
        confirmMessage: '',

        get canPreview() {
            return this.templateId !== '' && this.startDate !== '' && this.endDate !== '';
        },

        get totalLeaveDed() {
            return this.rows.reduce((sum, r) => sum + (parseFloat(r.leave_deduction) || 0), 0);
        },

        preview() {
            if (!this.canPreview) return;
            this.loading = true;
            this.error = '';
            this.previewed = false;

            fetch('{{ url("/accountant/payroll/batch/preview") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    template_id: this.templateId,
                    start_date: this.startDate,
                    end_date: this.endDate,
                }),
            })
            .then(r => r.json())
            .then(res => {
                this.loading = false;
                this.previewed = true;
                if (res.success) {
                    this.rows = res.rows;
                    this.totals = res.totals;
                } else {
                    this.error = res.message || 'Preview failed';
                }
            })
            .catch(e => {
                this.loading = false;
                this.previewed = true;
                this.error = 'Network error';
            });
        },

        recalcRow(row) {
            const gross = parseFloat(row.gross) || 0;
            const paye = parseFloat(row.paye) || 0;
            const nssf = parseFloat(row.nssf) || 0;
            const lst = parseFloat(row.lst) || 0;
            const leave = parseFloat(row.leave_deduction) || 0;
            const earnings = parseFloat(row.item_earnings) || 0;
            const deductions = parseFloat(row.item_deductions) || 0;
            row.net_pay = Math.max(0, gross + earnings - paye - nssf - lst - leave - deductions);

            // Recalc totals
            const totals = { gross: 0, paye: 0, nssf: 0, lst: 0, net: 0, earnings: 0 };
            this.rows.forEach(r => {
                totals.gross += parseFloat(r.gross) || 0;
                totals.paye += parseFloat(r.paye) || 0;
                totals.nssf += parseFloat(r.nssf) || 0;
                totals.lst += parseFloat(r.lst) || 0;
                totals.net += parseFloat(r.net_pay) || 0;
                totals.earnings += parseFloat(r.item_earnings) || 0;
            });
            this.totals = totals;
        },

        confirmRun() {
            this.confirming = true;
            this.confirmMessage = '';

            fetch('{{ url("/accountant/payroll/batch/confirm") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    template_id: this.templateId,
                    start_date: this.startDate,
                    end_date: this.endDate,
                    rows: this.rows.map(r => ({
                        staff_id: r.staff_id,
                        salary_id: r.salary_id,
                        gross: r.gross,
                        paye: r.paye,
                        nssf: r.nssf,
                        lst: r.lst,
                        leave_deduction: r.leave_deduction || 0,
                        net_pay: r.net_pay,
                    })),
                }),
            })
            .then(r => r.json())
            .then(res => {
                this.confirming = false;
                if (res.success) {
                    this.confirmMessage = res.message;
                } else {
                    this.confirmMessage = 'Error: ' + (res.message || 'Unknown');
                }
            })
            .catch(e => {
                this.confirming = false;
                this.confirmMessage = 'Network error';
            });
        },

        numberFormat(n) {
            return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },
    };
}
</script>
@endpush
