{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $payroll->user->school->name }} — Payslip</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap");

        :root {
            --d-blue: #1E6FD9;
            --d-green: #22C55E;
            --d-dark: #0F172A;
            --d-amber: #D97706;
            --d-border: #E2E8F0;
            --d-text: #1E293B;
            --d-muted: #64748B;
            --d-shell: #F8FAFC;
            --d-white: #FFFFFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            font-size: 12px;
            color: var(--d-text);
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .payslip {
            width: 210mm;
            max-width: 100%;
            margin: 0 auto;
            padding: 24px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--d-blue);
            margin-bottom: 20px;
        }

        .header .logo img {
            max-width: 120px;
            max-height: 60px;
        }

        .header .school-info {
            text-align: right;
        }

        .header .school-info h1 {
            font-family: 'Sora', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--d-dark);
        }

        .header .school-info p {
            font-size: 11px;
            color: var(--d-muted);
            margin-top: 2px;
        }

        .payslip-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .payslip-title h2 {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--d-dark);
        }

        .payslip-title p {
            font-size: 11px;
            color: var(--d-muted);
        }

        .employee-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid var(--d-border);
            border-radius: 8px;
            overflow: hidden;
        }

        .employee-info td {
            padding: 8px 12px;
            border: 1px solid var(--d-border);
            font-size: 12px;
        }

        .employee-info td:first-child {
            font-weight: 600;
            background: var(--d-shell);
            width: 140px;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .salary-table th {
            font-family: 'Sora', sans-serif;
            font-size: 13px;
            font-weight: 700;
            padding: 10px 12px;
            text-align: left;
            background: var(--d-dark);
            color: var(--d-white);
        }

        .salary-table th:last-child {
            text-align: right;
        }

        .salary-table td {
            padding: 7px 12px;
            border-bottom: 1px solid var(--d-border);
            font-size: 12px;
        }

        .salary-table td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .salary-table .total-row td {
            font-weight: 700;
            background: var(--d-shell);
            border-top: 2px solid var(--d-dark);
        }

        .salary-table .net-pay-row td {
            font-family: 'Sora', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: var(--d-green);
            background: #F0FDF4;
            border-top: 2px solid var(--d-green);
        }

        .statutory-row td:last-child {
            color: var(--d-amber);
        }

        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid var(--d-border);
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--d-muted);
        }

        .footer .signature-line {
            margin-top: 30px;
            width: 200px;
            border-top: 1px solid var(--d-dark);
            padding-top: 4px;
            font-size: 11px;
            color: var(--d-text);
        }

        @media print {
            body { background: white; }
            .payslip { padding: 12px; }
            .employee-info td:first-child { background: var(--d-shell) !important; }
            .salary-table th { background: var(--d-dark) !important; }
            .salary-table .net-pay-row td { background: #F0FDF4 !important; }
            @page { margin: 15mm; }
        }

        @media screen and (max-width: 600px) {
            .header { flex-direction: column; gap: 12px; }
            .header .school-info { text-align: left; }
            .employee-info, .salary-table { font-size: 11px; }
        }
    </style>
</head>
<body>
    <div class="payslip">
        {{-- Header: School info + logo --}}
        <div class="header">
            <div class="logo">
                @if(isset($logo) && $logo)
                    <img src="{{ $logo }}" alt="School Logo">
                @endif
            </div>
            <div class="school-info">
                <h1>{{ $payroll->user->school->name }}</h1>
                <p>{{ $payroll->user->school->address ?? '' }}, {{ $payroll->user->school->city->name ?? '' }}</p>
                <p>{{ $payroll->user->school->phone ?? '' }} | {{ $payroll->user->school->email ?? '' }}</p>
            </div>
        </div>

        {{-- Payslip title --}}
        <div class="payslip-title">
            <h2>Payslip — {{ $payroll->payrollno }}</h2>
            <p>{{ date('d F Y', strtotime($payroll->start_date)) }} — {{ date('d F Y', strtotime($payroll->end_date)) }}</p>
        </div>

        {{-- Employee info --}}
        <table class="employee-info">
            <tr>
                <td>Employee Name</td>
                <td>{{ $payroll->user->userprofile->firstname ?? '' }} {{ $payroll->user->userprofile->lastname ?? '' }}</td>
                <td>Employee ID</td>
                <td>{{ $payroll->user->teacherprofile[0]['employee_id'] ?? '—' }}</td>
            </tr>
            <tr>
                <td>Designation</td>
                <td>{{ ucfirst($payroll->user->teacherprofile[0]['designation'] ?? 'Staff') }}</td>
                <td>Contact</td>
                <td>{{ $payroll->user->mobile_no ?? '—' }}</td>
            </tr>
            <tr>
                <td>Pay Period</td>
                <td>{{ date('d M Y', strtotime($payroll->start_date)) }} — {{ date('d M Y', strtotime($payroll->end_date)) }}</td>
                <td>Paid Days</td>
                <td>{{ $payroll->getTotalDays() ?? 0 }} days</td>
            </tr>
        </table>

        {{-- Earnings & Deductions side by side --}}
        <table class="salary-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Earnings</th>
                    <th style="width: 50%;">Amount (UGX)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary ({{ $payroll->percentage ?? 100 }}%)</td>
                    <td>{{ number_format($payroll->salarypercentage()) }}</td>
                </tr>
                @php $earningsTotal = $payroll->salarypercentage(); @endphp
                @foreach($payroll->payslipitems as $item)
                    @if($item->salaryitem->templateitem->payrollitem->type === 'earning')
                    <tr>
                        <td>{{ $item->salaryitem->templateitem->payrollitem->name }}</td>
                        <td>{{ number_format(round((float) $item->amount)) }}</td>
                        @php $earningsTotal += round((float) $item->amount); @endphp
                    </tr>
                    @endif
                @endforeach
                <tr class="total-row">
                    <td>Total Earnings</td>
                    <td>{{ number_format($earningsTotal) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="salary-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Deductions</th>
                    <th style="width: 50%;">Amount (UGX)</th>
                </tr>
            </thead>
            <tbody>
                @php $deductionsTotal = 0; @endphp
                @foreach($payroll->payslipitems as $item)
                    @if($item->salaryitem->templateitem->payrollitem->type === 'deduction')
                    <tr>
                        <td>{{ $item->salaryitem->templateitem->payrollitem->name }}</td>
                        <td>{{ number_format(round((float) $item->amount)) }}</td>
                        @php $deductionsTotal += round((float) $item->amount); @endphp
                    </tr>
                    @endif
                @endforeach
                @if($payroll->leave_deduction > 0)
                <tr>
                    <td>Leave Deduction</td>
                    <td>{{ number_format($payroll->leave_deduction) }}</td>
                    @php $deductionsTotal += $payroll->leave_deduction; @endphp
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total Deductions</td>
                    <td>{{ number_format($deductionsTotal) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Net Pay --}}
        <table class="salary-table">
            <tr class="net-pay-row">
                <td style="width: 50%;">Net Pay</td>
                <td style="width: 50%;">UGX {{ number_format($payroll->totalsalary()) }}</td>
            </tr>
        </table>

        {{-- Footer --}}
        <div class="footer">
            <div>
                <p>Generated on: {{ date('d F Y H:i') }}</p>
                <p>KlassApp — {{ url('/') }}</p>
            </div>
            <div>
                <p>This is a computer-generated payslip.</p>
                <div class="signature-line">Authorised Signatory</div>
            </div>
        </div>
    </div>
</body>
</html>
