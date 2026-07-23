@extends('layouts.app')
@section('title', 'Payroll Impact Summary')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.benefits-index') }}" class="text-decoration-none">Reports</a></li>
    <li class="breadcrumb-item active">Payroll Impact</li>
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
    <div class="col-auto"><button class="btn btn-primary"><i class="fa fa-search me-1"></i>Run</button></div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-3">
                <small class="text-muted">Total Benefits</small>
                <h3 class="text-info fw-bold mb-0">₹{{ number_format($benefitsTotal, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-3">
                <small class="text-muted">Total Bonuses</small>
                <h3 class="text-warning fw-bold mb-0">₹{{ number_format($bonusesTotal, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-3">
                <small class="text-muted">Combined Impact</small>
                <h3 class="text-success fw-bold mb-0">₹{{ number_format($benefitsTotal + $bonusesTotal, 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card page-card">
    <div class="card-header bg-white"><strong>Per-Employee Breakdown — {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th class="text-end">Benefits</th>
                        <th class="text-end">Bonuses</th>
                        <th class="text-end">Total Extras</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $r['employee']->full_name }}<br><small class="text-muted">{{ $r['employee']->employee_code }}</small></td>
                        <td>{{ $r['employee']->department?->name ?? '—' }}</td>
                        <td class="text-end">₹{{ number_format($r['benefit_total'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($r['bonus_total'], 2) }}</td>
                        <td class="text-end fw-bold text-success">₹{{ number_format($r['total_extras'], 2) }}</td>
                        <td class="small">
                            @foreach($r['benefits'] as $b)
                                <span class="badge bg-info me-1">{{ $b->fundType?->name ?? '—' }}: ₹{{ number_format($b->amount, 0) }}</span>
                            @endforeach
                            @foreach($r['bonuses'] as $b)
                                <span class="badge bg-{{ $b->type_color }} me-1">{{ $b->type_label }}: ₹{{ number_format($b->amount, 0) }}</span>
                            @endforeach
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No employees received benefits or bonuses in this month.</td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                <tfoot class="table-dark">
                    <tr>
                        <td colspan="3" class="fw-bold">Totals</td>
                        <td class="text-end fw-bold">₹{{ number_format($rows->sum('benefit_total'), 2) }}</td>
                        <td class="text-end fw-bold">₹{{ number_format($rows->sum('bonus_total'), 2) }}</td>
                        <td class="text-end fw-bold text-warning">₹{{ number_format($rows->sum('total_extras'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
