@extends('layouts.app')
@section('title', 'Salary Slip')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('salary-slips.index') }}" class="text-decoration-none">Salary Slips</a></li>
    <li class="breadcrumb-item active">View</li>
@endsection
@section('content')
<div class="card page-card mb-3">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('salary-slips.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left me-1" style="font-size:.8rem"></i>Back</a>
            <h5 class="mb-0 fw-semibold">Salary Slip — {{ $salarySlip->month_name }} {{ $salarySlip->year }}</h5>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa fa-print me-1"></i>Print</button>
            <a href="{{ route('salary-slips.pdf', $salarySlip) }}" class="btn btn-sm btn-danger" target="_blank"><i class="fa fa-file-pdf me-1"></i>PDF</a>
            <form action="{{ route('salary-slips.destroy', $salarySlip) }}" method="POST"
                  onsubmit="return confirm('Delete this salary slip for {{ addslashes($salarySlip->employee->full_name) }} ({{ $salarySlip->month_name }} {{ $salarySlip->year }})?\n\nAny associated loan repayments will be reversed.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash me-1"></i>Delete</button>
            </form>
        </div>
    </div>
</div>

<div class="card page-card" id="printArea">
    <div class="card-body p-5">

        {{-- Header --}}
        @php $showEntity = $salarySlip->employee->entity; @endphp
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div class="d-flex align-items-center gap-3">
                @if($showEntity && $showEntity->logo_base64)
                    <img src="{{ $showEntity->logo_base64 }}"
                         style="max-height:60px;max-width:80px;object-fit:contain;" alt="Logo">
                @endif
                <div>
                    <h2 class="fw-bold text-primary mb-0">{{ $showEntity?->name ?? 'HRMS' }}</h2>
                    @if(!$showEntity)
                    <div class="text-muted small">Human Resource Management System</div>
                    @endif
                </div>
            </div>
            <div class="text-end">
                <h5 class="fw-bold text-uppercase">Salary Slip</h5>
                <div class="text-muted small">{{ $salarySlip->month_name }} {{ $salarySlip->year }}</div>
            </div>
        </div>

        {{-- Employee Info --}}
        <div class="bg-light rounded p-3 mb-4">
            <div class="row g-2">
                <div class="col-md-4"><div class="text-muted small">Employee Name</div><div class="fw-semibold">{{ $salarySlip->employee->full_name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Employee Code</div><div class="fw-semibold">{{ $salarySlip->employee->employee_code }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Designation</div><div class="fw-semibold">{{ $salarySlip->employee->designation?->name ?? 'N/A' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Department</div><div class="fw-semibold">{{ $salarySlip->employee->department?->name ?? 'N/A' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Pay Period</div><div class="fw-semibold">{{ $salarySlip->month_name }} {{ $salarySlip->year }}</div></div>
                @if(!empty($salarySlip->attendance_summary))
                <div class="col-md-4">
                    <div class="text-muted small">Working Days</div>
                    <div class="fw-semibold">{{ $salarySlip->attendance_summary['total_working_days'] ?? '—' }}</div>
                </div>
                @endif

                {{-- Salary Revision row --}}
                @if($effectiveIncrement)
                <div class="col-12 mt-1">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded"
                         style="background:#fefce8;border:1px solid #fde047;">
                        <i class="fa fa-arrow-up text-warning"></i>
                        <span class="fw-semibold text-warning-emphasis" style="font-size:.88rem">Salary Revision Applied</span>
                        <span class="text-muted" style="font-size:.82rem">—</span>
                        <span style="font-size:.85rem">
                            w.e.f <strong>{{ $effectiveIncrement->effective_date->format('d M Y') }}</strong>:
                            <span class="text-secondary text-decoration-line-through">₹{{ number_format($effectiveIncrement->previous_salary, 2) }}</span>
                            <i class="fa fa-arrow-right mx-1 text-success" style="font-size:.75rem"></i>
                            <span class="text-success fw-semibold">₹{{ number_format($effectiveIncrement->new_salary, 2) }}</span>
                            @if($effectiveIncrement->increment_percentage)
                                <span class="badge bg-success ms-1" style="font-size:.72rem">+{{ number_format($effectiveIncrement->increment_percentage, 1) }}%</span>
                            @endif
                        </span>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @php
            $allRaw          = $salarySlip->allowances ?? [];
            $deductions      = $salarySlip->deductions ?? [];

            // Split into 3 buckets by marker prefix
            $allowances = [];
            $benefits   = [];
            $bonuses    = [];
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
        @endphp

        {{-- Earnings & Deductions --}}
        <div class="row g-4">
            <div class="col-md-6">
                <table class="table table-sm table-bordered">
                    <thead class="table-success">
                        <tr><th>Earnings (Salary)</th><th class="text-end">Amount (₹)</th></tr>
                    </thead>
                    <tbody>
                        @foreach($allowances as $name => $amount)
                        <tr>
                            <td>
                                {{ $name }}
                                @if(str_starts_with($name, 'Overtime'))
                                    <span class="badge bg-success ms-1" style="font-size:.65rem">OT</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr>
                            <td>Salary Sub-total</td>
                            <td class="text-end text-success">{{ number_format($allowanceTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

                @if(!empty($benefits))
                <table class="table table-sm table-bordered mt-3">
                    <thead style="background:#0dcaf0; color:#fff">
                        <tr><th>Benefit Funds</th><th class="text-end">Amount (₹)</th></tr>
                    </thead>
                    <tbody>
                        @foreach($benefits as $name => $amount)
                        <tr>
                            <td>{{ $name }} <span class="badge bg-info ms-1" style="font-size:.65rem">BENEFIT</span></td>
                            <td class="text-end">{{ number_format($amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr>
                            <td>Benefits Sub-total</td>
                            <td class="text-end text-info">{{ number_format($benefitsTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
                @endif

                @if(!empty($bonuses))
                <table class="table table-sm table-bordered mt-3">
                    <thead style="background:#ffc107; color:#000">
                        <tr><th>Bonuses & Incentives</th><th class="text-end">Amount (₹)</th></tr>
                    </thead>
                    <tbody>
                        @foreach($bonuses as $name => $amount)
                        <tr>
                            <td>{{ $name }} <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">BONUS</span></td>
                            <td class="text-end">{{ number_format($amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr>
                            <td>Bonus Sub-total</td>
                            <td class="text-end text-warning">{{ number_format($bonusesTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
                @endif

                @if($additionalTotal > 0)
                <div class="alert alert-info mt-2 py-2 mb-0 small d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-plus-circle me-1"></i>Total Additional Earnings (Benefits + Bonuses)</span>
                    <strong>₹{{ number_format($additionalTotal, 2) }}</strong>
                </div>
                @endif

                <div class="alert alert-success mt-2 py-2 mb-0 small d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-coins me-1"></i><strong>Total Earnings (Gross)</strong></span>
                    <strong>₹{{ number_format($totalEarnings, 2) }}</strong>
                </div>
            </div>

            <div class="col-md-6">
                <table class="table table-sm table-bordered">
                    <thead class="table-danger">
                        <tr><th>Deductions</th><th class="text-end">Amount (₹)</th></tr>
                    </thead>
                    <tbody>
                        @forelse($deductions as $name => $amount)
                        <tr>
                            <td>
                                {{ $name }}
                                @if(in_array($name, ['PF','ESI']))
                                    <span class="badge bg-secondary ms-1" style="font-size:.65rem">STAT</span>
                                @elseif(str_contains($name, 'Late'))
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">LATE</span>
                                @elseif(str_contains($name, 'Absent'))
                                    <span class="badge bg-danger ms-1" style="font-size:.65rem">ABS</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-muted">No deductions</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr>
                            <td>Total Deductions</td>
                            <td class="text-end text-danger">{{ number_format($totalDeductions, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Net Salary --}}
        <div class="bg-primary text-white rounded p-3 d-flex justify-content-between align-items-center mt-2">
            <div class="fw-semibold fs-5">Net Salary (Take Home)</div>
            <div class="fw-bold fs-4">₹{{ number_format($salarySlip->net_salary, 2) }}</div>
        </div>

        {{-- Attendance Summary --}}
        @if(!empty($salarySlip->attendance_summary))
        @php $att = $salarySlip->attendance_summary; @endphp
        <div class="card mt-4 border-0 bg-light">
            <div class="card-header bg-transparent border-0 fw-semibold text-muted pb-1">
                <i class="fa fa-calendar-check me-2"></i>Attendance Summary — {{ $salarySlip->month_name }} {{ $salarySlip->year }}
            </div>
            <div class="card-body pt-2">
                <div class="row text-center g-3">
                    <div class="col-6 col-md-2">
                        <div class="text-muted small">Working Days</div>
                        <div class="fw-bold fs-5">{{ $att['total_working_days'] }}</div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="text-muted small">Present</div>
                        <div class="fw-bold fs-5 text-success">{{ $att['present_days'] }}</div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="text-muted small">Half Day</div>
                        <div class="fw-bold fs-5 text-warning">{{ $att['half_days'] }}</div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="text-muted small">Paid Leave</div>
                        <div class="fw-bold fs-5 text-info">{{ $att['paid_leave_days'] ?? $att['leave_days'] }}</div>
                        @if(($att['approved_leave_days'] ?? 0) > 0)
                        <div class="text-muted" style="font-size:.68rem">{{ $att['approved_leave_days'] }} approved</div>
                        @endif
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="text-muted small">Absent (deducted)</div>
                        <div class="fw-bold fs-5 text-danger">{{ $att['absent_days'] }}</div>
                        <div class="text-muted" style="font-size:.68rem">unaccounted working days</div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="text-muted small">Late Days</div>
                        <div class="fw-bold fs-5 {{ ($att['late_days'] ?? 0) > 0 ? 'text-warning' : 'text-muted' }}">
                            {{ $att['late_days'] ?? 0 }}
                        </div>
                    </div>
                </div>
                {{-- Leave breakdown --}}
                @if(($att['paid_leave_days'] ?? 0) > 0 || ($att['unpaid_leave_days'] ?? 0) > 0)
                <div class="mt-2 d-flex gap-3 flex-wrap small">
                    @if(($att['approved_leave_days'] ?? 0) > 0)
                        <span class="badge bg-info text-dark">✓ {{ $att['approved_leave_days'] }} day(s) approved leave (paid)</span>
                    @endif
                    @if(($att['paid_leave_days'] ?? 0) - ($att['approved_leave_days'] ?? 0) > 0)
                        <span class="badge bg-secondary">+ {{ ($att['paid_leave_days'] - ($att['approved_leave_days'] ?? 0)) }} free monthly quota</span>
                    @endif
                    @if(($att['unpaid_leave_days'] ?? 0) > 0)
                        <span class="badge bg-danger">{{ $att['unpaid_leave_days'] }} unpaid leave (counted as absent)</span>
                    @endif
                </div>
                @endif

                @if(($att['ot_hours'] ?? 0) > 0 || ($att['late_minutes'] ?? 0) > 0)
                <hr class="my-3">
                <div class="row text-center g-3">
                    @if(($att['ot_hours'] ?? 0) > 0)
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">OT Hours</div>
                        <div class="fw-bold text-success">{{ \App\Helpers\AppSettings::fmtOtHours((float)($att['ot_hours'] ?? 0)) }}</div>
                        <small class="text-muted">@ 2× hourly rate</small>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">OT Pay Added</div>
                        <div class="fw-bold text-success">₹{{ number_format($att['ot_amount'], 2) }}</div>
                    </div>
                    @endif
                    @if(($att['late_minutes'] ?? 0) > 0)
                    @php
                        $lateGrace       = $att['late_grace_minutes']        ?? 90;
                        $lateMins        = $att['late_minutes']              ?? 0;
                        $lateDeductable  = $att['deductable_late_mins']      ?? 0;
                        $lateDeduction   = $att['late_deduction']            ?? 0;
                        $remainingPerm   = $att['remaining_late_permission'] ?? max(0, $lateGrace - $lateMins);
                        $exceeded        = $lateMins > $lateGrace;
                        $fmtMin = fn(int $m) => ($m >= 60 ? intdiv($m,60).'h ' : '') . ($m % 60) . 'm';
                    @endphp
                    <div class="col-12 col-md-6">
                        <div class="alert {{ $exceeded ? 'alert-danger' : 'alert-warning' }} py-2 mb-0 small">
                            <div class="fw-semibold mb-1"><i class="fa fa-clock me-1"></i>Late Arrival Breakdown</div>
                            <div class="d-flex flex-wrap gap-3">
                                <span>Total late: <strong>{{ $fmtMin($lateMins) }}</strong>
                                    <span class="text-muted">({{ $att['late_days'] ?? 0 }} day(s))</span></span>
                                <span>Monthly grace: <strong>{{ $fmtMin($lateGrace) }}</strong></span>
                                @if($exceeded)
                                    <span class="text-danger fw-semibold">Exceeded! Deducting {{ $fmtMin($lateDeductable) }} (2×)</span>
                                    <span class="text-danger">₹{{ number_format($lateDeduction, 2) }} deducted</span>
                                @else
                                    <span class="text-success">Remaining grace: <strong>{{ $fmtMin($remainingPerm) }}</strong></span>
                                    <span class="text-success"><i class="fa fa-check-circle me-1"></i>No deduction</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                <div class="mt-3 small text-muted d-flex gap-4 flex-wrap">
                    <span>Working Days: <strong>{{ $att['total_working_days'] ?? '—' }}</strong>
                        <span class="text-muted" style="font-size:.72rem">(actual working)</span>
                    </span>
                    <span>Calendar Days: <strong>{{ $att['calendar_days'] ?? '—' }}</strong>
                        <span class="text-muted" style="font-size:.72rem">(month total)</span>
                    </span>
                    <span>Per Day Rate: <strong>₹{{ number_format($att['per_day_salary'], 2) }}</strong>
                        <span class="text-muted" style="font-size:.72rem">
                            (₹{{ number_format($att['ctc_per_month'] ?? 0, 0) }} ÷ {{ $att['calendar_days'] ?? 30 }} days)
                        </span>
                    </span>
                    @if(isset($att['per_hour_rate']))
                    <span>Per Hour Rate: <strong>₹{{ number_format($att['per_hour_rate'], 2) }}</strong>
                        <span class="text-muted" style="font-size:.72rem">(per day ÷ 8 hrs)</span>
                    </span>
                    @endif
                    @if(isset($att['absent_days']) && $att['absent_days'] > 0)
                    <span class="text-danger">Absent Deduction: <strong>₹{{ number_format($att['absent_days'] * $att['per_day_salary'], 2) }}</strong>
                        <span style="font-size:.75rem">({{ $att['absent_days'] }} days × ₹{{ number_format($att['per_day_salary'], 2) }})</span>
                    </span>
                    @endif
                    @if(isset($att['basic_salary']))
                    <span>Basic Salary: <strong>₹{{ number_format($att['basic_salary'], 2) }}</strong></span>
                    @endif
                    @if(isset($att['ctc_per_month']))
                    <span>CTC/Month: <strong>₹{{ number_format($att['ctc_per_month'], 2) }}</strong></span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <p class="text-muted small mt-4 mb-0">
            <i class="fa fa-info-circle me-1"></i>This is a computer-generated salary slip and does not require a physical signature.
        </p>
    </div>
</div>
@endsection
@push('styles')
<style>
@media print {
    #topbar, #sidebar, .page-card:first-child, footer, .btn { display: none !important; }
    #main-content { margin-left: 0 !important; }
    #printArea { box-shadow: none !important; }
}
</style>
@endpush
