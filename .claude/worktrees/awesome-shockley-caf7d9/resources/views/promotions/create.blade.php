@extends('layouts.app')
@section('title','Add Promotion')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('promotions.index') }}" class="text-decoration-none">Promotions</a></li>
<li class="breadcrumb-item active">Add Promotion</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('promotions.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Add Promotion</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('promotions.store') }}" method="POST">
            @csrf
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" id="empSelect" class="form-select @error('employee_id') is-invalid @enderror">
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                data-designation="{{ $emp->designation_id }}"
                                data-department="{{ $emp->department_id }}"
                                {{ old('employee_id', $selected?->id) == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }} ({{ $emp->employee_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                    <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Previous Designation</label>
                    <select name="previous_designation_id" id="prevDesig" class="form-select">
                        <option value="">Select</option>
                        @foreach($designations as $d)<option value="{{ $d->id }}" {{ old('previous_designation_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">New Designation <span class="text-danger">*</span></label>
                    <select name="new_designation_id" class="form-select @error('new_designation_id') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach($designations as $d)<option value="{{ $d->id }}" {{ old('new_designation_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach
                    </select>
                    @error('new_designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Department (if changed)</label>
                    <select name="department_id" id="deptSelect" class="form-select">
                        <option value="">Same / No Change</option>
                        @foreach($departments as $d)<option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Save Promotion</button>
                <a href="{{ route('promotions.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
$('#empSelect').on('change', function() {
    var desig = $(this).find(':selected').data('designation');
    var dept  = $(this).find(':selected').data('department');
    if (desig) $('#prevDesig').val(desig);
    if (dept)  $('#deptSelect').val(dept);
});
@if($selected)
$('#empSelect').trigger('change');
@endif
</script>
@endpush
