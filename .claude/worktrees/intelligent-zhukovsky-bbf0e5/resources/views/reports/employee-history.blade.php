@extends('layouts.app')
@section('title', 'Employee Benefit History')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.benefits-index') }}" class="text-decoration-none">Reports</a></li>
    <li class="breadcrumb-item active">Employee History</li>
@endsection

@section('content')
<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-5">
        <label class="form-label fw-semibold">Employee</label>
        <select name="employee_id" class="form-select" required>
            <option value="">Select Employee</option>
            @foreach($employees as $e)
            <option value="{{ $e->id }}" {{ $employee?->id == $e->id ? 'selected' : '' }}>{{ $e->full_name }} ({{ $e->employee_code }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-primary"><i class="fa fa-search me-1"></i>View</button></div>
</form>

@if($employee)
<div class="card mb-3 page-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1">{{ $employee->full_name }} <small class="text-muted">({{ $employee->employee_code }})</small></h5>
                <small class="text-muted">{{ $employee->designation?->name ?? '—' }} · {{ $employee->department?->name ?? '—' }}</small>
            </div>
            <div class="text-end">
                <small class="text-muted">Total Benefits: <strong class="text-success">₹{{ number_format($benefits->sum('amount'), 2) }}</strong></small><br>
                <small class="text-muted">Total Bonuses: <strong class="text-warning">₹{{ number_format($bonuses->where('status','approved')->sum('amount'), 2) }}</strong></small>
            </div>
        </div>
    </div>
</div>

{{-- Benefits --}}
<div class="card mb-3 page-card">
    <div class="card-header bg-info text-white"><strong>Benefit Funds — {{ $benefits->count() }} record(s)</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr><th>#</th><th>Fund Type</th><th>Amount</th><th>Effective Month</th><th>Status</th><th>Notes</th></tr>
            </thead>
            <tbody>
                @forelse($benefits as $i => $b)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><span class="badge bg-{{ $b->fundType?->color ?? 'secondary' }}">{{ $b->fundType?->name ?? '—' }}</span></td>
                    <td class="fw-semibold text-success">₹{{ number_format($b->amount, 2) }}</td>
                    <td>{{ $b->effective_month?->format('M Y') }}</td>
                    <td><span class="badge bg-{{ $b->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($b->status) }}</span></td>
                    <td class="small text-muted">{{ $b->description ?: '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No benefits.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Bonuses --}}
@if($bonuses->isNotEmpty())
<div class="card page-card">
    <div class="card-header bg-warning text-dark"><strong>Bonuses & Incentives — {{ $bonuses->count() }} record(s)</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr><th>#</th><th>Type</th><th>Amount</th><th>Reason</th><th>Payroll Month</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($bonuses as $i => $b)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><span class="badge bg-{{ $b->type_color }}">{{ $b->type_label }}</span></td>
                    <td class="fw-semibold text-success">₹{{ number_format($b->amount, 2) }}</td>
                    <td class="small">{{ $b->reason }}</td>
                    <td>{{ date('M Y', mktime(0,0,0,$b->payroll_month,1,$b->payroll_year)) }}</td>
                    <td>
                        @php $sBadge = ['pending'=>'warning text-dark','approved'=>'success','rejected'=>'danger'][$b->status] ?? 'secondary'; @endphp
                        <span class="badge bg-{{ $sBadge }}">{{ ucfirst($b->status) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@else
<div class="alert alert-info">Select an employee to view their benefit & bonus history.</div>
@endif
@endsection
