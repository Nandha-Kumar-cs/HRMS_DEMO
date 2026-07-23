@extends('layouts.app')
@section('title','Edit Promotion')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('promotions.index') }}" class="text-decoration-none">Promotions</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('promotions.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Edit Promotion</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('promotions.update', $promotion) }}" method="POST">
            @csrf @method('PUT')
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select">
                        @foreach($employees as $emp)<option value="{{ $emp->id }}" {{ old('employee_id', $promotion->employee_id) == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }} ({{ $emp->employee_code }})</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                    <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', $promotion->effective_date->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Previous Designation</label>
                    <select name="previous_designation_id" class="form-select">
                        <option value="">None</option>
                        @foreach($designations as $d)<option value="{{ $d->id }}" {{ old('previous_designation_id', $promotion->previous_designation_id) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">New Designation <span class="text-danger">*</span></label>
                    <select name="new_designation_id" class="form-select">
                        @foreach($designations as $d)<option value="{{ $d->id }}" {{ old('new_designation_id', $promotion->new_designation_id) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">No Change</option>
                        @foreach($departments as $d)<option value="{{ $d->id }}" {{ old('department_id', $promotion->department_id) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $promotion->remarks) }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Update</button>
                <a href="{{ route('promotions.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
