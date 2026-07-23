@extends('layouts.app')
@section('title', 'Create Confirmation Letter')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('confirmation-letters.index') }}" class="text-decoration-none">Confirmation Letters</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection
@section('content')
<div class="card page-card" style="max-width:600px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('confirmation-letters.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Create Confirmation Letter</h5>
    </div>
    <div class="card-body">
        @include('partials.salary-guard-alert')

        <form action="{{ route('confirmation-letters.store') }}" method="POST">
            @csrf
            @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            <div class="row g-3">
                <div class="col-md-8">
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
                <div class="col-md-4">
                    <label class="form-label">Confirmation Date <span class="text-danger">*</span></label>
                    <input type="date" name="confirmation_date" class="form-control @error('confirmation_date') is-invalid @enderror"
                           value="{{ old('confirmation_date', date('Y-m-d')) }}" required>
                    @error('confirmation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Create Letter</button>
                <a href="{{ route('confirmation-letters.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
function checkSalary(sel) {
    var opt = sel.find(':selected');
    var salary = parseFloat(opt.data('salary') || 0);
    if (sel.val() && salary === 0) {
        $('#salaryWarningName').text(opt.data('name') || '');
        $('#salaryWarningLink').attr('href', opt.data('edit') || '#');
        $('#salaryWarningBanner').removeClass('d-none');
    } else { $('#salaryWarningBanner').addClass('d-none'); }
}
$(function(){
    $('#employeeSelect').on('change', function(){ checkSalary($(this)); });
    if ($('#employeeSelect').val()) checkSalary($('#employeeSelect'));
});
</script>
@endpush
