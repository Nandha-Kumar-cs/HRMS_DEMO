@extends('layouts.app')
@section('title','Add Loan / Advance')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('loans.index') }}" class="text-decoration-none">Loans & Advances</a></li>
<li class="breadcrumb-item active">Add</li>
@endsection
@section('content')
<div class="card page-card" style="max-width:780px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('loans.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Add Loan / Advance</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('loans.store') }}" method="POST">
            @csrf
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror">
                        <option value="">— Select Employee —</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}"
                            {{ old('employee_id', $selected?->id) == $emp->id ? 'selected' : '' }}>
                            {{ $emp->full_name }} ({{ $emp->employee_code }})
                        </option>
                        @endforeach
                    </select>
                    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select">
                        <option value="loan"    {{ old('type') === 'loan'    ? 'selected' : '' }}>Loan</option>
                        <option value="advance" {{ old('type') === 'advance' ? 'selected' : '' }}>Advance</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Date Given <span class="text-danger">*</span></label>
                    <input type="date" name="date_given" class="form-control"
                           value="{{ old('date_given', date('Y-m-d')) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Principal Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="amount" id="inpAmount" step="0.01" min="1"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount') }}" required>
                    </div>
                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Interest Rate (% per year)</label>
                    <div class="input-group">
                        <input type="number" name="interest_rate" id="inpRate" step="0.01" min="0" max="100"
                               class="form-control" value="{{ old('interest_rate', 0) }}">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text text-muted">Enter 0 for no interest (advance).</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Total Months <span class="text-danger">*</span></label>
                    <input type="number" name="total_months" id="inpMonths" min="1"
                           class="form-control @error('total_months') is-invalid @enderror"
                           value="{{ old('total_months') }}" required>
                    @error('total_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Auto-calculated summary --}}
                <div class="col-12">
                    <div id="loanSummary" class="alert alert-info py-2 small d-none">
                        <div class="row g-1">
                            <div class="col-auto">Principal: <strong id="sumPrincipal">—</strong></div>
                            <div class="col-auto">+</div>
                            <div class="col-auto">Interest: <strong id="sumInterest" class="text-warning">—</strong></div>
                            <div class="col-auto">=</div>
                            <div class="col-auto">Total Due: <strong id="sumTotal" class="text-primary">—</strong></div>
                            <div class="col-auto ms-3 border-start ps-3">Suggested EMI: <strong id="sumEmi" class="text-success">—</strong></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Monthly Deduction (EMI) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="monthly_deduction" id="inpEmi" step="0.01" min="0.01"
                               class="form-control @error('monthly_deduction') is-invalid @enderror"
                               value="{{ old('monthly_deduction') }}" required>
                    </div>
                    <div class="form-text text-muted">
                        <i class="fa fa-magic me-1 text-success"></i>Auto-calculated from principal, rate &amp; tenure
                    </div>
                    @error('monthly_deduction')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Save</button>
                <a href="{{ route('loans.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
var suggestedEmi = 0;

function calcLoan() {
    var principal = parseFloat($('#inpAmount').val())  || 0;
    var rate      = parseFloat($('#inpRate').val())    || 0;
    var months    = parseInt($('#inpMonths').val())    || 0;

    if (principal <= 0 || months <= 0) {
        $('#loanSummary').addClass('d-none');
        return;
    }

    // Simple interest: P × R × (T / 12)
    var interest = principal * (rate / 100) * (months / 12);
    var total    = principal + interest;
    suggestedEmi = months > 0 ? total / months : 0;

    $('#sumPrincipal').text('₹' + principal.toLocaleString('en-IN', {minimumFractionDigits:2}));
    $('#sumInterest').text('₹' + interest.toLocaleString('en-IN', {minimumFractionDigits:2}));
    $('#sumTotal').text('₹' + total.toLocaleString('en-IN', {minimumFractionDigits:2}));
    $('#sumEmi').text('₹' + suggestedEmi.toFixed(2));
    $('#loanSummary').removeClass('d-none');

    // Auto-fill EMI field
    $('#inpEmi').val(suggestedEmi.toFixed(2));
}

$('#inpAmount, #inpRate, #inpMonths').on('input', calcLoan);

// Run on load if old values populated
calcLoan();
</script>
@endpush
