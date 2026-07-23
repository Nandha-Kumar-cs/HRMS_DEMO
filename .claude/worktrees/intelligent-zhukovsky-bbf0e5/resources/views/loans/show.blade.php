@extends('layouts.app')
@section('title','Loan / Advance Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('loans.index') }}" class="text-decoration-none">Loans & Advances</a></li>
<li class="breadcrumb-item active">Details</li>
@endsection
@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">

    {{-- ── Summary Card ──────────────────────────────────────────────────────── --}}
    <div class="col-md-4">
        <div class="card page-card h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="fa fa-file-invoice-dollar me-1 text-primary"></i>Loan Summary
                </h6>
                <div class="d-flex gap-1">
                    <a href="{{ route('loans.edit', $loan) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                    <a href="{{ route('loans.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted w-40">Employee</th><td class="fw-semibold">{{ $loan->employee->full_name }}</td></tr>
                    <tr><th class="text-muted">Code</th><td>{{ $loan->employee->employee_code }}</td></tr>
                    <tr><th class="text-muted">Type</th>
                        <td><span class="badge bg-{{ $loan->type === 'loan' ? 'primary' : 'info' }}">{{ ucfirst($loan->type) }}</span></td></tr>
                    <tr><th class="text-muted">Date Given</th><td>{{ $loan->date_given->format('d M Y') }}</td></tr>
                    <tr><th class="text-muted">Total Months</th><td>{{ $loan->total_months }}</td></tr>
                    <tr><th class="text-muted">Paid Months</th><td>{{ $loan->paid_months }}</td></tr>
                    <tr><th class="text-muted">Remaining</th><td>{{ $loan->remaining_months }} month(s)</td></tr>
                    <tr><th class="text-muted">Monthly EMI</th><td>₹{{ number_format($loan->monthly_deduction, 2) }}</td></tr>
                    <tr><th class="text-muted">Status</th>
                        <td><span class="badge bg-{{ $loan->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($loan->status) }}</span></td></tr>
                </table>

                <hr>

                {{-- Interest Breakdown --}}
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th class="text-muted">Principal</th>
                            <td class="text-end fw-semibold">₹{{ number_format($loan->amount, 2) }}</td>
                        </tr>
                        @if($loan->interest_rate > 0)
                        <tr>
                            <th class="text-muted">
                                Interest Rate
                                <span class="text-muted fw-normal" style="font-size:.78rem">
                                    ({{ $loan->interest_rate }}% p.a. × {{ $loan->total_months }} mo)
                                </span>
                            </th>
                            <td class="text-end text-warning fw-semibold">+ ₹{{ number_format($loan->total_interest, 2) }}</td>
                        </tr>
                        <tr class="border-top">
                            <th>Total Due</th>
                            <td class="text-end fw-bold text-primary">₹{{ number_format($loan->total_due, 2) }}</td>
                        </tr>
                        @else
                        <tr>
                            <th class="text-muted">Interest</th>
                            <td class="text-end text-muted">Nil</td>
                        </tr>
                        <tr class="border-top">
                            <th>Total Due</th>
                            <td class="text-end fw-bold text-primary">₹{{ number_format($loan->total_due, 2) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                {{-- Repayment Progress --}}
                <div class="row text-center g-2 mb-2">
                    <div class="col-4">
                        <div class="text-muted" style="font-size:.72rem">Total Due</div>
                        <div class="fw-bold text-primary" style="font-size:.9rem">₹{{ number_format($loan->total_due, 0) }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted" style="font-size:.72rem">Returned</div>
                        <div class="fw-bold text-success" style="font-size:.9rem">₹{{ number_format($loan->returned_amount, 0) }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted" style="font-size:.72rem">Pending</div>
                        <div class="fw-bold text-danger" style="font-size:.9rem">₹{{ number_format($loan->pending_amount, 0) }}</div>
                    </div>
                </div>

                @if($loan->interest_rate > 0 && $loan->returned_amount > 0)
                <div class="small text-muted d-flex justify-content-between mb-1">
                    <span>Principal paid: <strong>₹{{ number_format($loan->principal_paid, 2) }}</strong></span>
                    <span>Interest paid: <strong>₹{{ number_format($loan->interest_paid, 2) }}</strong></span>
                </div>
                @endif

                <div class="progress mb-1" style="height:10px">
                    <div class="progress-bar bg-success" style="width:{{ min(100, $loan->progress_pct) }}%"
                         title="{{ $loan->progress_pct }}% repaid"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>{{ $loan->progress_pct }}% repaid</span>
                    <span>₹{{ number_format($loan->pending_amount, 0) }} left</span>
                </div>

                @if($loan->status === 'active' && $loan->pending_amount > 0)
                <div class="mt-3">
                    <button class="btn btn-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#repayModal">
                        <i class="fa fa-plus me-1"></i>Add Repayment
                    </button>
                </div>
                @elseif($loan->status === 'closed')
                <div class="alert alert-success py-2 mt-3 text-center small mb-0">
                    <i class="fa fa-check-circle me-1"></i>Loan fully settled
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Repayment History ──────────────────────────────────────────────────── --}}
    <div class="col-md-8">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="fa fa-history me-1 text-primary"></i>Repayment History
                </h6>
                @if($loan->status === 'active' && $loan->pending_amount > 0)
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#repayModal">
                    <i class="fa fa-plus me-1"></i>Add Repayment
                </button>
                @endif
            </div>
            <div class="card-body">

                @if($loan->repayments->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="fa fa-inbox fa-2x mb-2 d-block"></i>No repayments recorded yet.
                </div>
                @else

                {{-- Running balance table --}}
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th class="text-end">Amount Paid</th>
                                @if($loan->interest_rate > 0)
                                <th class="text-end">Interest&nbsp;Portion</th>
                                <th class="text-end">Principal&nbsp;Portion</th>
                                @endif
                                <th class="text-end">Balance After</th>
                                <th>Source</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $sortedReps  = $loan->repayments->sortBy('payment_date');
                                $runningBalance = $loan->total_due;
                                $interestRatio  = $loan->total_due > 0 && $loan->total_interest > 0
                                    ? $loan->total_interest / $loan->total_due
                                    : 0;
                                $seq = 0;
                            @endphp
                            @foreach($sortedReps as $rep)
                            @php
                                $seq++;
                                $paid     = (float) $rep->amount_paid;
                                $intPart  = round($paid * $interestRatio, 2);
                                $prinPart = round($paid - $intPart, 2);
                                $runningBalance = max(0, round($runningBalance - $paid, 2));
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $seq }}</td>
                                <td>{{ $rep->payment_date->format('d M Y') }}</td>
                                <td class="text-end fw-semibold text-success">₹{{ number_format($paid, 2) }}</td>
                                @if($loan->interest_rate > 0)
                                <td class="text-end text-warning">₹{{ number_format($intPart, 2) }}</td>
                                <td class="text-end text-info">₹{{ number_format($prinPart, 2) }}</td>
                                @endif
                                <td class="text-end fw-semibold {{ $runningBalance > 0 ? 'text-danger' : 'text-success' }}">
                                    ₹{{ number_format($runningBalance, 2) }}
                                </td>
                                <td>
                                    @if($rep->salary_slip_id)
                                        <span class="badge bg-primary">Salary Slip</span>
                                    @else
                                        <span class="badge bg-secondary">Manual</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $rep->note ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="{{ $loan->interest_rate > 0 ? 2 : 2 }}">Total Repaid</th>
                                <th class="text-end text-success">₹{{ number_format($loan->returned_amount, 2) }}</th>
                                @if($loan->interest_rate > 0)
                                <th class="text-end text-warning">₹{{ number_format($loan->interest_paid, 2) }}</th>
                                <th class="text-end text-info">₹{{ number_format($loan->principal_paid, 2) }}</th>
                                @endif
                                <th class="text-end {{ $loan->pending_amount > 0 ? 'text-danger' : 'text-success' }} fw-bold">
                                    ₹{{ number_format($loan->pending_amount, 2) }}
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Summary chips --}}
                <div class="d-flex gap-3 flex-wrap mt-3 small">
                    <span class="badge bg-light text-dark border py-2 px-3">
                        Total Due: <strong>₹{{ number_format($loan->total_due, 2) }}</strong>
                    </span>
                    <span class="badge bg-success py-2 px-3">
                        Returned: ₹{{ number_format($loan->returned_amount, 2) }}
                    </span>
                    <span class="badge bg-{{ $loan->pending_amount > 0 ? 'danger' : 'success' }} py-2 px-3">
                        {{ $loan->pending_amount > 0 ? 'Pending: ₹'.number_format($loan->pending_amount,2) : '✓ Fully Paid' }}
                    </span>
                    @if($loan->interest_rate > 0)
                    <span class="badge bg-warning text-dark py-2 px-3">
                        Interest paid: ₹{{ number_format($loan->interest_paid, 2) }} / ₹{{ number_format($loan->total_interest, 2) }}
                    </span>
                    @endif
                </div>

                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Add Repayment Modal ──────────────────────────────────────────────────── --}}
@if($loan->status === 'active' && $loan->pending_amount > 0)
<div class="modal fade" id="repayModal" tabindex="-1" aria-labelledby="repayModalLabel">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('loans.repayments.store', $loan) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="repayModalLabel">
                        <i class="fa fa-rupee-sign me-1"></i>Add Repayment
                    </h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger py-2">
                        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                    </div>
                    @endif

                    {{-- Pending balance info --}}
                    <div class="alert alert-info py-2 mb-3 small">
                        <div class="d-flex justify-content-between">
                            <span>Total Due</span>
                            <strong>₹{{ number_format($loan->total_due, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Already Paid</span>
                            <strong class="text-success">₹{{ number_format($loan->returned_amount, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between border-top mt-1 pt-1">
                            <span><strong>Pending Balance</strong></span>
                            <strong class="text-danger">₹{{ number_format($loan->pending_amount, 2) }}</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount Paid <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="amount_paid" step="0.01" min="0.01"
                                   max="{{ $loan->pending_amount }}"
                                   class="form-control @error('amount_paid') is-invalid @enderror"
                                   value="{{ old('amount_paid', min($loan->monthly_deduction, $loan->pending_amount)) }}"
                                   required>
                            @error('amount_paid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text text-muted">
                            Max: ₹{{ number_format($loan->pending_amount, 2) }} (pending balance)
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control"
                               value="{{ old('payment_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <input type="text" name="note" class="form-control"
                               placeholder="e.g. Monthly deduction June 2026"
                               value="{{ old('note') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fa fa-save me-1"></i>Save Repayment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
// Auto-open modal if validation errors exist (user corrects form after bad input)
@if($errors->any())
document.addEventListener('DOMContentLoaded', function() {
    var modal = new bootstrap.Modal(document.getElementById('repayModal'));
    modal.show();
});
@endif
</script>
@endpush
@endsection
