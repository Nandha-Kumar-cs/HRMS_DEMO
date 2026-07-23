@extends('layouts.app')
@section('title', 'Leave History')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('leave-requests.index') }}" class="text-decoration-none">Leave Requests</a></li>
    <li class="breadcrumb-item active">Leave History</li>
@endsection

@section('content')
<div class="card page-card">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-clock-rotate-left me-2 text-primary"></i>Leave History</h5>
        <a href="{{ route('leave-requests.create') }}" class="btn btn-sm btn-primary">
            <i class="fa fa-plus me-1"></i>New Request
        </a>
    </div>

    <div class="card-body">

        {{-- ── Filter Form ─────────────────────────────────────────────────── --}}
        <form method="GET" action="{{ route('leave-requests.history') }}" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Employee</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" {{ $filterEmp == $e->id ? 'selected' : '' }}>
                            {{ $e->full_name }} ({{ $e->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Leave Type</label>
                <select name="leave_type_id" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $t)
                        <option value="{{ $t->id }}" {{ $filterType == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Month</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">All Months</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $filterMonth == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small fw-semibold">Year</label>
                <select name="year" class="form-select form-select-sm">
                    @for($y = now()->year + 1; $y >= now()->year - 4; $y--)
                        <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            {{-- keep active tab across filter submit --}}
            <input type="hidden" name="status" value="{{ $filterStatus }}">
            <div class="col-auto d-flex align-items-end">
                <button class="btn btn-sm btn-primary px-3"><i class="fa fa-filter me-1"></i>Filter</button>
                <a href="{{ route('leave-requests.history') }}" class="btn btn-sm btn-light ms-1">Reset</a>
            </div>
        </form>

        {{-- ── Summary Strip ────────────────────────────────────────────────── --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="border rounded p-3 text-center h-100">
                    <div class="fs-3 fw-bold text-dark">{{ $counts['all'] }}</div>
                    <div class="small text-muted">Total Requests</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-3 text-center h-100" style="border-color:#198754 !important;background:#f0fdf4">
                    <div class="fs-3 fw-bold text-success">{{ $counts['approved'] }}</div>
                    <div class="small text-muted">Approved &nbsp;<span class="badge bg-success">{{ $totalApprovedDays }}d</span></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-3 text-center h-100" style="border-color:#ffc107 !important;background:#fffbeb">
                    <div class="fs-3 fw-bold text-warning">{{ $counts['pending'] }}</div>
                    <div class="small text-muted">Pending</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-3 text-center h-100" style="border-color:#dc3545 !important;background:#fff5f5">
                    <div class="fs-3 fw-bold text-danger">{{ $counts['rejected'] }}</div>
                    <div class="small text-muted">Rejected</div>
                </div>
            </div>
        </div>

        {{-- ── Top Employees (approved days) ───────────────────────────────── --}}
        @if($empSummary->isNotEmpty())
        <div class="alert alert-light border py-2 mb-3 small">
            <i class="fa fa-trophy me-1 text-warning"></i>
            <strong>Most approved leave days ({{ $filterYear }}):</strong>
            @foreach($empSummary as $es)
                <span class="ms-2 badge bg-secondary">{{ $es['name'] }} — {{ $es['days'] }}d</span>
            @endforeach
        </div>
        @endif

        {{-- ── Status Tabs ─────────────────────────────────────────────────── --}}
        <ul class="nav nav-tabs mb-3">
            @foreach([
                'all'      => ['label'=>'All',      'color'=>'secondary'],
                'approved' => ['label'=>'Approved',  'color'=>'success'],
                'pending'  => ['label'=>'Pending',   'color'=>'warning'],
                'rejected' => ['label'=>'Rejected',  'color'=>'danger'],
            ] as $tab => $cfg)
            <li class="nav-item">
                <a class="nav-link {{ $filterStatus === $tab ? 'active fw-semibold' : '' }}"
                   href="{{ request()->fullUrlWithQuery(['status' => $tab, 'page' => 1]) }}">
                    {{ $cfg['label'] }}
                    <span class="badge bg-{{ $cfg['color'] }} ms-1" style="font-size:.7rem">{{ $counts[$tab] }}</span>
                </a>
            </li>
            @endforeach
        </ul>

        {{-- ── Leave Table ─────────────────────────────────────────────────── --}}
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" style="font-size:.875rem">
                <thead class="table-dark">
                    <tr>
                        <th style="width:36px">#</th>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th class="text-center">Days</th>
                        <th>Reason</th>
                        <th class="text-center">Status</th>
                        <th>Approved / Rejected By</th>
                        <th>Date</th>
                        <th class="text-center" style="width:60px">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $i => $lr)
                    @php
                        $statusColor = match($lr->status) {
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default    => 'warning text-dark',
                        };
                        $rowClass = match($lr->status) {
                            'approved' => '',
                            'rejected' => 'table-danger bg-opacity-10',
                            'pending'  => 'table-warning bg-opacity-10',
                            default    => '',
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="text-muted small">{{ $leaves->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-semibold">{{ $lr->employee?->full_name ?? '—' }}</div>
                            <small class="text-muted">{{ $lr->employee?->employee_code }}</small>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-75">{{ $lr->leaveType?->name ?? '—' }}</span>
                            @if($lr->leaveType?->is_paid)
                                <br><small class="text-success" style="font-size:.68rem">Paid</small>
                            @elseif($lr->leaveType?->is_comp_off)
                                <br><small class="text-purple" style="font-size:.68rem">Comp Off</small>
                            @else
                                <br><small class="text-muted" style="font-size:.68rem">Unpaid</small>
                            @endif
                        </td>
                        <td class="fw-semibold text-primary">{{ $lr->start_date->format('d M Y') }}</td>
                        <td class="{{ $lr->start_date->ne($lr->end_date) ? 'fw-semibold text-primary' : 'text-muted' }}">
                            {{ $lr->start_date->eq($lr->end_date) ? '—' : $lr->end_date->format('d M Y') }}
                        </td>
                        <td class="text-center fw-bold">
                            <span class="badge bg-secondary">{{ $lr->days_requested }}d</span>
                        </td>
                        <td class="text-muted small" style="max-width:180px">
                            {{ $lr->reason ? \Str::limit($lr->reason, 60) : '—' }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $statusColor }}">{{ ucfirst($lr->status) }}</span>
                        </td>
                        <td class="small">
                            @if($lr->approvedBy)
                                <div>{{ $lr->approvedBy->name }}</div>
                                <small class="text-muted">{{ $lr->approved_at?->format('d M Y, h:i A') }}</small>
                            @elseif($lr->status === 'pending')
                                <span class="text-muted fst-italic">Awaiting</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                            @if($lr->remarks)
                                <br><small class="text-info" title="{{ $lr->remarks }}"><i class="fa fa-comment-dots me-1"></i>{{ \Str::limit($lr->remarks, 40) }}</small>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $lr->created_at?->format('d M Y') }}</td>
                        <td class="text-center">
                            <a href="{{ route('leave-requests.show', $lr) }}"
                               class="btn btn-sm btn-outline-primary" title="View details">
                                <i class="fa fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-5">
                            <i class="fa fa-calendar-xmark fa-2x mb-2 d-block text-secondary"></i>
                            No leave records found for the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="small text-muted">
                Showing {{ $leaves->firstItem() ?? 0 }}–{{ $leaves->lastItem() ?? 0 }} of {{ $leaves->total() }} records
            </div>
            {{ $leaves->links() }}
        </div>

    </div>
</div>
@endsection
