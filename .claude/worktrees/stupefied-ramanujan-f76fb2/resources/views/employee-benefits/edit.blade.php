@extends('layouts.app')
@section('title', 'Edit Benefit')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('employee-benefits.index') }}" class="text-decoration-none">Employee Benefits</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="card page-card" style="max-width:700px">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-pen me-2 text-primary"></i>Edit Benefit</h5>
    </div>
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('employee-benefits.update', $employeeBenefit) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select" required>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" {{ old('employee_id', $employeeBenefit->employee_id) == $e->id ? 'selected' : '' }}>
                                {{ $e->full_name }} ({{ $e->employee_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Benefit Fund Type <span class="text-danger">*</span></label>
                    <select name="benefit_fund_type_id" class="form-select" required>
                        @foreach($fundTypes as $t)
                            <option value="{{ $t->id }}" {{ old('benefit_fund_type_id', $employeeBenefit->benefit_fund_type_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $employeeBenefit->amount) }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Effective Month <span class="text-danger">*</span></label>
                    <input type="month" name="effective_month" value="{{ old('effective_month', $employeeBenefit->effective_month?->format('Y-m')) }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active"   {{ old('status', $employeeBenefit->status) === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $employeeBenefit->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description / Notes</label>
                    <textarea name="description" rows="2" class="form-control" maxlength="1000">{{ old('description', $employeeBenefit->description) }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><i class="fa fa-save me-1"></i>Update</button>
                <a href="{{ route('employee-benefits.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
