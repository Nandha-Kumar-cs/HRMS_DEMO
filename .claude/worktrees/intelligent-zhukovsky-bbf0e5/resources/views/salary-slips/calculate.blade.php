@extends('layouts.app')
@section('title','Salary Calculation — ' . date('F', mktime(0,0,0,$month,1)) . ' ' . $year)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('salary-slips.index') }}" class="text-decoration-none">Salary Slips</a></li>
<li class="breadcrumb-item active">Salary Calculation</li>
@endsection

@section('content')

{{-- Month / Year Picker --}}
<form method="GET" class="row g-2 mb-4 align-items-end">
    <div class="col-auto">
        <label class="form-label fw-semibold mb-1">Month</label>
        <select name="month" class="form-select">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
            @endfor
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label fw-semibold mb-1">Year</label>
        <select name="year" class="form-select">
            @for($y = now()->year; $y >= now()->year - 4; $y--)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary"><i class="fa fa-calculator me-1"></i>Calculate</button>
    </div>
    <div class="col-auto ms-auto">
        <a href="{{ route('salary-slips.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-list me-1"></i>All Slips
        </a>
    </div>
</form>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">Employees</div>
                <div class="fw-bold fs-4 text-primary">{{ $rows->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">Total Gross</div>
                <div class="fw-bold fs-5 text-success">₹{{ number_format($totals['gross'], 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">Total Deductions</div>
                <div class="fw-bold fs-5 text-danger">₹{{ number_format($totals['deductions'], 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">Total Net Pay</div>
                <div class="fw-bold fs-5 text-primary">₹{{ number_format($totals['net'], 0) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Sub-totals for statutory --}}
<div class="d-flex gap-3 mb-3 flex-wrap align-items-center">
    <span class="badge bg-secondary fs-6 py-2 px-3">
        <i class="fa fa-building me-1"></i>PF Liability: ₹{{ number_format($totals['pf'], 2) }}
    </span>
    <span class="badge bg-secondary fs-6 py-2 px-3">
        <i class="fa fa-hospital me-1"></i>ESI Liability: ₹{{ number_format($totals['esi'], 2) }}
    </span>
    <span class="text-muted small ms-auto">
        Working Days in {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}: <strong>{{ $totalWorkingDays }}</strong>
        &nbsp;|&nbsp;
        Calendar Days: <strong>{{ \Carbon\Carbon::create($year, $month, 1)->daysInMonth }}</strong>
        <span class="text-muted" style="font-size:.75rem">(per-day rate denominator)</span>
    </span>
</div>

{{-- Payroll Register Table --}}
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">
            <i class="fa fa-calculator me-2 text-primary"></i>Payroll Register — {{ $startDate->format('F Y') }}
        </h5>
        <small class="text-muted">{{ $rows->count() }} active employee(s)</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" style="font-size:.83rem">
                <thead>
                    <tr>
                        <th rowspan="2" style="min-width:160px;vertical-align:middle;background:#1e293b;color:#fff">#&nbsp;&nbsp;Employee</th>
                        <th colspan="6" class="text-center" style="background:#1e4060;color:#fff;letter-spacing:.05em">ATTENDANCE</th>
                        <th colspan="2" class="text-center" style="background:#1a4d1a;color:#fff;letter-spacing:.05em">EARNINGS</th>
                        <th colspan="3" class="text-center" style="background:#5c1a1a;color:#fff;letter-spacing:.05em">DEDUCTIONS</th>
                        <th rowspan="2" class="text-center" style="min-width:110px;vertical-align:middle;background:#10375c;color:#fff">NET PAY</th>
                        <th rowspan="2" class="text-center" style="min-width:100px;vertical-align:middle;background:#1e293b;color:#fff">PAYSLIP</th>
                    </tr>
                    <tr style="font-size:.75rem">
                        <th class="text-center" style="background:#1e4060;color:#e2e8f0">P</th>
                        <th class="text-center" style="background:#1e4060;color:#e2e8f0">H</th>
                        <th class="text-center" style="background:#1e4060;color:#e2e8f0">A</th>
                        <th class="text-center" style="background:#1e4060;color:#e2e8f0">L</th>
                        <th class="text-center" style="background:#1e4060;color:#e2e8f0">Late</th>
                        <th class="text-center" style="background:#1e4060;color:#e2e8f0">OT Hrs</th>
                        <th class="text-end" style="background:#1a4d1a;color:#bbf7d0">Gross Earnings</th>
                        <th class="text-end" style="background:#1a4d1a;color:#bbf7d0">OT ₹</th>
                        <th class="text-end" style="background:#5c1a1a;color:#fca5a5">PF+ESI</th>
                        <th class="text-end" style="background:#5c1a1a;color:#fca5a5">Absent+Late</th>
                        <th class="text-end" style="background:#5c1a1a;color:#fca5a5">Other</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $row)
                    @php
                        $emp      = $row['employee'];
                        $slip     = $row['slip'];
                        $pfEsi    = ($row['deductions']['PF'] ?? 0) + ($row['deductions']['ESI'] ?? 0);
                        $attDed   = $row['absent_deduction'] + $row['late_deduction'];
                        $otherDed = $row['total_deductions'] - $pfEsi - $attDed;
                        $noSalary = $emp->fixed_salary == 0 && $emp->variable_salary == 0;

                        // Guard: block payslip for months before the employee's joining date
                        $beforeJoining = false;
                        if ($emp->joining_date) {
                            $joinYear  = (int) $emp->joining_date->format('Y');
                            $joinMonth = (int) $emp->joining_date->format('n');
                            $beforeJoining = ($year < $joinYear) || ($year === $joinYear && $month < $joinMonth);
                        }
                    @endphp
                    <tr class="{{ $beforeJoining ? 'table-secondary opacity-75' : ($noSalary ? 'table-warning' : '') }}">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small">{{ $i+1 }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $emp->full_name }}</div>
                                    <small class="text-muted">{{ $emp->employee_code }}</small>
                                    @if($emp->department)
                                        <span class="badge bg-light text-dark ms-1" style="font-size:.65rem">{{ $emp->department->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        {{-- Attendance --}}
                        <td class="text-center text-success fw-semibold">{{ $row['present_days'] }}</td>
                        <td class="text-center text-warning fw-semibold">{{ $row['half_days'] }}</td>
                        <td class="text-center text-danger fw-semibold">{{ number_format($row['absent_days'], 1) }}</td>
                        <td class="text-center text-secondary">{{ $row['leave_days'] }}</td>
                        <td class="text-center {{ $row['late_minutes'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}" style="font-size:.8rem">
                            @php
                                $lm  = $row['late_minutes'];
                                $rem = $row['remaining_late_permission'] ?? max(0, 90 - $lm);
                                $fmt = fn($m) => ($m >= 60 ? intdiv($m, 60).'h ' : '') . ($m % 60) . 'm';
                            @endphp
                            @if($lm > 0)
                                {{ $fmt($lm) }}
                                @if($row['late_deduction'] > 0)
                                    <br><small class="text-danger" style="font-size:.65rem">2× ded.</small>
                                @else
                                    <br><small class="text-success" style="font-size:.65rem">{{ $fmt($rem) }} left</small>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-center {{ $row['ot_hours'] > 0 ? 'text-success fw-semibold' : 'text-muted' }}">
                            {{ $row['ot_hours'] > 0 ? $row['ot_hours'].'h' : '—' }}
                        </td>
                        {{-- Earnings --}}
                        @if($beforeJoining)
                        <td colspan="5" class="text-center text-muted fst-italic small">—</td>
                        @elseif($noSalary)
                        <td colspan="5" class="text-center text-muted fst-italic small">Salary not configured</td>
                        @else
                        <td class="text-end">{{ number_format($row['gross_salary'], 0) }}</td>
                        <td class="text-end {{ $row['ot_amount'] > 0 ? 'text-success' : 'text-muted' }}">
                            {{ $row['ot_amount'] > 0 ? number_format($row['ot_amount'], 0) : '—' }}
                        </td>
                        {{-- Deductions --}}
                        <td class="text-end text-danger">{{ number_format($pfEsi, 0) }}</td>
                        <td class="text-end {{ $attDed > 0 ? 'text-warning' : 'text-muted' }}">
                            {{ $attDed > 0 ? number_format($attDed, 0) : '—' }}
                        </td>
                        <td class="text-end text-muted">{{ $otherDed > 0 ? number_format($otherDed, 0) : '—' }}</td>
                        @endif
                        {{-- Net Pay --}}
                        <td class="text-center fw-bold {{ $beforeJoining ? 'text-muted' : ($noSalary ? 'text-muted' : 'text-primary') }} fs-6">
                            @if($beforeJoining) — @elseif(!$noSalary) ₹{{ number_format($row['net_salary'], 0) }} @else — @endif
                        </td>
                        {{-- Payslip Action --}}
                        <td class="text-center">
                            @if($beforeJoining)
                                {{-- Month is before joining — block generation entirely --}}
                                <span class="badge bg-secondary text-white" style="font-size:.72rem"
                                      title="Joined {{ $emp->joining_date->format('d M Y') }}">
                                    <i class="fa fa-ban me-1"></i>Before Joining
                                </span>
                            @elseif($noSalary)
                                <span class="badge bg-warning text-dark">No Salary</span>
                            @elseif($slip)
                                <a href="{{ route('salary-slips.show', $slip) }}" class="btn btn-sm btn-outline-info" title="View Payslip">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ route('salary-slips.pdf', $slip) }}" class="btn btn-sm btn-outline-danger ms-1" target="_blank" title="Download PDF">
                                    <i class="fa fa-file-pdf"></i>
                                </a>
                                <form action="{{ route('salary-slips.store') }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Regenerate payslip for {{ addslashes($emp->full_name) }} ({{ date('F', mktime(0,0,0,$month,1)) }} {{ $year }})?\n\nThis will recalculate salary using current attendance data. Loan deductions will NOT be re-processed.')">
                                    @csrf
                                    <input type="hidden" name="employee_id"      value="{{ $emp->id }}">
                                    <input type="hidden" name="month"            value="{{ $month }}">
                                    <input type="hidden" name="year"             value="{{ $year }}">
                                    <input type="hidden" name="force_regenerate" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-warning ms-1" title="Regenerate Payslip">
                                        <i class="fa fa-rotate"></i>
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('salary-slips.store') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <button type="submit" class="btn btn-sm btn-success" title="Generate Payslip">
                                        <i class="fa fa-file-invoice-dollar me-1"></i>Generate
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="15" class="text-center text-muted py-4">No active employees found.</td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                <tfoot class="table-dark">
                    <tr style="font-size:.83rem">
                        <td colspan="7" class="fw-semibold">Totals ({{ $rows->count() }} employees)</td>
                        <td class="text-end fw-bold text-success">₹{{ number_format($totals['gross'], 0) }}</td>
                        <td class="text-end {{ $totals['ot_amount'] > 0 ? 'text-success' : 'text-muted' }}">
                            {{ $totals['ot_amount'] > 0 ? '₹'.number_format($totals['ot_amount'], 0) : '—' }}
                        </td>
                        <td class="text-end fw-bold text-danger">₹{{ number_format($totals['pf'] + $totals['esi'], 0) }}</td>
                        <td class="text-end {{ $totals['absent_late'] > 0 ? 'text-warning fw-bold' : 'text-muted' }}">
                            {{ $totals['absent_late'] > 0 ? '₹'.number_format($totals['absent_late'], 0) : '—' }}
                        </td>
                        <td class="text-end text-muted">
                            {{ $totals['other_ded'] > 0 ? '₹'.number_format($totals['other_ded'], 0) : '—' }}
                        </td>
                        <td class="text-center fw-bold text-warning">₹{{ number_format($totals['net'], 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Legend --}}
<div class="d-flex gap-3 mt-3 flex-wrap small text-muted">
    <span><strong>P</strong> = Present (incl. Late)</span>
    <span><strong>H</strong> = Half Day</span>
    <span><strong>A</strong> = Absent (deducted)</span>
    <span><strong>L</strong> = On Leave</span>
    <span><strong>Late</strong> = Late arrivals (minutes past 09:00 deducted)</span>
    <span><strong>OT</strong> = Overtime hours after 6:15 PM paid at 2× hourly rate</span>
</div>
@endsection
