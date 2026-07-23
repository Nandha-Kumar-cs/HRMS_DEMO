@extends('layouts.app')
@section('title', 'Salary Slips')
@section('breadcrumb')
    <li class="breadcrumb-item active">Salary Slips</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-file-invoice-dollar me-2 text-primary"></i>Salary Slips</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('salary-slips.calculate') }}" class="btn btn-outline-primary btn-sm"><i class="fa fa-calculator me-1"></i>Salary Calculation</a>
            <a href="{{ route('salary-slips.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Generate Slip</a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label fw-semibold mb-1">Search Employee</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Name or code…" value="{{ $search }}">
                </div>
            </div>
            <div class="col-sm-2">
                <label class="form-label fw-semibold mb-1">Department</label>
                <select name="department" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $department == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label fw-semibold mb-1">Month</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">All Months</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$m,1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label fw-semibold mb-1">Year</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">All Years</option>
                    @for($y = now()->year + 1; $y >= now()->year - 4; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-sm-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="fa fa-filter me-1"></i>Filter
                </button>
                @if($search || $department || $month || $year)
                <a href="{{ route('salary-slips.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa fa-times me-1"></i>Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    <div class="card-body">
        {{-- Result count --}}
        <div class="text-muted small mb-2">
            Showing {{ $slips->firstItem() ?? 0 }}–{{ $slips->lastItem() ?? 0 }} of {{ $slips->total() }} slip(s)
            @if($search || $department || $month || $year)
                <span class="ms-1 text-primary">(filtered)</span>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Month</th>
                        <th>Year</th>
                        <th>CTC/Month</th>
                        <th>Net Salary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slips as $slip)
                    <tr>
                        <td>{{ $slips->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="fw-semibold">{{ $slip->employee->full_name }}</div>
                            <small class="text-muted">{{ $slip->employee->employee_code }}</small>
                        </td>
                        <td><small class="text-muted">{{ $slip->employee->department?->name ?? '—' }}</small></td>
                        <td>{{ $slip->month_name }}</td>
                        <td>{{ $slip->year }}</td>
                        <td>₹{{ number_format($slip->fixed_salary, 2) }}</td>
                        <td class="fw-semibold text-success">₹{{ number_format($slip->net_salary, 2) }}</td>
                        <td>
                            <a href="{{ route('salary-slips.show', $slip) }}" class="btn btn-sm btn-info text-white" title="View"><i class="fa fa-eye"></i></a>
                            <a href="{{ route('salary-slips.pdf', $slip) }}" class="btn btn-sm btn-danger ms-1" target="_blank" title="PDF"><i class="fa fa-file-pdf"></i></a>
                            <form action="{{ route('salary-slips.destroy', $slip) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete salary slip for {{ addslashes($slip->employee->full_name) }} ({{ $slip->month_name }} {{ $slip->year }})?\n\nThis will also reverse any associated loan repayments.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger ms-1" title="Delete"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        <i class="fa fa-file-invoice-dollar fa-2x d-block mb-2 opacity-25"></i>
                        No salary slips found{{ ($search || $department || $month || $year) ? ' for the selected filters.' : '.' }}
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $slips->links() }}
    </div>
</div>
@endsection
