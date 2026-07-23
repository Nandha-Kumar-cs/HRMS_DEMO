@extends('layouts.app')
@section('title', 'Bonuses & Incentives')
@section('breadcrumb')
    <li class="breadcrumb-item active">Bonuses & Incentives</li>
@endsection

@section('content')

<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-trophy me-2 text-warning"></i>Bonuses & Incentives</h5>
        @if(in_array(auth()->user()->role ?? 'admin', ['admin','manager']))
        <a href="{{ route('employee-bonuses.create') }}" class="btn btn-sm btn-primary">
            <i class="fa fa-plus me-1"></i>Add Bonus / Incentive
        </a>
        @endif
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Employee</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>{{ $e->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(\App\Models\EmployeeBonus::TYPES as $k => $label)
                    <option value="{{ $k }}" {{ request('type') === $k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Month</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">All</option>
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Year</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">All</option>
                    @for($y = now()->year + 1; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-sm btn-primary w-100"><i class="fa fa-filter"></i></button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Type</th>
                        <th class="text-end">Amount</th>
                        <th>Reason</th>
                        <th>Payroll Month</th>
                        <th>Status</th>
                        <th>Added By</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bonuses as $i => $b)
                    <tr>
                        <td>{{ $bonuses->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-semibold">{{ $b->employee?->full_name }}</div>
                            <small class="text-muted">{{ $b->employee?->employee_code }}</small>
                        </td>
                        <td><span class="badge bg-{{ $b->type_color }}">{{ $b->type_label }}</span></td>
                        <td class="text-end fw-semibold text-success">₹{{ number_format($b->amount, 2) }}</td>
                        <td class="small">{{ $b->reason }}</td>
                        <td>{{ date('M Y', mktime(0,0,0,$b->payroll_month,1,$b->payroll_year)) }}</td>
                        <td>
                            @php
                                $sBadge = ['pending' => 'warning text-dark', 'approved' => 'success', 'rejected' => 'danger'][$b->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $sBadge }}">{{ ucfirst($b->status) }}</span>
                        </td>
                        <td class="small text-muted">{{ $b->addedBy?->name ?? '—' }}</td>
                        <td class="text-center">
                            @if(in_array(auth()->user()->role ?? 'admin', ['admin','manager']))
                                @if($b->status === 'pending')
                                <form action="{{ route('employee-bonuses.approve', $b) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success" title="Approve"><i class="fa fa-check"></i></button>
                                </form>
                                <form action="{{ route('employee-bonuses.reject', $b) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-warning" title="Reject"><i class="fa fa-xmark"></i></button>
                                </form>
                                @endif
                                <a href="{{ route('employee-bonuses.edit', $b) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-pen"></i></a>
                                <form action="{{ route('employee-bonuses.destroy', $b) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this bonus?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No bonuses found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $bonuses->links() }}</div>
    </div>
</div>
@endsection
