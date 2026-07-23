@extends('layouts.app')
@section('title', 'Create Increment Letter')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('increment-letters.index') }}" class="text-decoration-none">Increment Letters</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection
@section('content')
<div class="card page-card" style="max-width:700px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('increment-letters.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Create Increment Letter</h5>
    </div>
    <div class="card-body">
        @include('partials.salary-guard-alert')

        <form action="{{ route('increment-letters.store') }}" method="POST">
            @csrf
            @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" id="empSelect" class="form-select @error('employee_id') is-invalid @enderror">
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
                    <label class="form-label">Old CTC / Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="old_salary" id="oldSalary" step="0.01" min="0"
                               class="form-control @error('old_salary') is-invalid @enderror"
                               value="{{ old('old_salary') }}" oninput="calcIncrement()" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">New CTC / Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="new_salary" id="newSalary" step="0.01" min="0"
                               class="form-control @error('new_salary') is-invalid @enderror"
                               value="{{ old('new_salary') }}" oninput="calcIncrement()" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Increment %</label>
                    <div class="input-group">
                        <input type="number" name="increment_percentage" id="incrPct" step="0.01" class="form-control bg-light" value="{{ old('increment_percentage') }}" readonly>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                    <input type="date" name="effective_date" class="form-control @error('effective_date') is-invalid @enderror"
                           value="{{ old('effective_date', date('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Create Increment Letter</button>
                <a href="{{ route('increment-letters.index') }}" class="btn btn-light">Cancel</a>
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
    if (sel.val() && salary === 0) {
        $('#salaryWarningName').text(opt.data('name') || '');
        $('#salaryWarningLink').attr('href', opt.data('edit') || '#');
        $('#salaryWarningBanner').removeClass('d-none');
    } else { $('#salaryWarningBanner').addClass('d-none'); }
}
$('#empSelect').on('change', function() {
    var sal = parseFloat($(this).find(':selected').data('salary') || 0);
    if (sal > 0) { $('#oldSalary').val(sal); calcIncrement(); }
    checkSalary($(this));
});
function calcIncrement() {
    var old  = parseFloat($('#oldSalary').val()) || 0;
    var newv = parseFloat($('#newSalary').val()) || 0;
    if (old > 0 && newv > 0) {
        $('#incrPct').val(((newv - old) / old * 100).toFixed(2));
    }
}
$(function(){
    if ($('#empSelect').val()) checkSalary($('#empSelect'));
});
</script>
@endpush
