@extends('layouts.app')
@section('title','Edit Increment')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('increments.index') }}" class="text-decoration-none">Increments</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('increments.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Edit Increment</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('increments.update', $increment) }}" method="POST">
            @csrf @method('PUT')
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id', $increment->employee_id) == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Previous Salary <span class="text-danger">*</span></label>
                    <div class="input-group"><span class="input-group-text">₹</span>
                    <input type="number" name="previous_salary" step="0.01" class="form-control" value="{{ old('previous_salary', $increment->previous_salary) }}" required></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">New Salary <span class="text-danger">*</span></label>
                    <div class="input-group"><span class="input-group-text">₹</span>
                    <input type="number" name="new_salary" step="0.01" class="form-control" value="{{ old('new_salary', $increment->new_salary) }}" required></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                    <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', $increment->effective_date->format('Y-m-d')) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $increment->remarks) }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Update</button>
                <a href="{{ route('increments.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
