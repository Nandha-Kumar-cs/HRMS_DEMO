@extends('layouts.app')
@section('title', 'Monthly Benefit Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.benefits-index') }}" class="text-decoration-none">Reports</a></li>
    <li class="breadcrumb-item active">Monthly Benefits</li>
@endsection

@section('content')

{{-- Filters --}}
<div class="card page-card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label fw-semibold mb-1">Search Employee</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Name or code…" value="{{ $search ?? '' }}">
                </div>
            </div>
            <div class="col-sm-2">
                <label class="form-label fw-semibold mb-1">Department</label>
                <select name="department" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ ($department ?? '') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label fw-semibold mb-1">Month</label>
                <select name="month" class="form-select form-select-sm">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$m,1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label fw-semibold mb-1">Year</label>
                <select name="year" class="form-select form-select-sm">
                    @for($y = now()->year + 1; $y >= now()->year - 4; $y--)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-sm-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="fa fa-filter me-1"></i>Run
                </button>
                @if(!empty($search) || !empty($department))
                <a href="{{ route('reports.monthly-benefits', ['month' => $month, 'year' => $year]) }}"
                   class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa fa-times me-1"></i>Clear
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Summary cards --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-3">
                <small class="text-muted">Total Benefits</small>
                <h3 class="text-success fw-bold mb-0">₹{{ number_format($summary['total_amount'], 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-3">
                <small class="text-muted">Employees Covered</small>
                <h3 class="text-primary fw-bold mb-0">{{ $summary['employee_count'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-3">
                <small class="text-muted">Distinct Fund Types</small>
                <h3 class="text-info fw-bold mb-0">{{ count($summary['by_type']) }}</h3>
            </div>
        </div>
    </div>
</div>

@forelse($summary['by_type'] as $section)
<div class="card mb-3 page-card">
    <div class="card-header bg-{{ $section['color'] }} text-white d-flex justify-content-between">
        <strong>{{ $section['name'] }}</strong>
        <span>{{ $section['count'] }} employee(s) · ₹{{ number_format($section['total'], 2) }}</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Effective Month</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($section['items'] as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $item->employee?->full_name }}</td>
                    <td>{{ $item->employee?->department?->name ?? '—' }}</td>
                    <td>{{ $item->effective_month?->format('M Y') }}</td>
                    <td class="text-end text-success fw-semibold">₹{{ number_format($item->amount, 2) }}</td>
                    <td><span class="badge bg-{{ $item->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($item->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="alert alert-info">
    <i class="fa fa-circle-info me-2"></i>
    No active benefits found for {{ date('F', mktime(0,0,0,$month,1)) }} {{ $year }}
    @if(!empty($search) || !empty($department)) with the selected filters @endif.
</div>
@endforelse
@endsection
