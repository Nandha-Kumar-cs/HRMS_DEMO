@extends('layouts.app')
@section('title', 'Employee Leave Status ' . $year)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}" class="text-decoration-none">Attendance</a></li>
    <li class="breadcrumb-item active">Leave Status</li>
@endsection

@push('styles')
<style>
/* ── Sticky employee column ── */
.ls-table-wrap { overflow-x: auto; }

.ls-table { font-size: .8rem; min-width: 900px; border-collapse: separate; border-spacing: 0; }

.ls-table th, .ls-table td { white-space: nowrap; padding: 6px 10px; border: 1px solid #dee2e6; }

/* Freeze left columns */
.ls-table .col-no,
.ls-table .col-emp,
.ls-table .col-dept {
    position: sticky;
    background: #fff;
    z-index: 2;
}
.ls-table .col-no   { left: 0;    min-width: 36px;  max-width: 36px; }
.ls-table .col-emp  { left: 36px; min-width: 160px; max-width: 200px; }
.ls-table .col-dept { left: 196px;min-width: 110px; max-width: 140px; }

.ls-table thead tr th {
    background: #1e293b;
    color: #fff;
    font-weight: 600;
    font-size: .75rem;
    text-align: center;
    position: sticky;
    top: 0;
    z-index: 3;
}
.ls-table thead tr th.col-no,
.ls-table thead tr th.col-emp,
.ls-table thead tr th.col-dept { z-index: 4; }  /* header + sticky col overlap */

.ls-table tbody tr:hover .col-no,
.ls-table tbody tr:hover .col-emp,
.ls-table tbody tr:hover .col-dept { background: #f8fafc; }
.ls-table tbody tr:hover { background: #f8fafc; }

/* ── Balance cell colours ── */
.bal-pos  { color: #15803d; font-weight: 700; background: #f0fdf4; }
.bal-zero { color: #64748b; }
.bal-neg  { color: #b91c1c; font-weight: 700; background: #fef2f2; }

/* Annual column */
.col-annual { background: #f8fafc !important; font-weight: 700; border-left: 2px solid #94a3b8 !important; }
.ls-table thead th.col-annual { background: #334155 !important; }

/* Current month highlight */
.col-current { border-top: 3px solid #3b82f6 !important; }
.ls-table thead th.col-current { background: #2563eb !important; }

/* Totals row */
.totals-row td { background: #f1f5f9 !important; font-weight: 600; border-top: 2px solid #94a3b8 !important; }
.totals-row .col-no, .totals-row .col-emp, .totals-row .col-dept { background: #f1f5f9 !important; }

/* Summary chips */
.ls-chip { display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:600; }
.ls-chip-green   { background:#dcfce7;color:#15803d;border:1px solid #bbf7d0; }
.ls-chip-neutral { background:#f1f5f9;color:#475569;border:1px solid #cbd5e1; }
.ls-chip-red     { background:#fee2e2;color:#b91c1c;border:1px solid #fecaca; }

@media print {
    .no-print { display:none!important; }
    .ls-table .col-no, .ls-table .col-emp, .ls-table .col-dept { position:static; }
}
</style>
@endpush

@section('content')
<div class="card page-card">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 fw-semibold"><i class="fa fa-calendar-check me-2 text-primary"></i>Employee Leave Status</h5>
            <span class="badge bg-light text-dark border">{{ $year }}</span>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('leave-status.export', request()->query()) }}" class="btn btn-sm btn-outline-success">
                <i class="fa fa-file-excel me-1"></i>Export Excel
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-print me-1"></i>Print
            </button>
        </div>
    </div>

    {{-- ── Filters ──────────────────────────────────────────────────────────── --}}
    <div class="card-body border-bottom pb-3 no-print">
        <form method="GET" id="leaveStatusForm" class="row g-2 align-items-end">
            {{-- Year --}}
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Year</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($y = now()->year + 1; $y >= now()->year - 4; $y--)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Department --}}
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Department</label>
                <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $deptId == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Leave Type --}}
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Leave Type</label>
                <select name="leave_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ $leaveTypeId == $lt->id ? 'selected' : '' }}>
                            {{ $lt->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Emp Status --}}
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Emp Status</label>
                <select name="emp_status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="active" {{ ($empStatus ?? 'active') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="all"    {{ ($empStatus ?? 'active') === 'all'    ? 'selected' : '' }}>All Employees</option>
                </select>
            </div>

            {{-- Search --}}
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control"
                           placeholder="Employee name or code…"
                           value="{{ $search ?? '' }}">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                    @if($search || $deptId || $leaveTypeId)
                    <a href="{{ route('leave-status.index', ['year' => $year, 'emp_status' => $empStatus]) }}"
                       class="btn btn-outline-secondary" title="Clear filters"><i class="fa fa-xmark"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div class="card-body">

        {{-- ── Summary row ─────────────────────────────────────────────────── --}}
        @php
            $cntGreen = $cntNeutral = $cntRed = 0;
            foreach ($employees->getCollection() as $emp) {
                $annualBalance = $grid[$emp->id]['annual']['balance'] ?? 12;
                if ($annualBalance > 0)      $cntGreen++;
                elseif ($annualBalance == 0) $cntNeutral++;
                else                         $cntRed++;
            }
        @endphp

        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
            <span class="text-muted small me-1">{{ $year }} &mdash; {{ $employees->total() }} employees</span>
            <span class="ls-chip ls-chip-green"><i class="fa fa-circle-check"></i>{{ $cntGreen }} have annual balance</span>
            <span class="ls-chip ls-chip-neutral"><i class="fa fa-minus-circle"></i>{{ $cntNeutral }} fully used</span>
            <span class="ls-chip ls-chip-red"><i class="fa fa-circle-exclamation"></i>{{ $cntRed }} exceeded quota</span>
        </div>

        {{-- ── Info ──────────────────────────────────────────────────────────── --}}
        <div class="alert alert-light border py-2 mb-3 small">
            <i class="fa fa-info-circle text-primary me-1"></i>
            Shows <span class="badge bg-success">Approved</span> leave days only &mdash; leave type-based.
            &nbsp;|&nbsp;
            <span class="fw-bold" style="color:#15803d">■</span> Green = Paid leave (no deduction) &nbsp;
            <span class="fw-bold" style="color:#b91c1c">■</span> Red = Unpaid leave (salary deducted).
            &nbsp;|&nbsp;
            <strong>—</strong> = No leave taken / future month.
            &nbsp;|&nbsp;
            Annual <strong>bal:</strong> = paid-quota balance (1 paid leave/month).
        </div>

        {{-- ── Yearly Grid Table ────────────────────────────────────────────── --}}
        @php $currentMonth = now()->month; @endphp

        @if($employees->isEmpty())
            <div class="text-center py-5">
                <i class="fa fa-calendar-xmark fa-3x text-muted mb-3 d-block"></i>
                <p class="text-muted">No employees found.</p>
                <a href="{{ route('leave-status.index', ['year' => $year]) }}" class="btn btn-sm btn-outline-secondary">Reset filters</a>
            </div>
        @else
        <div class="ls-table-wrap">
            <table class="ls-table table-bordered">
                <thead>
                    <tr>
                        <th class="col-no text-center">#</th>
                        <th class="col-emp">Employee</th>
                        <th class="col-dept">Department</th>
                        @for($m = 1; $m <= 12; $m++)
                            <th class="text-center {{ ($m == $currentMonth && $year == now()->year) ? 'col-current' : '' }}"
                                style="min-width:54px">
                                {{ date('M', mktime(0,0,0,$m,1)) }}
                                <br><span style="font-size:.65rem;font-weight:400;opacity:.75">{{ $year }}</span>
                            </th>
                        @endfor
                        <th class="text-center col-annual" style="min-width:70px">
                            Annual<br><span style="font-size:.65rem;font-weight:400;opacity:.75">Balance</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php $startIdx = $employees->firstItem(); @endphp
                    @foreach($employees->getCollection() as $i => $emp)
                    @php $empGrid = $grid[$emp->id] ?? []; @endphp
                    <tr>
                        <td class="col-no text-center text-muted">{{ $startIdx + $i }}</td>
                        <td class="col-emp">
                            <a href="{{ route('employees.show', $emp) }}"
                               class="text-decoration-none text-dark fw-semibold">{{ $emp->full_name }}</a>
                            <br><small class="text-muted">{{ $emp->employee_code }}</small>
                        </td>
                        <td class="col-dept text-muted small">{{ $emp->department?->name ?? '—' }}</td>

                        @for($m = 1; $m <= 12; $m++)
                        @php
                            $isFuture   = ($year > now()->year) || ($year == now()->year && $m > now()->month);
                            $cell       = $empGrid[$m] ?? ['paid_days' => 0, 'unpaid_days' => 0, 'days' => 0, 'balance' => 1];
                            $paidDays   = $cell['paid_days']   ?? 0;
                            $unpaidDays = $cell['unpaid_days'] ?? 0;
                            $isCurr     = ($m == $currentMonth && $year == now()->year);

                            // Tooltip
                            if ($isFuture) {
                                $tip = 'Not yet applicable';
                            } else {
                                $tipParts = [];
                                if ($paidDays > 0)   $tipParts[] = $paidDays . ' paid leave day' . ($paidDays > 1 ? 's' : '');
                                if ($unpaidDays > 0) $tipParts[] = $unpaidDays . ' unpaid leave day' . ($unpaidDays > 1 ? 's' : '');
                                $tip = $tipParts ? implode(' + ', $tipParts) : 'No approved leave';
                            }
                        @endphp
                        <td class="text-center {{ $isCurr ? 'col-current' : '' }}"
                            @if($isFuture) style="color:#cbd5e1;font-size:.8rem" @endif
                            title="{{ date('F', mktime(0,0,0,$m,1)) }}: {{ $tip }}">
                            @if($isFuture)
                                <span style="color:#cbd5e1">—</span>
                            @elseif($paidDays === 0 && $unpaidDays === 0)
                                <span class="text-muted">—</span>
                            @else
                                @if($paidDays > 0 && $unpaidDays === 0)
                                    {{-- Only paid leave --}}
                                    <span style="color:#15803d;font-weight:700">{{ $paidDays }}d</span>
                                @elseif($unpaidDays > 0 && $paidDays === 0)
                                    {{-- Only unpaid leave --}}
                                    <span style="color:#b91c1c;font-weight:700">{{ $unpaidDays }}d</span>
                                @else
                                    {{-- Mixed: paid + unpaid --}}
                                    <span style="color:#15803d;font-weight:700">{{ $paidDays }}p</span>&nbsp;<span style="color:#b91c1c;font-weight:700">{{ $unpaidDays }}u</span>
                                @endif
                            @endif
                        </td>
                        @endfor

                        {{-- Annual total --}}
                        @php
                            $ann        = $empGrid['annual'] ?? ['paid_days' => 0, 'unpaid_days' => 0, 'days' => 0, 'balance' => 12, 'quota' => 12];
                            $annPaid    = $ann['paid_days']   ?? 0;
                            $annUnpaid  = $ann['unpaid_days'] ?? 0;
                            $annBal     = $ann['balance'];
                            $yearFuture = ($year > now()->year);
                            $annTipBal  = $yearFuture ? '' : ('Paid: '.$annPaid.'d, Balance: '.($annBal >= 0 ? '+' : '').$annBal.' | Unpaid: '.$annUnpaid.'d');
                        @endphp
                        <td class="text-center col-annual"
                            @if($yearFuture) style="color:#cbd5e1" @endif
                            title="{{ $yearFuture ? 'Year not yet started' : $annTipBal }}">
                            @if($yearFuture)
                                <span style="color:#cbd5e1">—</span>
                            @elseif($annPaid === 0 && $annUnpaid === 0)
                                <span class="text-muted">—</span>
                                <br><small style="font-size:.65rem;color:#94a3b8">bal: +{{ $ann['quota'] ?? 0 }}</small>
                            @else
                                @if($annPaid > 0 && $annUnpaid === 0)
                                    <span style="color:#15803d;font-weight:700">{{ $annPaid }}d</span>
                                @elseif($annUnpaid > 0 && $annPaid === 0)
                                    <span style="color:#b91c1c;font-weight:700">{{ $annUnpaid }}d</span>
                                @else
                                    <span style="color:#15803d;font-weight:700">{{ $annPaid }}p</span>&nbsp;<span style="color:#b91c1c;font-weight:700">{{ $annUnpaid }}u</span>
                                @endif
                                <br><small style="font-size:.65rem;font-weight:400;color:{{ $annBal >= 0 ? '#15803d' : '#b91c1c' }}">bal: {{ $annBal >= 0 ? '+' . $annBal : $annBal }}</small>
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    {{-- Totals row --}}
                    <tr class="totals-row">
                        <td class="col-no"></td>
                        <td class="col-emp fw-semibold">Totals</td>
                        <td class="col-dept text-muted small">all employees</td>
                        @for($m = 1; $m <= 12; $m++)
                        @php
                            $totFuture = ($year > now()->year) || ($year == now()->year && $m > now()->month);
                            $tot       = $totals[$m];
                            $totPaid   = $tot['paid_taken']   ?? 0;
                            $totUnpaid = $tot['unpaid_taken'] ?? 0;
                        @endphp
                        <td class="text-center" @if($totFuture) style="color:#cbd5e1" @endif>
                            @if($totFuture)
                                <span style="color:#cbd5e1">—</span>
                            @elseif($totPaid === 0 && $totUnpaid === 0)
                                <span class="text-muted">—</span>
                            @else
                                @if($totPaid > 0 && $totUnpaid === 0)
                                    <span style="color:#15803d;font-weight:600">{{ $totPaid }}d</span>
                                @elseif($totUnpaid > 0 && $totPaid === 0)
                                    <span style="color:#b91c1c;font-weight:600">{{ $totUnpaid }}d</span>
                                @else
                                    <span style="color:#15803d;font-weight:600">{{ $totPaid }}p</span>&nbsp;<span style="color:#b91c1c;font-weight:600">{{ $totUnpaid }}u</span>
                                @endif
                            @endif
                        </td>
                        @endfor
                        @php
                            $annTot      = $totals['annual'];
                            $annTotPaid  = $annTot['paid_taken']   ?? 0;
                            $annTotUnpaid= $annTot['unpaid_taken'] ?? 0;
                            $annTotTaken = $annTot['taken'] ?? 0;
                            $annTotQuota = $annTot['quota'] ?? 0;
                        @endphp
                        <td class="text-center col-annual">
                            @if($annTotPaid > 0)
                                <span style="color:#15803d;font-weight:600">{{ $annTotPaid }}p</span>
                            @endif
                            @if($annTotUnpaid > 0)
                                @if($annTotPaid > 0)&nbsp;@endif
                                <span style="color:#b91c1c;font-weight:600">{{ $annTotUnpaid }}u</span>
                            @endif
                            @if($annTotPaid === 0 && $annTotUnpaid === 0)
                                <span class="text-muted">—</span>
                            @endif
                            <br><small style="font-size:.65rem;color:#64748b">{{ $annTotTaken }}/{{ $annTotQuota }}d</small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ── Pagination ───────────────────────────────────────────────────── --}}
        @if($employees->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2 no-print">
            <small class="text-muted">
                Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }} employees
            </small>
            {{ $employees->links() }}
        </div>
        @endif

        @endif

    </div>{{-- card-body --}}
</div>{{-- card --}}
@endsection
