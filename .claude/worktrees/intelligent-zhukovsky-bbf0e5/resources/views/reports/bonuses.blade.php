@extends('layouts.app')
@section('title', 'Bonus Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.benefits-index') }}" class="text-decoration-none">Reports</a></li>
    <li class="breadcrumb-item active">Bonuses</li>
@endsection

@section('content')
<form method="GET" class="row g-2 mb-3 align-items-end">
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
            @for($y = now()->year + 1; $y >= now()->year - 4; $y--)
            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label fw-semibold mb-1">Type</label>
        <select name="type" class="form-select">
            <option value="">All</option>
            @foreach(\App\Models\EmployeeBonus::TYPES as $k => $label)
            <option value="{{ $k }}" {{ $type === $k ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-primary"><i class="fa fa-search me-1"></i>Run</button></div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-3">
                <small class="text-muted">Total Bonus Amount</small>
                <h3 class="text-success fw-bold mb-0">₹{{ number_format($totalAmount, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-3">
                <small class="text-muted">Total Records</small>
                <h3 class="text-primary fw-bold mb-0">{{ $totalCount }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-3">
                <small class="text-muted">Distinct Types</small>
                <h3 class="text-info fw-bold mb-0">{{ $byType->count() }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- Type breakdown --}}
@if($byType->count())
<div class="card mb-3 page-card">
    <div class="card-header bg-white"><strong>Breakdown by Type</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>Type</th><th class="text-center">Count</th><th class="text-end">Total</th></tr>
            </thead>
            <tbody>
                @foreach($byType as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="text-center">{{ $row['count'] }}</td>
                    <td class="text-end fw-semibold text-success">₹{{ number_format($row['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Detailed list --}}
<div class="card page-card">
    <div class="card-header bg-white"><strong>All Bonus Records — {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</strong></div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 align-middle">
            <thead class="table-dark">
                <tr><th>#</th><th>Employee</th><th>Department</th><th>Type</th><th>Reason</th><th class="text-end">Amount</th><th>Added By</th></tr>
            </thead>
            <tbody>
                @forelse($bonuses as $i => $b)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $b->employee?->full_name }}</td>
                    <td>{{ $b->employee?->department?->name ?? '—' }}</td>
                    <td><span class="badge bg-{{ $b->type_color }}">{{ $b->type_label }}</span></td>
                    <td class="small">{{ $b->reason }}</td>
                    <td class="text-end fw-semibold text-success">₹{{ number_format($b->amount, 2) }}</td>
                    <td class="small text-muted">{{ $b->addedBy?->name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No bonuses found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
