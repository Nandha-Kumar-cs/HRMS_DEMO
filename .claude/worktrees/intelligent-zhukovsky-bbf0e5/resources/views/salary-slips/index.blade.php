@extends('layouts.app')
@section('title', 'Salary Slips')
@section('breadcrumb')
    <li class="breadcrumb-item active">Salary Slips</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">Salary Slips</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('salary-slips.calculate') }}" class="btn btn-outline-primary btn-sm"><i class="fa fa-calculator me-1"></i>Salary Calculation</a>
            <a href="{{ route('salary-slips.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Generate Slip</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr><th>#</th><th>Employee</th><th>Month</th><th>Year</th><th>CTC/Month</th><th>Net Salary</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($slips as $slip)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><div class="fw-semibold">{{ $slip->employee->full_name }}</div><small class="text-muted">{{ $slip->employee->employee_code }}</small></td>
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
                    <tr><td colspan="7" class="text-center text-muted">No salary slips yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $slips->links() }}
    </div>
</div>
@endsection
