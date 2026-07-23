@extends('layouts.app')
@section('title','Add Increment')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('increments.index') }}" class="text-decoration-none">Increments</a></li>
<li class="breadcrumb-item active">Add Increment</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('increments.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Add Increment</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('increments.store') }}" method="POST">
            @csrf
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" id="empSelect" class="form-select @error('employee_id') is-invalid @enderror">
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                data-salary="{{ $emp->fixed_salary }}"
                                {{ old('employee_id', $selected?->id) == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }} ({{ $emp->employee_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Previous Salary <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="previous_salary" id="prevSalary" step="0.01" min="0"
                               class="form-control @error('previous_salary') is-invalid @enderror"
                               value="{{ old('previous_salary', $selected?->fixed_salary) }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">New Salary <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="new_salary" id="newSalary" step="0.01" min="0"
                               class="form-control @error('new_salary') is-invalid @enderror"
                               value="{{ old('new_salary') }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                    <input type="date" name="effective_date" id="effectiveDate" class="form-control" value="{{ old('effective_date', date('Y-m-d')) }}" required>
                    <div id="futureDateWarning" class="form-text text-warning d-none">
                        <i class="fa fa-clock me-1"></i>Future date — salary will NOT change today. It applies from this date during payroll generation.
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Increment Preview</label>
                    <div class="form-control bg-light" id="incrPreview" style="color:#16a34a;font-weight:600">—</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Save & Update Salary</button>
                <a href="{{ route('increments.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
$('#empSelect').on('change', function() {
    var salary = $(this).find(':selected').data('salary');
    if (salary) {
        $('#prevSalary').val(parseFloat(salary).toFixed(2));
        updatePreview();
    }
});

function updatePreview() {
    var prev = parseFloat($('#prevSalary').val()) || 0;
    var next = parseFloat($('#newSalary').val()) || 0;
    if (prev > 0 && next > 0) {
        var diff = next - prev;
        var pct = ((diff / prev) * 100).toFixed(2);
        $('#incrPreview').text((diff >= 0 ? '+' : '') + '₹' + diff.toLocaleString('en-IN') + ' (' + pct + '%)');
        $('#incrPreview').css('color', diff >= 0 ? '#16a34a' : '#dc2626');
    }
}

function checkFutureDate() {
    var val = $('#effectiveDate').val();
    if (!val) return;
    var today = new Date(); today.setHours(0,0,0,0);
    var picked = new Date(val);
    if (picked > today) {
        $('#futureDateWarning').removeClass('d-none');
    } else {
        $('#futureDateWarning').addClass('d-none');
    }
}

$('#prevSalary, #newSalary').on('input', updatePreview);
$('#effectiveDate').on('change', checkFutureDate);
$(document).ready(function() {
    if ($('#empSelect').val()) updatePreview();
    checkFutureDate();
});
</script>
@endpush
