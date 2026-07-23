@extends('layouts.app')
@section('title', 'Comp Off Balance Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('comp-off.dashboard') }}" class="text-decoration-none">Comp Off</a></li>
    <li class="breadcrumb-item active">Balance Report</li>
@endsection

@section('content')
<div class="card page-card">

    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold">
            <i class="fa fa-scale-balanced me-2 text-primary"></i>Comp Off Balance Report
        </h5>
    </div>

    {{-- Filters --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
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
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control"
                           placeholder="Employee name or code…" value="{{ $search ?? '' }}">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                    @if($search || $deptId)
                    <a href="{{ route('comp-off.balance') }}" class="btn btn-outline-secondary" title="Clear">
                        <i class="fa fa-xmark"></i>
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        @if($employees->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fa fa-users fa-3x mb-3 d-block opacity-25"></i>
                No employees found.
            </div>
        @else

        @if(!$compOffType)
        <div class="alert alert-warning m-3">
            <i class="fa fa-triangle-exclamation me-2"></i>
            Comp Off leave type is not configured. Please run <code>php artisan migrate</code> to set it up.
        </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th class="text-center">Credits Earned</th>
                        <th class="text-center">Days Used</th>
                        <th class="text-center">Balance</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i = $employees->firstItem(); @endphp
                    @foreach($employees->getCollection() as $emp)
                    @php
                        $credited = $creditMap[$emp->id] ?? 0;
                        $used     = (int) ($usedMap[$emp->id] ?? 0);
                        $balance  = max(0, $credited - $used);
                    @endphp
                    <tr>
                        <td class="text-muted small">{{ $i++ }}</td>
                        <td>
                            <strong>{{ $emp->full_name }}</strong>
                            <br><small class="text-muted">{{ $emp->employee_code }}</small>
                        </td>
                        <td class="text-muted small">{{ $emp->department?->name ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary rounded-pill">{{ $credited }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark rounded-pill">{{ $used }}</span>
                        </td>
                        <td class="text-center">
                            @if($balance > 0)
                                <span class="badge bg-success fs-6 px-3">{{ $balance }}</span>
                            @elseif($balance == 0)
                                <span class="badge bg-secondary rounded-pill">0</span>
                            @else
                                <span class="badge bg-danger rounded-pill">{{ $balance }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('comp-off.credits', ['search' => $emp->full_name]) }}"
                               class="btn btn-xs btn-outline-primary py-0 px-2 small" title="View Credits">
                                <i class="fa fa-eye"></i> History
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
        <div class="d-flex justify-content-between align-items-center px-3 py-2 flex-wrap gap-2">
            <small class="text-muted">
                Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }} employees
            </small>
            {{ $employees->links() }}
        </div>
        @endif

        @endif
    </div>
</div>
@endsection
