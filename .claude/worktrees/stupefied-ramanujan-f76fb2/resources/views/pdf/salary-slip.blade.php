<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9.5pt; color: #1e293b; background: #fff; }
    .page { padding: 22px 32px; }

    /* Header */
    .header { display: flex; justify-content: space-between; align-items: flex-start;
               border-bottom: 3px solid #2563eb; padding-bottom: 10px; margin-bottom: 12px; }
    .company-name { font-size: 20pt; font-weight: bold; color: #2563eb; line-height: 1; }
    .company-sub  { font-size: 7.5pt; color: #64748b; margin-top: 2px; }
    .slip-title   { font-size: 13pt; font-weight: bold; text-align: right; }
    .slip-period  { font-size: 8.5pt; color: #64748b; text-align: right; margin-top: 2px; }

    /* Employee info box */
    .emp-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 5px;
               padding: 8px 12px; margin-bottom: 10px; }
    .emp-grid { display: flex; flex-wrap: wrap; }
    .emp-cell { width: 33.33%; margin-bottom: 5px; }
    .lbl { font-size: 7.5pt; color: #64748b; font-weight: bold; text-transform: uppercase; letter-spacing: .4px; }
    .val { font-size: 9pt; font-weight: bold; margin-top: 1px; }

    /* Earnings / Deductions table */
    .dual-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .dual-table td, .dual-table th { padding: 4px 8px; border: 1px solid #e2e8f0; font-size: 8.5pt; vertical-align: top; }
    .th-earn { background: #166534; color: #fff; font-weight: bold; }
    .th-ded  { background: #991b1b; color: #fff; font-weight: bold; }
    .th-earn.right, .th-ded.right { text-align: right; }
    .foot-earn { background: #dcfce7; font-weight: bold; }
    .foot-ded  { background: #fee2e2; font-weight: bold; }
    .right { text-align: right; }
    .badge-sm { font-size: 6.5pt; padding: 1px 4px; border-radius: 3px; display: inline-block; }
    .bg-stat { background:#94a3b8; color:#fff; }
    .bg-late { background:#d97706; color:#fff; }
    .bg-abs  { background:#dc2626; color:#fff; }
    .bg-ot   { background:#16a34a; color:#fff; }
    .row-ot  { background: #f0fdf4; }
    .row-late{ background: #fffbeb; }
    .row-abs { background: #fff5f5; }

    /* Net box */
    .net-box { background: #1e3a5f; color: #fff; padding: 8px 14px; border-radius: 5px;
               display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .net-label  { font-size: 11pt; font-weight: bold; }
    .net-amount { font-size: 14pt; font-weight: bold; }

    /* Attendance summary — single compact line */
    .att-box   { border: 1px solid #e2e8f0; border-radius: 5px; padding: 7px 12px; margin-bottom: 10px; }
    .att-title { font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 5px;
                 padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }
    .att-row   { width: 100%; border-collapse: collapse; }
    .att-row td { text-align: center; padding: 3px 4px; border-right: 1px solid #e2e8f0; }
    .att-row td:last-child { border-right: none; }
    .att-val   { font-size: 10pt; font-weight: bold; line-height: 1.2; }
    .att-lbl   { font-size: 6.5pt; color: #64748b; }
    .c-green   { color: #16a34a; }
    .c-yellow  { color: #d97706; }
    .c-red     { color: #dc2626; }
    .c-gray    { color: #64748b; }
    .c-blue    { color: #2563eb; }

    /* Rates row */
    .rates { font-size: 7pt; color: #64748b; margin-top: 5px; border-top: 1px solid #e2e8f0;
             padding-top: 4px; }

    /* Footer */
    .footer { margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 6px;
              font-size: 7pt; color: #94a3b8; text-align: center; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="company-name">HRMS</div>
            <div class="company-sub">Human Resource Management System</div>
            <div class="company-sub">123 Business Park, Tech City, India – 400001</div>
        </div>
        <div>
            <div class="slip-title">Salary Slip</div>
            <div class="slip-period">{{ $salarySlip->month_name }} {{ $salarySlip->year }}</div>
        </div>
    </div>

    {{-- Employee Info --}}
    <div class="emp-box">
        <div class="emp-grid">
            <div class="emp-cell"><div class="lbl">Employee Name</div><div class="val">{{ $salarySlip->employee->full_name }}</div></div>
            <div class="emp-cell"><div class="lbl">Employee Code</div><div class="val">{{ $salarySlip->employee->employee_code }}</div></div>
            <div class="emp-cell"><div class="lbl">Designation</div><div class="val">{{ $salarySlip->employee->designation?->name ?? 'N/A' }}</div></div>
            <div class="emp-cell"><div class="lbl">Department</div><div class="val">{{ $salarySlip->employee->department?->name ?? 'N/A' }}</div></div>
            <div class="emp-cell"><div class="lbl">Pay Period</div><div class="val">{{ $salarySlip->month_name }} {{ $salarySlip->year }}</div></div>
            @if(!empty($salarySlip->attendance_summary))
            <div class="emp-cell"><div class="lbl">Working Days</div><div class="val">{{ $salarySlip->attendance_summary['total_working_days'] ?? '—' }}</div></div>
            @endif
        </div>
    </div>

    {{-- Earnings & Deductions side by side --}}
    @php
        $allRaw          = $salarySlip->allowances ?? [];
        $deductions      = $salarySlip->deductions ?? [];

        $allowances = []; $benefits = []; $bonuses = [];
        foreach ($allRaw as $name => $amt) {
            if (str_starts_with($name, '[BENEFIT] ')) {
                $benefits[substr($name, 10)] = $amt;
            } elseif (str_starts_with($name, '[BONUS] ')) {
                $bonuses[substr($name, 8)]   = $amt;
            } else {
                $allowances[$name] = $amt;
            }
        }
        $allowanceTotal  = array_sum($allowances);
        $benefitsTotal   = array_sum($benefits);
        $bonusesTotal    = array_sum($bonuses);
        $totalEarnings   = $allowanceTotal + $benefitsTotal + $bonusesTotal;
        $additionalTotal = $benefitsTotal + $bonusesTotal;
        $totalDeductions = array_sum($deductions);

        // Build parallel arrays for the dual-column table
        $earnsRows = [];
        foreach ($allowances as $n => $a) {
            $earnsRows[] = [$n, $a, str_starts_with($n, 'Overtime') ? 'ot' : ''];
        }
        foreach ($benefits as $n => $a) { $earnsRows[] = [$n, $a, 'benefit']; }
        foreach ($bonuses  as $n => $a) { $earnsRows[] = [$n, $a, 'bonus'];   }

        $dedsRows = [];
        foreach ($deductions as $n => $a) {
            $type = in_array($n, ['PF','ESI']) ? 'stat'
                 : (str_contains($n,'Late') ? 'late'
                 : (str_contains($n,'Absent') ? 'abs' : ''));
            $dedsRows[] = [$n, $a, $type];
        }
        $maxRows = max(count($earnsRows), count($dedsRows));
    @endphp

    <table class="dual-table">
        <thead>
            <tr>
                <th class="th-earn" style="width:34%">Earnings</th>
                <th class="th-earn right" style="width:16%">Amount (₹)</th>
                <th class="th-ded"  style="width:34%">Deductions</th>
                <th class="th-ded right" style="width:16%">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < $maxRows; $i++)
            @php
                $e = $earnsRows[$i] ?? ['—', null, ''];
                $d = $dedsRows[$i]  ?? ['—', null, ''];
            @endphp
            <tr class="{{ $e[2] === 'ot' ? 'row-ot' : ($d[2] === 'late' ? 'row-late' : ($d[2] === 'abs' ? 'row-abs' : '')) }}">
                <td>
                    {{ $e[0] }}
                    @if($e[2] === 'ot')<span class="badge-sm bg-ot">OT</span>
                    @elseif($e[2] === 'benefit')<span class="badge-sm" style="background:#0dcaf0;color:#fff">BENEFIT</span>
                    @elseif($e[2] === 'bonus')<span class="badge-sm" style="background:#ffc107;color:#000">BONUS</span>
                    @endif
                </td>
                <td class="right">{{ $e[1] !== null ? number_format($e[1], 2) : '' }}</td>
                <td>
                    {{ $d[0] }}
                    @if($d[2] === 'stat')<span class="badge-sm bg-stat">STAT</span>
                    @elseif($d[2] === 'late')<span class="badge-sm bg-late">LATE</span>
                    @elseif($d[2] === 'abs')<span class="badge-sm bg-abs">ABS</span>
                    @endif
                </td>
                <td class="right">{{ $d[1] !== null ? number_format($d[1], 2) : '' }}</td>
            </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td class="foot-earn">Total Earnings</td>
                <td class="foot-earn right" style="color:#166534">{{ number_format($totalEarnings, 2) }}</td>
                <td class="foot-ded">Total Deductions</td>
                <td class="foot-ded right" style="color:#991b1b">{{ number_format($totalDeductions, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($additionalTotal > 0)
    {{-- Additional earnings summary --}}
    <table class="dual-table" style="margin-bottom:8px">
        <tr>
            <td style="width:50%; background:#f0fdf4; padding:6px 10px; font-weight:bold; border:1px solid #bbf7d0">
                Salary Sub-total
            </td>
            <td style="width:25%; background:#f0fdf4; text-align:right; padding:6px 10px; border:1px solid #bbf7d0">
                &#8377; {{ number_format($allowanceTotal, 2) }}
            </td>
            <td rowspan="3" style="width:25%; background:#1e3a5f; color:#fff; text-align:center; padding:8px; vertical-align:middle">
                <div style="font-size:7pt">Total Additional Earnings</div>
                <div style="font-size:13pt; font-weight:bold; margin-top:4px">&#8377; {{ number_format($additionalTotal, 2) }}</div>
                <div style="font-size:6.5pt; margin-top:3px">Benefits + Bonuses</div>
            </td>
        </tr>
        @if($benefitsTotal > 0)
        <tr>
            <td style="background:#cffafe; padding:6px 10px; font-weight:bold; border:1px solid #a5f3fc">Benefit Funds Total</td>
            <td style="background:#cffafe; text-align:right; padding:6px 10px; border:1px solid #a5f3fc">&#8377; {{ number_format($benefitsTotal, 2) }}</td>
        </tr>
        @endif
        @if($bonusesTotal > 0)
        <tr>
            <td style="background:#fef3c7; padding:6px 10px; font-weight:bold; border:1px solid #fde68a">Bonuses & Incentives Total</td>
            <td style="background:#fef3c7; text-align:right; padding:6px 10px; border:1px solid #fde68a">&#8377; {{ number_format($bonusesTotal, 2) }}</td>
        </tr>
        @endif
    </table>
    @endif

    {{-- Net Salary --}}
    <div class="net-box">
        <div class="net-label">Final Net Pay (Take Home)</div>
        <div class="net-amount">&#8377; {{ number_format($salarySlip->net_salary, 2) }}</div>
    </div>

    {{-- Attendance Summary — single compact line --}}
    @if(!empty($salarySlip->attendance_summary))
    @php $att = $salarySlip->attendance_summary; @endphp
    <div class="att-box">
        <div class="att-title">Attendance Summary &mdash; {{ $salarySlip->month_name }} {{ $salarySlip->year }}</div>
        <table class="att-row">
            <tr>
                <td><div class="att-val c-blue">{{ $att['total_working_days'] }}</div><div class="att-lbl">Working Days</div></td>
                <td><div class="att-val c-green">{{ $att['present_days'] }}</div><div class="att-lbl">Present</div></td>
                <td><div class="att-val c-yellow">{{ $att['half_days'] }}</div><div class="att-lbl">Half Day</div></td>
                <td><div class="att-val c-gray">{{ $att['leave_days'] }}</div><div class="att-lbl">On Leave</div></td>
                <td><div class="att-val c-red">{{ $att['absent_days'] }}</div><div class="att-lbl">Absent</div></td>
                <td><div class="att-val c-yellow">{{ $att['late_days'] ?? 0 }}</div><div class="att-lbl">Late Days</div></td>
                @if(($att['ot_hours'] ?? 0) > 0)
                <td><div class="att-val c-green">{{ \App\Helpers\AppSettings::fmtOtHours((float)($att['ot_hours'] ?? 0)) }}</div><div class="att-lbl">OT Hours (2&times;)</div></td>
                <td><div class="att-val c-green">&#8377;&nbsp;{{ number_format($att['ot_amount'] ?? 0, 2) }}</div><div class="att-lbl">OT Pay</div></td>
                @endif
                @if(($att['late_minutes'] ?? 0) > 0)
                <td><div class="att-val c-yellow">{{ $att['late_minutes'] }}&nbsp;min</div><div class="att-lbl">Late Mins</div></td>
                <td><div class="att-val c-red">&#8377;&nbsp;{{ number_format($att['late_deduction'] ?? 0, 2) }}</div><div class="att-lbl">Late Deduction</div></td>
                @endif
            </tr>
        </table>
        <div class="rates">
            @if(isset($att['ctc_per_month']))<span>CTC/Month: <strong>&#8377; {{ number_format($att['ctc_per_month'], 2) }}</strong></span>&nbsp;&nbsp;@endif
            @if(isset($att['basic_salary']))<span>Basic: <strong>&#8377; {{ number_format($att['basic_salary'], 2) }}</strong></span>&nbsp;&nbsp;@endif
            <span>LOP/Day: <strong>&#8377; {{ number_format($att['per_day_salary'] ?? 0, 2) }}</strong></span>&nbsp;&nbsp;
            @if(isset($att['per_hour_rate']))<span>OT Rate (2&times;): <strong>&#8377; {{ number_format(($att['per_hour_rate'] ?? 0) * 2, 2) }}</strong></span>@endif
        </div>
    </div>
    @endif

    <div class="footer">
        This is a computer-generated salary slip and does not require a physical signature.
        &bull; Generated by HRMS &bull; {{ now()->format('d M Y H:i') }}
    </div>
</div>
</body>
</html>
