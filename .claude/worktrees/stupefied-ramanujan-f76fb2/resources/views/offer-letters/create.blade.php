@extends('layouts.app')
@section('title', 'Create Offer Letter')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('offer-letters.index') }}" class="text-decoration-none">Offer Letters</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('offer-letters.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Create Offer Letter</h5>
    </div>
    <div class="card-body">
        @include('partials.salary-guard-alert')

        <form action="{{ route('offer-letters.store') }}" method="POST">
            @csrf
            @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" id="employeeSelect">
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                data-salary="{{ $emp->fixed_salary + $emp->variable_salary }}"
                                data-name="{{ $emp->full_name }}"
                                data-edit="{{ route('employees.edit', $emp) }}"
                                {{ (old('employee_id', $selected?->id) == $emp->id) ? 'selected' : '' }}>
                                {{ $emp->full_name }} ({{ $emp->employee_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Offer Date <span class="text-danger">*</span></label>
                    <input type="date" name="offer_date" class="form-control @error('offer_date') is-invalid @enderror"
                           value="{{ old('offer_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Joining Date <span class="text-danger">*</span></label>
                    <input type="date" name="joining_date" class="form-control @error('joining_date') is-invalid @enderror"
                           value="{{ old('joining_date') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Offered Salary <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="salary" id="salaryField" step="0.01" min="0"
                               class="form-control @error('salary') is-invalid @enderror"
                               value="{{ old('salary', $selected ? $selected->fixed_salary + $selected->variable_salary : '') }}" required>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Terms & Conditions</label>
                    <textarea name="terms" class="form-control" rows="6" placeholder="Enter terms and conditions...">{{ old('terms', 'This offer is contingent upon satisfactory completion of all pre-employment requirements.') }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Create Offer Letter</button>
                <a href="{{ route('offer-letters.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
function checkSalary(sel) {
    var opt    = sel.find(':selected');
    var salary = parseFloat(opt.data('salary') || 0);
    var name   = opt.data('name') || '';
    var editUrl= opt.data('edit') || '#';
    if (sel.val() && salary === 0) {
        $('#salaryWarningName').text(name);
        $('#salaryWarningLink').attr('href', editUrl);
        $('#salaryWarningBanner').removeClass('d-none');
    } else {
        $('#salaryWarningBanner').addClass('d-none');
    }
}
$(function(){
    $('#employeeSelect').on('change', function() {
        var opt    = $(this).find(':selected');
        var salary = parseFloat(opt.data('salary') || 0);
        if (salary > 0) $('#salaryField').val(salary);
        checkSalary($(this));
    });
    // Check on load if pre-selected
    if ($('#employeeSelect').val()) checkSalary($('#employeeSelect'));
});
</script>
@endpush
