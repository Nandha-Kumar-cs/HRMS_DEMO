@extends('layouts.app')
@section('title', 'Employee Benefits')
@section('breadcrumb')
    <li class="breadcrumb-item active">Employee Benefits</li>
@endsection

@section('content')

<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-gift me-2 text-primary"></i>Employee Benefits</h5>
        @if(in_array(auth()->user()->role ?? 'admin', ['admin','manager']))
        <a href="{{ route('employee-benefits.create') }}" class="btn btn-sm btn-primary">
            <i class="fa fa-plus me-1"></i>Assign Benefit
        </a>
        @endif
    </div>
    <div class="card-body">
        {{-- Filters --}}
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Employee</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>
                        {{ $e->full_name }} ({{ $e->employee_code }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Fund Type</label>
                <select name="fund_type_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($fundTypes as $t)
                    <option value="{{ $t->id }}" {{ request('fund_type_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
            <div class="col-md-1">
                <label class="form-label small fw-semibold">Year</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">All</option>
                    @for($y = now()->year + 1; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-auto d-flex align-items-end">
                <button class="btn btn-sm btn-primary px-3"><i class="fa fa-filter me-1"></i>Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Fund Type / Benefit</th>
                        <th class="text-end">Amount (₹)</th>
                        <th>Frequency</th>
                        <th>Coverage</th>
                        <th>Status</th>
                        <th>Added By</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($benefits as $i => $b)
                    @php
                        $displayName = $b->benefit_name ?? $b->fundType?->name ?? '—';
                        $freqColors  = ['weekly'=>'info','fortnightly'=>'info','monthly'=>'primary','quarterly'=>'warning','half_yearly'=>'warning','annual'=>'danger'];
                        $freqColor   = $freqColors[$b->frequency ?? 'monthly'] ?? 'secondary';
                        $freqLabel   = $b->getFrequencyLabel();
                    @endphp
                    <tr>
                        <td>{{ $benefits->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-semibold">{{ $b->employee?->full_name }}</div>
                            <small class="text-muted">{{ $b->employee?->employee_code }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $b->fundType?->color ?? 'secondary' }}">{{ $b->fundType?->name ?? '—' }}</span>
                            @if($b->benefit_name)
                                <br><small class="text-muted">{{ $b->benefit_name }}</small>
                            @endif
                        </td>
                        <td class="text-end fw-semibold text-success">₹{{ number_format($b->amount, 2) }}</td>
                        <td>
                            @if($b->frequency)
                                <span class="badge bg-{{ $freqColor }} text-{{ in_array($b->frequency, ['quarterly','half_yearly']) ? 'dark' : 'white' }}" style="font-size:.72rem">{{ $freqLabel }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="small">
                            @if($b->start_date)
                                <span class="text-success fw-semibold">{{ $b->start_date->format('d M Y') }}</span>
                                →
                                @if($b->end_date)
                                    <span class="text-danger">{{ $b->end_date->format('d M Y') }}</span>
                                @else
                                    <span class="text-muted">Ongoing</span>
                                @endif
                            @else
                                {{ $b->effective_month?->format('M Y') ?? '—' }}
                            @endif
                        </td>
                        <td><span class="badge bg-{{ $b->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($b->status) }}</span></td>
                        <td class="small text-muted">{{ $b->addedBy?->name ?? '—' }}</td>
                        <td class="text-center">
                            @if(in_array(auth()->user()->role ?? 'admin', ['admin','manager']))
                            <a href="{{ route('employee-benefits.edit', $b) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-pen"></i></a>
                            <form action="{{ route('employee-benefits.destroy', $b) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this benefit?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No benefits found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $benefits->links() }}</div>
    </div>
</div>
@endsection
