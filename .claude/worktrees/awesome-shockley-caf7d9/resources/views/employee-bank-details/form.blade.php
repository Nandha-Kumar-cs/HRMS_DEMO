@extends('layouts.app')
@section('title', ($bankDetail ? 'Edit' : 'Add') . ' Bank Details — ' . $employee->full_name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('employees.index') }}" class="text-decoration-none">Employees</a></li>
<li class="breadcrumb-item"><a href="{{ route('employees.show', $employee) }}" class="text-decoration-none">{{ $employee->full_name }}</a></li>
<li class="breadcrumb-item active">Bank Details</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-arrow-left me-1" style="font-size:.8rem"></i>Back
        </a>
        <h5 class="mb-0 fw-semibold">
            <i class="fas fa-university me-2 text-primary"></i>
            {{ $bankDetail ? 'Edit' : 'Add' }} Bank Details — {{ $employee->full_name }}
        </h5>
    </div>
    <div class="card-body" style="max-width:700px">

        @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form action="{{ route('employee-bank-details.upsert', $employee) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                           value="{{ old('bank_name', $bankDetail?->bank_name) }}" placeholder="e.g. State Bank of India" required maxlength="100">
                    @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Holder Name <span class="text-danger">*</span></label>
                    <input type="text" name="account_holder_name" class="form-control @error('account_holder_name') is-invalid @enderror"
                           value="{{ old('account_holder_name', $bankDetail?->account_holder_name ?? $employee->full_name) }}" required maxlength="150">
                    @error('account_holder_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Number <span class="text-danger">*</span></label>
                    <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror"
                           value="{{ old('account_number', $bankDetail?->account_number) }}" placeholder="e.g. 12345678901234"
                           required maxlength="30" autocomplete="off">
                    @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">IFSC Code <span class="text-danger">*</span></label>
                    <input type="text" name="ifsc_code" id="ifscInput" class="form-control @error('ifsc_code') is-invalid @enderror"
                           value="{{ old('ifsc_code', $bankDetail?->ifsc_code) }}" placeholder="e.g. SBIN0001234"
                           required maxlength="20" style="text-transform:uppercase">
                    @error('ifsc_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div id="ifscInfo" class="small text-muted mt-1" style="display:none"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                    <input type="text" name="branch_name" id="branchName" class="form-control @error('branch_name') is-invalid @enderror"
                           value="{{ old('branch_name', $bankDetail?->branch_name) }}" required maxlength="100">
                    @error('branch_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">UPI ID <span class="text-muted small">(optional)</span></label>
                    <input type="text" name="upi_id" class="form-control @error('upi_id') is-invalid @enderror"
                           value="{{ old('upi_id', $bankDetail?->upi_id) }}" placeholder="e.g. name@upi" maxlength="100">
                    @error('upi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="alert alert-info small mt-4 py-2">
                <i class="fa fa-shield-alt me-1"></i>
                Bank details are stored securely and used for salary disbursement only.
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i>{{ $bankDetail ? 'Update' : 'Save' }} Bank Details
                </button>
                <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    // Auto-uppercase IFSC
    $('#ifscInput').on('input', function () {
        this.value = this.value.toUpperCase();
        var val = this.value;
        if (val.length === 11) {
            // Auto-fill bank/branch via Razorpay IFSC public API (optional, graceful fallback)
            $.getJSON('https://ifsc.razorpay.com/' + val, function (data) {
                if (data && data.BANK) {
                    $('#ifscInfo').text(data.BANK + ' — ' + data.BRANCH + ', ' + data.CITY).show();
                    if (!$('#branchName').val()) {
                        $('#branchName').val(data.BRANCH);
                    }
                }
            }).fail(function () { $('#ifscInfo').hide(); });
        } else {
            $('#ifscInfo').hide();
        }
    });
});
</script>
@endpush
